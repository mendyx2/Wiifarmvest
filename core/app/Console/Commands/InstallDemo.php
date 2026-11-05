<?php

namespace App\Console\Commands;

use Database\Seeders\DemoSeeder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Build a demo database from nothing (notes Step 129): the tables
 * (database/demo/schema.sql), the migrations, then sample content
 * (Database\Seeders\DemoSeeder).
 *
 *   php artisan demo:install              into an empty database
 *   php artisan demo:install --if-empty   the same, but quietly does nothing when the
 *                                         database already has tables (safe in a start command)
 *   php artisan demo:install --fresh      wipe a demo database first (asks; refuses on a
 *                                         database that looks live)
 *   php artisan demo:install --if-empty --if-enabled
 *                                         what the start command runs (nixpacks.toml, Procfile):
 *                                         only when DEMO_INSTALL=true and the database is empty
 *
 * It never runs on a database with members unless --fresh is given, and
 * --fresh refuses when there are more than 50 members.
 */
class InstallDemo extends Command
{
    protected $signature = 'demo:install
                            {--if-empty : Do nothing if the database already has tables}
                            {--if-enabled : Do nothing unless the DEMO_INSTALL environment variable is true}
                            {--fresh : Drop every table first (a demo database only)}
                            {--admin-password= : Admin password (default Admin@2026, or DEMO_ADMIN_PASSWORD)}
                            {--member-password= : Password of every demo member (default Demo@2026, or DEMO_MEMBER_PASSWORD)}
                            {--force : Do not ask}';

    protected $description = 'Create a demo database: tables, migrations and sample members, products and bonuses';

    public function handle(): int
    {
        if ($this->option('if-enabled') && !filter_var(env('DEMO_INSTALL'), FILTER_VALIDATE_BOOLEAN)) {
            return self::SUCCESS;   // silent: this runs on every start
        }

        $tables = Schema::getTableListing();

        if ($tables && $this->option('if-empty')) {
            $this->line('demo:install: the database already has tables; nothing done.');
            return self::SUCCESS;
        }

        if ($tables) {
            if (!$this->option('fresh')) {
                $this->error('The database is not empty (' . count($tables) . ' tables). Use an empty database, or --fresh to wipe a demo one.');
                return self::FAILURE;
            }
            $members = Schema::hasTable('users') ? DB::table('users')->count() : 0;
            if ($members > 50) {
                $this->error("This database has {$members} members: it looks live. demo:install will not wipe it.");
                return self::FAILURE;
            }
            if (!$this->option('force') && !$this->confirm('Drop all ' . count($tables) . ' tables of ' . DB::getDatabaseName() . ' and rebuild the demo?')) {
                return self::SUCCESS;
            }
            Schema::dropAllTables();
        }

        $this->info('Creating the tables...');
        $sql = file_get_contents(database_path('demo/schema.sql'));
        foreach (preg_split('/;\s*\n/', $sql) as $statement) {
            $statement = trim(preg_replace('/^--.*$/m', '', $statement));
            if ($statement !== '') {
                DB::unprepared($statement);
            }
        }

        $this->info('Running the migrations...');
        Artisan::call('migrate', ['--force' => true], $this->output);

        $this->info('Adding the demo content (members are registered one by one; this takes a minute)...');
        $admin  = $this->option('admin-password') ?: (env('DEMO_ADMIN_PASSWORD') ?: 'Admin@2026');
        $member = $this->option('member-password') ?: (env('DEMO_MEMBER_PASSWORD') ?: 'Demo@2026');
        (new DemoSeeder())->passwords($admin, $member)->setContainer($this->laravel)->setCommand($this)->__invoke();

        $this->newLine();
        $this->info('Demo ready.');
        $this->table(['Sign in at', 'Username', 'Password'], [
            ['/admin', DemoSeeder::ADMIN_USERNAME, $admin],
            ['/user/login', DemoSeeder::MAIN_MEMBER . ' (also tunde1, zainab, chidi1 ...; ' . DemoSeeder::COMPANY . ' is the company account)', $member],
        ]);

        return self::SUCCESS;
    }
}
