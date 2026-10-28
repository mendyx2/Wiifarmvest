<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\Pin;
use App\Models\User;
use App\Models\Test;
use App\Models\Matrix;
use App\Models\Rmatrix;
use App\Models\CronJob;
use App\Lib\CurlRequest;
use App\Constants\Status;
use App\Models\UserExtra;
use App\Models\CronJobLog;
use App\Models\Transaction;
use App\Models\MatrixStage;
use App\Models\Rinvestment;
use App\Models\UserStageProgress;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Services\MatrixPlacementService;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use App\Mail\WelcomeMember;
use App\Mail\WelcomeEmail;
use Illuminate\Support\Facades\Mail;


class CronController extends Controller
{    
    protected $matrixService;
    
    public function __construct(MatrixPlacementService $matrixService)
    {
        $this->matrixService = $matrixService;
        
    }
   
    protected function referralLandComission(User $user, $details, $percentage, $amount, $trx_no)
    {   
        $amount_value = ($amount * ($percentage / 100));

        if ($user && $amount_value) {
            $user->balance  +=  $amount_value;
            $user->save();

            $transaction               = new Transaction();
            $transaction->user_id      = $user->id;
            $transaction->amount       = $amount_value;
            $transaction->charge       = 0;
            $transaction->trx_type     = '+';
            $transaction->details      = $details;
            $transaction->remark       = 'Shares_commission';
            $transaction->trx          = $trx_no;
            $transaction->post_balance = $user->balance;
            $transaction->save();

                notify($user, 'Shares_commission', [
                    'trx'          => $trx_no,
                    'amount'       => showAmount($amount, currencyFormat: false),
                    'username'     => $user->username,
                    'post_balance' => showAmount($user->balance, currencyFormat: false),
                ]);
                //Fraction_land_commission
           
        }
    }

    
    
    


    public function cron()
    {
        $general            = gs();
        $general->last_cron = now();
        $general->save();
        
        $crons = CronJob::with('schedule');

        if (request()->alias) {
            $crons->where('alias', request()->alias);
        } else {
            $crons->where('next_run', '<', now())->where('is_running', Status::YES);
        }
        $this->runJobs($crons->get());

        if (request()->target == 'all') {
            $notify[] = ['success', 'Cron executed successfully'];
            return back()->withNotify($notify);
        }
        if (request()->alias) {
            $notify[] = ['success', keyToTitle(request()->alias) . ' executed successfully'];
            return back()->withNotify($notify);
        }
    }

    /** Run the given cron jobs now, logging each one (also used by the admin's "Run Now"). */
    public function runJobs($crons): void
    {
        foreach ($crons as $cron) {
            $cronLog              = new CronJobLog();
            $cronLog->cron_job_id = $cron->id;
            $cronLog->start_at    = now();
            // Built by the container (CronController itself needs a
            // MatrixPlacementService), and any failure, not only an
            // Exception, is logged so one job cannot stop the rest
            try {
                if ($cron->is_default) {
                    $method = $cron->action[1];
                    app($cron->action[0])->$method();
                } else {
                    CurlRequest::curlContent($cron->url);
                }
            } catch (\Throwable $e) {
                report($e);
                $cronLog->error = $e->getMessage();
            }
            $cron->last_run = now();
            $cron->next_run = now()->addSeconds($cron->schedule->interval);
            $cron->save();

            $cronLog->end_at   = $cron->last_run;
            $cronLog->duration = Carbon::parse($cronLog->start_at)->diffInSeconds(Carbon::parse($cronLog->end_at));
            $cronLog->save();
        }
    }

    public function stageOneComplete() {
    Matrix::where('left', '>', 0)
        ->where('right', '>', 0)
        ->where('stage_id', 1)
        ->where('is_active', true)
        ->chunk(50, function($matrices) {
            foreach ($matrices as $mat) {
                try {
                    $this->matrixService->checkStageOne($mat);
                } catch (\Throwable $e) {
                    \Log::error("stageOneComplete failed for matrix {$mat->id}: " . $e->getMessage());
                }
            }
        });
    }


