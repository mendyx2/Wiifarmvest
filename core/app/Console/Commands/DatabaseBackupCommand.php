<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class DatabaseBackupCommand extends Command
{
    protected $signature   = 'db:backup';
    protected $description = 'Dump the MySQL database to storage/app/db_bk/ as a gzip-compressed SQL file.';

    // Number of days to retain backup files before auto-purging
    private const RETENTION_DAYS = 30;

    public function handle(): int
    {
        $connection = config('database.default');
        $db         = config("database.connections.{$connection}");

        if (($db['driver'] ?? '') !== 'mysql') {
            Log::error('db:backup — only MySQL connections are supported.');
            $this->error('Only MySQL connections are supported.');
            return self::FAILURE;
        }

        // Resolve mysqldump binary — cPanel servers typically have it at /usr/bin
        $mysqldump = $this->resolveMysqldump();
        if (!$mysqldump) {
            Log::error('db:backup — mysqldump binary not found on this server.');
            $this->error('mysqldump not found.');
            return self::FAILURE;
        }

        $backupDir = storage_path('app/db_bk');
        if (!is_dir($backupDir) && !mkdir($backupDir, 0750, true)) {
            Log::error("db:backup — could not create backup directory: {$backupDir}");
            $this->error('Cannot create backup directory.');
            return self::FAILURE;
        }

        // Random suffix: a backup must never be findable by guessing the cron time
        $filename = 'db_backup_' . now()->format('Y-m-d_H-i-s') . '_' . bin2hex(random_bytes(8)) . '.sql.gz';
        $filepath = $backupDir . DIRECTORY_SEPARATOR . $filename;

        $host     = $db['host']        ?? '127.0.0.1';
        $port     = (int) ($db['port'] ?? 3306);
        $socket   = $db['unix_socket'] ?? '';
        $database = $db['database'];
        $username = $db['username'];
        $password = $db['password'];

        // Build mysqldump flags
        // --single-transaction  : consistent InnoDB snapshot without locking tables
        // --routines            : include stored procedures and functions
        // --triggers            : include triggers (explicit, default on)
        // --events              : include scheduled events
        // --no-tablespaces      : avoids needing PROCESS privilege on shared hosting
        // --skip-lock-tables    : safe companion to --single-transaction
        $flags = '--single-transaction --routines --triggers --events --no-tablespaces --skip-lock-tables';

        // On cPanel the DB often listens on a unix socket; prefer it over TCP
        $connection_flags = $socket
            ? sprintf('--socket=%s', escapeshellarg($socket))
            : sprintf('--host=%s --port=%d', escapeshellarg($host), $port);

        $cmd = sprintf(
            '%s %s --user=%s %s %s',
            escapeshellcmd($mysqldump),
            $flags,
            escapeshellarg($username),
            $connection_flags,
            escapeshellarg($database)
        );

        // Password is injected via the child process environment only.
        // It is never appended to the command string, so it is invisible in `ps aux`.
        $env = array_merge(getenv() ?: [], ['MYSQL_PWD' => $password]);

        $descriptors = [
            0 => ['pipe', 'r'],  // stdin
            1 => ['pipe', 'w'],  // stdout — the SQL dump stream
            2 => ['pipe', 'w'],  // stderr — mysqldump warnings / errors
        ];

        $process = proc_open($cmd, $descriptors, $pipes, null, $env);

        if (!is_resource($process)) {
            Log::error('db:backup — proc_open failed to launch mysqldump.');
            $this->error('Failed to launch mysqldump.');
            return self::FAILURE;
        }

        fclose($pipes[0]); // we send nothing on stdin

        // Compress the dump stream on-the-fly using PHP native gzip (level 9)
        $gz = gzopen($filepath, 'wb9');
        if (!$gz) {
            proc_close($process);
            Log::error("db:backup — could not open gzip output file: {$filepath}");
            $this->error('Cannot write to backup file.');
            return self::FAILURE;
        }

        while (!feof($pipes[1])) {
            gzwrite($gz, fread($pipes[1], 65536));
        }
        gzclose($gz);

        $stderr   = trim(stream_get_contents($pipes[2]));
        fclose($pipes[1]);
        fclose($pipes[2]);
        $exitCode = proc_close($process);

        if ($exitCode !== 0) {
            @unlink($filepath); // remove the partial / corrupt file
            Log::error("db:backup — mysqldump exited {$exitCode}. stderr: {$stderr}");
            $this->error("mysqldump failed (exit {$exitCode}): {$stderr}");
            return self::FAILURE;
        }

        // Warn on stderr but do not treat as failure (mysqldump prints harmless notices)
        if ($stderr) {
            Log::warning("db:backup — mysqldump stderr: {$stderr}");
        }

        // Lock down the file — only the web-server user can read it
        chmod($filepath, 0640);

        $this->purgeOldBackups($backupDir);

        $size = $this->humanFileSize(filesize($filepath));
        $message = "{$filename} ({$size})";

        Log::info("db:backup — {$message}");
        $this->info($message); // captured by Artisan::output() in the controller
        return self::SUCCESS;
    }

    /**
     * Delete compressed backups older than RETENTION_DAYS to manage disk usage.
     */
    private function purgeOldBackups(string $dir): void
    {
        $cutoff = now()->subDays(self::RETENTION_DAYS)->timestamp;

        foreach (glob($dir . '/db_backup_*.sql.gz') ?: [] as $file) {
            if (filemtime($file) < $cutoff) {
                @unlink($file);
                Log::info('db:backup — purged old backup: ' . basename($file));
            }
        }
    }

    /**
     * Locate mysqldump, checking common cPanel / Linux paths if `which` fails.
     */
    private function resolveMysqldump(): string|false
    {
        $candidates = [
            trim((string) shell_exec('which mysqldump 2>/dev/null')),
            '/usr/bin/mysqldump',
            '/usr/local/bin/mysqldump',
            '/usr/local/mysql/bin/mysqldump',
        ];

        foreach ($candidates as $path) {
            if ($path && is_executable($path)) {
                return $path;
            }
        }

        return false;
    }

    /**
     * Convert bytes to a human-readable string (B / KB / MB / GB).
     */
    private function humanFileSize(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $i     = 0;
        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }
        return round($bytes, 2) . ' ' . $units[$i];
    }
}
