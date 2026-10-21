<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Matrix;
use App\Models\Project;
use App\Models\PvLog;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class ProjectController extends Controller
{
    // This is Charles. just folow my comments
    public function index()
    {
        $pageTitle      = 'My Project';
        $user           = auth()->user();
        $currentProject = $user->project_id ? Project::find($user->project_id) : null;

        /* Only show projects at or above the user's current tier (hide downgradable options)
        */
        $query = Project::where('status', true)->orderBy('sort_order')->orderBy('amount');
        if ($currentProject) {
            $query->where('sort_order', '>=', $currentProject->sort_order);
        }
        $projects = $query->get(); 

        $visa       = (float) ($user->visa ?? 0);
        $currentAmt = $currentProject ? (float) $currentProject->amount : 0.0;
        $money      = fn ($n) => gs('cur_sym') . showAmount($n, currencyFormat: false);

        // Migrated to React + Inertia (resources/js/Pages/User/MyProject.tsx).
        // Legacy view: Template::user.my_project
        return Inertia::render('User/MyProject', [
            'visa'      => $money($visa),
            'current'   => $currentProject ? ['id' => $currentProject->id, 'title' => $currentProject->title, 'amount' => $money($currentAmt), 'pv' => (float) $currentProject->pv] : null,
            'projects'  => $projects->map(function ($project) use ($currentProject, $currentAmt, $visa, $money) {
                $cost = round((float) $project->amount - $currentAmt, 2);
                $isCurrent = $currentProject && $currentProject->id === $project->id;
                return [
                    'id'          => $project->id,
                    'title'       => $project->title,
                    'icon'        => $project->icon,
                    'color'       => $project->color,
                    'amount'      => $money($project->amount),
                    'amountValue' => (float) $project->amount,
                    'pv'          => (float) $project->pv,
                    'pvLabel'     => showAmount($project->pv, currencyFormat: false),
                    'direct'      => showAmount($project->direct_commission, currencyFormat: false),
                    'indirect'    => showAmount($project->indirect_commission, currencyFormat: false),
                    'pairing'     => $project->pairing_per_day > 0 ? $money($project->pairing_per_day) : null,
                    'cashBack'    => $project->cash_back > 0 ? showAmount($project->cash_back, currencyFormat: false) . '%' : null,
                    'upgradeBonus' => (float) $project->upgrade_bonus,
                    'upgradeBonusLabel' => $project->upgrade_bonus > 0 ? showAmount($project->upgrade_bonus, currencyFormat: false) . '%' : null,
                    'monthlyFee'  => $project->monthly_maintenance > 0 ? $money($project->monthly_maintenance) : null,
                    'description' => $project->description ? \Illuminate\Support\Str::limit($project->description, 80) : null,
                    'isCurrent'   => $isCurrent,
                    // Projects at or above the current tier are listed; anything higher (or any, with no project yet) is an upgrade.
                    'upgradable'  => !$isCurrent && (!$currentProject || $project->sort_order > $currentProject->sort_order),
                    'available'   => !$isCurrent && (!$currentProject || $project->sort_order > $currentProject->sort_order) && (bool) $project->upgrade_allowed,
                    'cost'        => $cost,
                    'costLabel'   => $money($cost),
                    'shortfall'   => $visa < $cost ? $money($cost - $visa) : null,
                ];
            })->values(),
            'curSym'     => gs('cur_sym'),
            'upgradeUrl' => route('user.project.upgrade'),
            // Title bar (was the layouts.breadcrumb partial): cart button with item count.
            'cartUrl'    => route('user.cart.index'),
            'cartCount'  => (int) $user->cart_item_count,
        ])->withViewData(compact('pageTitle'));
    }

    public function upgrade(Request $request)
    {   
        $request->validate([
            'project_id' => 'required|integer|exists:projects,id',
        ]);

        $userId          = auth()->id();
        $targetProjectId = (int) $request->project_id;

        try {
            DB::transaction(function () use ($userId, $targetProjectId) {
                // Lock the user row to prevent concurrent double-upgrades
                $user = User::lockForUpdate()->findOrFail($userId);

                $currentProject = $user->project_id ? Project::find($user->project_id) : null;

                $targetProject = Project::where('id', $targetProjectId)
                    ->where('status', true)
                    ->where('upgrade_allowed', true)
                    ->lockForUpdate()
                    ->first();
 
                if (!$targetProject) {
                    throw new \RuntimeException('This project is not available for upgrade.');
                }

                // Idempotency: already on this project
                if ((int) $user->project_id == $targetProject->id) {
                    throw new \RuntimeException('You are already subscribed to this project.');
                }

                // Only allow moving to a higher-tier project
                if ($currentProject && $targetProject->sort_order <= $currentProject->sort_order) {
                    throw new \RuntimeException('You can only upgrade to a higher-tier project.');
                }

                // Cost is the price difference (user already paid for lower tier)
                $currentAmount = $currentProject ? (float) $currentProject->amount : 0.0;
                $upgradeCost = round((float) $targetProject->amount - $currentAmount, 2);

                if ($upgradeCost <= 0) {
                    throw new \RuntimeException('Upgrade cost is invalid. Please contact support on whatsapp.');
                }
                // 
                $visaBalance = (float) ($user->visa ?? 0);
                if ($visaBalance < $upgradeCost) {
                    throw new \RuntimeException(
                        'Insufficient VISA wallet balance. You need '
                        . gs('cur_sym') . showAmount($upgradeCost, currencyFormat: false)
                        . ' but have '
                        . gs('cur_sym') . showAmount($visaBalance, currencyFormat: false) . '.'
                    );
                }

                $trx = getTrx(12);

                // 1. Debit visa wallet and switch project
                $user->visa      -= $upgradeCost;
                $user->project_id = $targetProject->id;
                $user->save();

                // 2. Debit transaction log (visa wallet)
                $detais = 'Project upgrade to ' . $targetProject->title;
                $remark = 'project_upgrade';

                newTransaction($user, $detais, $remark, $upgradeCost, '-', $trx, 10, 0, 'visa');



                // 3.) Credit the upliner the difference in direct bonus commission 

                // this section is about upgrade. so we have to calculate the upgrade bonus. 
                // the upgrade comes in percentage

                // get the dollar value
                $dollar = rDollar();


                $currentUpgradeCommisison = $currentProject ? round(($currentProject->pv * ($currentProject->upgrade_bonus / 100)) * $dollar, 2) : 0.0;

                $newUpgradeCommission=  round(($targetProject->pv * ($targetProject->upgrade_bonus / 100)) * $dollar, 2);
                

                $upgrade_commisison = round($newUpgradeCommission - $currentUpgradeCommisison, 2);


                $current_InDirecCommisison = $currentProject ? (float) $currentProject->amount : 0.0;
                
                // the section is under reviews. 
                // Some user have no Project so this takes care of the errors 

                $current_Project_title = $currentProject ? $currentProject->title : null;

                $details ='Upgrade bonus gotten from '.$user->username.' upgrading to  '.$targetProject->title.' from '.$current_Project_title;

                upgradeBonus($user, $details, $upgrade_commisison, $trx);

                

                // 4. Cash back difference credited to product_wallet
                //    New cash back - old cash back (what user already received at registration)
               

                $newCashBack = round((float)$targetProject->cash_back, 2);
                $oldCashBack = $currentProject
                    ? round((float) $currentProject->cash_back , 2)
                    : 0.0;

                $cashBackDiff = round($newCashBack - $oldCashBack, 2);
                $uDetails = 'Repurchase wallet gotten from an upgrade to ' . $targetProject->title;
                
                processUpgradeCashBack($user, $cashBackDiff, $uDetails, $trx);
                
                // 5.) Direct Upgrade bonus to sponsor (25% difference in PVs per Proposed Plan)
                $currentPV = $currentProject ? (float) $currentProject->pv : 0;
                $targetPV = (float) ($targetProject->pv ?? 0);
                $uplinePVDiff = max(0, $targetPV - $currentPV);
                $details2 = $user->username . ' upgraded to ' . $targetProject->title . ' from ' . $current_Project_title;

                if ($uplinePVDiff > 0) {
                    $directUpgradeBonus = round($uplinePVDiff * 0.25 * rDollar(), 2);
                    $uDetails1 = 'Upgrade direct bonus from ' . $user->username . ' upgrading to ' . $targetProject->title;
                    upgradeBonus($user, $uDetails1, $directUpgradeBonus, $trx);

                    // Debit from isolated Member Compensation Reserve Ledger
                    try {
                        $sponsor = User::find($user->ref_by);
                        if ($sponsor) {
                            app(\App\Services\RevenueAllocationService::class)->disburseCompensation(
                                $directUpgradeBonus,
                                $trx,
                                'Upgrade Direct Bonus to ' . $sponsor->username . ' from ' . $user->username,
                                'CRP',
                                'upgrade_direct_bonus',
                                $sponsor->id
                            );
                        }
                    } catch (\Throwable $e) {
                        \Illuminate\Support\Facades\Log::warning('[RevenueAllocation] Failed to debit upgrade direct bonus: ' . $e->getMessage());
                    }
                }

                updatePV($user, $details2, $uplinePVDiff);

                // 6.) Indirect upgrade bonus across generations 2 to 10 (Gen 2: 2%, Gen 3-10: 1% each)
                $user1 = User::find($user->ref_by);
                if ($user1 && $uplinePVDiff > 0) {
                    $inDirect_bonus_details = 'Upgrade indirect bonus from ' . $user->username . ' upgrading to ' . $targetProject->title;
                    upgradeIndirectBonus($user1, $uplinePVDiff, $inDirect_bonus_details, $trx);
                }

                // Blueprint: Strategic Reserve — Global Share Pool (1.25%)
                try {
                    \App\Models\GlobalPoolLedger::accrue((float) $uplinePVDiff);
                } catch (\Throwable $e) {
                    \Illuminate\Support\Facades\Log::warning('[GlobalPool] Failed to accrue on upgrade: ' . $e->getMessage());
                }


            });
        } catch (\RuntimeException $e) {
            $notify[] = ['error', $e->getMessage()];
            return back()->withNotify($notify);
        } 
        /*catch (\Throwable $e) {
            $notify[] = ['error', 'Something went wrong. Please try again.'];
            return back()->withNotify($notify);
        }
        */

        $notify[] = ['success', 'Your project has been upgraded successfully.'];
        return back()->withNotify($notify);
    }

    


    /**
     * Walk up the binary tree from $user and credit each ancestor with the PV difference.
     * Mirrors the logic in updatePV() but accepts a custom amount instead of $user->project->pv.
     */
    private function propagatePv(User $user, int $pvAmount, string $details): void
    {
        $userMatrix = Matrix::where('stage_id', 1)->where('user_id', $user->id)->first();
        if (!$userMatrix) {
            return;
        }

        $childId  = $user->id;
        $parentId = (int) $userMatrix->parent_id;

        while ($parentId > 0) {
            $parentMatrix = Matrix::where('stage_id', 1)->where('user_id', $parentId)->first();
            if (!$parentMatrix) {
                break;
            }
            if ((int) $parentMatrix->left == $childId) {
                $parentMatrix->pv_left         += $pvAmount;
                $parentMatrix->pv_left_pairing += $pvAmount;
                $position                = 1;
            } else {
                $parentMatrix->pv_right         += $pvAmount;
                $parentMatrix->pv_right_pairing += $pvAmount;
                $position                 = 2;
            }

            $parentMatrix->save();
            
            pvLog($parentMatrix->user_id, $pvAmount, $position, '+', $details);

            $childId  = $parentMatrix->user_id;
            $parentId = (int) $parentMatrix->parent_id;
        }
    }
}