    public function recFire($stage_id){
        

        $stage_id = (int)$stage_id;
        //$count = 0;
        $row =[];
        $matrix = Matrix::where('stage_id', $stage_id)
            ->where('is_active', true)->get();
            foreach($matrix as $mat) {
                $this->matrixService->reconnectDownline($mat);
                $row[] = $mat;
            //$count++;
           }
          // dd($row);
 
    }
   
    
    public function stageOut(){   
        $rows = collect();
        Matrix::where('left', '>', 0)
            ->where('right', '>', 0)
            ->where('stage_id', '>', 1)
            ->where('is_active', 1)
            ->chunk(50, function($matrix) use($rows) {
            foreach($matrix as $mat) {
                // 
                $stage1 = MatrixStage::where('level', $mat->stage_id)->first();

                if ($stage1) {
                   $user = User::find($mat->user_id);
                  
                    if($stage1 && $user){
                       
                        $this->matrixService->checkStageCompletion1($user, $stage1);
                    }
                }
                
                
           
            }
         });    
    
    }
    function reconnectImmediate($stage){
        $stage_id = (int)$stage;
        Matrix::where('stage_id', $stage_id)
            ->chunk(50, function($matrix) {
            foreach($matrix as $mat) {
                if ($mat) {
                    $this->matrixService->reconnectImediateDownline($mat);
                }
            }
         });     
    }

    function reconnectAll($stage){
        $stage_id = (int)$stage;
        Matrix::where('stage_id', $stage_id)
            ->where('is_active', true)
            ->chunk(50, function($matrix) {
            foreach($matrix as $mat) { 
                if ($mat) {
                    $this->matrixService->reconnectDownline($mat);
                }
            }
         });   
        //dd($count);
    }

    /**
     * Dispatch payment processing jobs for all eligible users.
     * Queues one ProcessPayment job per user — runs in the background so no single
     * request blocks while payments are computed.
     *
     * Artisan command : payments:dispatch
     * Command file    : app/Console/Commands/DispatchPaymentProcessing.php
     * Scheduled       : daily at 00:00 via console.php
     * Route           : GET /paymentsDispatch?token=CRON_SECRET  (name: paymentsDispatch)
     * cPanel cron     : curl -s "https://yourdomain.com/paymentsDispatch?token=CRON_SECRET"
     */
    public function paymentsDispatch()
    {
        try {
            Artisan::call('payments:dispatch');
            return response('payments:dispatch OK [' . now() . ']', 200);
        } catch (\Throwable $e) {
            \Log::error('paymentsDispatch cron failed: ' . $e->getMessage());
            return response('payments:dispatch FAILED: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Check and process award qualifications for all users.
     * Evaluates each user's activity / sales metrics against award thresholds
     * and records any newly earned ranks or awards.
     *
     * Artisan command : award:check-qualifications
     * Command file    : app/Console/Commands/CheckAwardQualifications.php
     * Scheduled       : daily at 02:00 via console.php
     * Route           : GET /awardCheck?token=CRON_SECRET  (name: awardCheck)
     * cPanel cron     : curl -s "https://yourdomain.com/awardCheck?token=CRON_SECRET"
     */
    public function awardCheck()
    {
        try {
            Artisan::call('award:check-qualifications');
            return response('award:check-qualifications OK [' . now() . ']', 200);
        } catch (\Throwable $e) {
            \Log::error('awardCheck cron failed: ' . $e->getMessage());
            return response('award:check-qualifications FAILED: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Retry any ACB (Ambassador Cash Back) upline bonus payouts that were
     * missed while the queue was down. Processes pending ACB bonus records
     * and credits the appropriate upline wallets.
     *
     * Artisan command : acb:process
     * Command file    : app/Console/Commands/ProcessAcbBonusCommand.php
     * Scheduled       : hourly via console.php
     * Route           : GET /acbProcess?token=CRON_SECRET  (name: acbProcess)
     * cPanel cron     : curl -s "https://yourdomain.com/acbProcess?token=CRON_SECRET"
     */
    public function acbProcess()
    {
        try {
            Artisan::call('acb:process');
            return response('acb:process OK [' . now() . ']', 200);
        } catch (\Throwable $e) {
            \Log::error('acbProcess cron failed: ' . $e->getMessage());
            return response('acb:process FAILED: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Dump the MySQL database to storage/app/db_bk/ as a gzip-compressed SQL file.
     * The filename embeds the timestamp so each backup is uniquely identifiable.
     * Backups older than 30 days are automatically purged to manage disk usage.
     * The password is never exposed — it is passed to mysqldump via the child
     * process environment (MYSQL_PWD), keeping it invisible in server process lists.
     * 
     * Artisan command : db:backup
     * Command file    : app/Console/Commands/DatabaseBackupCommand.php
     * Backup location : storage/app/db_bk/db_backup_YYYY-MM-DD_HH-II-SS_<random>.sql.gz
     * Scheduled       : daily at 03:00 via console.php
     * Route           : GET /dbBackup?token=CRON_SECRET  (name: dbBackup)
     * cPanel cron     : curl -s "https://yourdomain.com/dbBackup?token=CRON_SECRET"
     */ 
    public function dbBackup()
    {
        try {
            $exitCode = Artisan::call('db:backup');
            $output   = trim(Artisan::output());

            if ($exitCode == 0) {
                return response('db:backup OK — ' . $output . ' [' . now() . ']', 200);
            }

            \Log::error('dbBackup cron — command exited with code ' . $exitCode . ': ' . $output);
            return response('db:backup FAILED: ' . $output, 500);
        } catch (\Throwable $e) {
            \Log::error('dbBackup cron exception: ' . $e->getMessage());
            return response('db:backup FAILED: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Dispatch matching-bonus jobs for all eligible user matrices.
     *
     * Artisan command : matching:dispatch
     * Command file    : app/Console/Commands/DispatchMatchingBonusCommand.php
     * Scheduled       : daily at 01:30 via console.php
     * Route           : GET /matchingDispatch?token=CRON_SECRET  (name: matchingDispatch)
     * cPanel cron     : curl -s "https://yourdomain.com/matchingDispatch?token=CRON_SECRET"
     */
    public function matchingDispatch()
    {
        try {
            Artisan::call('matching:dispatch');
            $output = trim(Artisan::output());
            return response('matching:dispatch OK — ' . $output . ' [' . now() . ']', 200);
        } catch (\Throwable $e) {
            \Log::error('matchingDispatch cron failed: ' . $e->getMessage());
            return response('matching:dispatch FAILED: ' . $e->getMessage(), 500);
        }
    }

    public function autoshipSweep()
    {
        try {
            Artisan::call('autoship:sweep');
            $output = trim(Artisan::output());
            return response('autoship:sweep OK — ' . $output . ' [' . now() . ']', 200);
        } catch (\Throwable $e) {
            \Log::error('autoshipSweep cron failed: ' . $e->getMessage());
            return response('autoship:sweep FAILED: ' . $e->getMessage(), 500);
        }
    }

    public function affiliateOrdersExpire()
    {
        try {
            Artisan::call('affiliate:expire-orders');
            $output = trim(Artisan::output());
            return response('affiliate:expire-orders OK — ' . $output . ' [' . now() . ']', 200);
        } catch (\Throwable $e) {
            \Log::error('affiliateOrdersExpire cron failed: ' . $e->getMessage());
            return response('affiliate:expire-orders FAILED: ' . $e->getMessage(), 500);
        }
    }

    public function clearCache()
    {
        try {
            Artisan::call('optimize:clear');
            $output = trim(Artisan::output());
            return response('optimize:clear OK — ' . $output . ' [' . now() . ']', 200);
        } catch (\Throwable $e) {
            \Log::error('clearCache cron failed: ' . $e->getMessage());
            return response('optimize:clear FAILED: ' . $e->getMessage(), 500);
        }
    }

    private function matchingBound()
    { 
        $generalSetting = gs();
        // Hours and days compared as numbers: date('H') and date('d') give "01",
        // and the setting holds "1", so 01:00–09:00, 24:00 and the 1st never matched
        if ($generalSetting->matching_bonus_time == 'daily') {
            $hour = (int) $generalSetting->matching_when % 24; // "24:00" is hour 0
            if ((int) now()->format('G') !== $hour) {
                return '1';
            }
        }
      

        if ($generalSetting->matching_bonus_time == 'weekly') {
            if (strtolower(now()->format('D')) != $generalSetting->matching_when) {
                return '2';
            }
        }

        if ($generalSetting->matching_bonus_time == 'monthly') {
            if ((int) now()->format('j') !== (int) $generalSetting->matching_when) {
                return '3';
            }
        }
       
     
        if (Carbon::now()->toDateString() > Carbon::parse($generalSetting->last_paid)->toDateString()) {
            $generalSetting->last_paid = Carbon::now()->toDateString();
            $generalSetting->save();

            $eligibleUsers = UserExtra::where('bv_left', '>=', $generalSetting->total_bv)->where('bv_right', '>=', $generalSetting->total_bv)->get();
            foreach ($eligibleUsers as $uex) {
                $weak = $uex->bv_left < $uex->bv_right ? $uex->bv_left : $uex->bv_right;
                $weaker = $weak < $generalSetting->max_bv ? $weak : $generalSetting->max_bv;

                $pair = intval($weaker / $generalSetting->total_bv);

                $bonus = $pair * $generalSetting->bv_price;

                $payment = User::find($uex->user_id);
                $payment->matching_bonus += $bonus;
                $payment->save();

                $user = $payment;

                $trx = new Transaction();
                $trx->user_id = $payment->id;
                $trx->amount = $bonus;
                $trx->charge = 0;
                $trx->trx_type = '+';
                $trx->post_balance = $payment->matching_bonus;
                $trx->remark = 'matching_bonus';
                $trx->trx = getTrx();
                $trx->details = 'Paid ' . showAmount($bonus) . ' For ' . $pair * $generalSetting->total_bv . ' BV.';
                $trx->save();

                notify($user, 'MATCHING_BONUS', [
                    'amount'       => showAmount($bonus, currencyFormat: false),
                    'paid_bv'      => $pair * $generalSetting->total_bv,
                    'post_balance' => showAmount($payment->matching_bonus, currencyFormat: false),
                    'trx'          => $trx->trx,
                ]);

                $paidbv = $pair * $generalSetting->total_bv;
                if ($generalSetting->cary_flash == 0) {
                    $bv['setl'] = $uex->bv_left - $paidbv;
                    $bv['setr'] = $uex->bv_right - $paidbv;
                    $bv['paid'] = $paidbv;
                    $bv['lostl'] = 0;
                    $bv['lostr'] = 0;
                }
                if ($generalSetting->cary_flash == 1) {
                    $bv['setl'] = $uex->bv_left - $weak;
                    $bv['setr'] = $uex->bv_right - $weak;
                    $bv['paid'] = $paidbv;
                    $bv['lostl'] = $weak - $paidbv;
                    $bv['lostr'] = $weak - $paidbv;
                }
                if ($generalSetting->cary_flash == 2) {
                    $bv['setl'] = 0;
                    $bv['setr'] = 0;
                    $bv['paid'] = $paidbv;
                    $bv['lostl'] = $uex->bv_left - $paidbv;
                    $bv['lostr'] = $uex->bv_right - $paidbv;
                }
                $uex->bv_left = $bv['setl'];
                $uex->bv_right = $bv['setr'];
                $uex->save();


                if ($bv['paid'] != 0) {
                    createBVLog($user->id, 1, $bv['paid'], 'Paid ' . $bonus . ' ' . $generalSetting->cur_text . ' For ' . $paidbv . ' BV.');
                    createBVLog($user->id, 2, $bv['paid'], 'Paid ' . $bonus . ' ' . $generalSetting->cur_text . ' For ' . $paidbv . ' BV.');
                }
                if ($bv['lostl'] != 0) {
                    createBVLog($user->id, 1, $bv['lostl'], 'Flush ' . $bv['lostl'] . ' BV after Paid ' . $bonus . ' ' . $generalSetting->cur_text . ' For ' . $paidbv . ' BV.');
                }
                if ($bv['lostr'] != 0) {
                    createBVLog($user->id, 2, $bv['lostr'], 'Flush ' . $bv['lostr'] . ' BV after Paid ' . $bonus . ' ' . $generalSetting->cur_text . ' For ' . $paidbv . ' BV.');
                }
            }
            return '---';
        }
    }
}
