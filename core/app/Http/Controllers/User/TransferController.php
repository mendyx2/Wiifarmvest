<?php

namespace App\Http\Controllers\User;

use App\Http\Requests\TransferRequest;
use App\Models\Transfer;
use App\Models\User;
use App\Models\Matrix;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Collection; 

class TransferController extends Controller
{
    public function index()
    {   $pageTitle        = 'Transfer Funds';
        $user = auth()->user();
        $bonusFields = $user->getBonusFields();
        $matrix = Matrix::where('user_id', $user->id)->first();
        $bonusPairingFields = $matrix->getPairingFields();
        $totalAvailable = $user->getTotalAvailableBonus() + $matrix->getTotalPairingBonus();

        // dd($bonusPairingFields);
        
        
        $removedId = 'Stockist-Rebate';
        if (!returnStockist(auth()->id())) {

            $remainingUsers = array_filter($bonusFields, function ($data) use ($removedId) {
                return $data['label'] !== $removedId;
            });
            // To re-index the array keys after filtering
            $bonusFields = array_values($remainingUsers);
          
        }
       

        
        $transfers = Transfer::where('user_id', $user->id)
            ->latest()
            ->paginate(10); 

        return view('Template::user.transfer.index', compact('bonusFields', 'totalAvailable', 'transfers','pageTitle','bonusPairingFields'));
    }

    public function transfer(TransferRequest $request)
    {   
        $pageTitle        = 'Transfer Funds';
        $user = auth()->user();
        $sourceField = $request->source_field;
        $amount = $request->amount;

        // Get the label for the source field
        $fieldLabels = [
            'stockist_rebate' => 'Stockist Rebate',
            
            'direct' => 'Direct Bonus',
            'indirect' => 'Indirect Bonus',
            'matching_bonus' => 'Matching Bonus',
            'upgrade_bonus' => 'Upgrade Bonus',
        ];

        $sourceLabel = $fieldLabels[$sourceField] ?? $sourceField;
        
       
        if ($request->source_field == 'unilevel_bonus') {
            //check if the user has done repurchase this month
            //'unilevel_bonus' => 'Unilevel Bonus',
             abort(403, 'Unauthorized action.');
        }
       

        return DB::transaction(function () use ($user, $sourceField, $amount, $sourceLabel) {
            // Check if user has sufficient balance in the source field
            if ($user->$sourceField < $amount) {
                return redirect()->back()->withErrors([
                    'amount' => "Insufficient balance in {$sourceLabel}. Available: " . showAmount($user->$sourceField)
                ]);
            }



            // Perform the transfer
            $user->$sourceField -= $amount;
            $user->balance += $amount;
            $user->save();

            // Record the transfer
            Transfer::create([
                'user_id' => $user->id,
                'source_field' => $sourceField,
                'source_label' => $sourceLabel,
                'amount' => $amount,
                'status' => Transfer::STATUS_COMPLETED,
            ]);

            return redirect()->route('user.transfer.index')->with('success', 
                "Successfully transferred " . showAmount($amount) . " from {$sourceLabel} to balance."
            );
        });
    }

    public function transfer1(TransferRequest $request)
    {    
        //dd($request);
        $pageTitle        = 'Transfer Funds';
        $user = auth()->user();
        $matrix = Matrix::where('user_id', $user->id)->first();
        $sourceField = $request->source_field;
        $amount = $request->amount;
            //pv_left_pairing,pv_right_pairing
        // Get the label for the source field
        //dd($request);
        $fieldLabels = [
            
            'pv_left_pairing' => 'PV left matching bonus',
            'pv_right_pairing' => 'PV right matching bonus',
        ];

        $sourceLabel = $fieldLabels[$sourceField] ?? $sourceField;

        return DB::transaction(function () use ($user, $matrix, $sourceField, $amount, $sourceLabel) {
            // Check if user has sufficient balance in the source field
            if ($matrix->$sourceField < $amount) {
                return redirect()->back()->withErrors([
                    'amount' => "Insufficient balance in {$sourceLabel}. Available: " . showAmount($user->$sourceField)
                ]);
            }

            // Perform the transfer
            $matrix->$sourceField -= $amount;
            $matrix->save();

            $user->balance += $amount;
            $user->save();

            // Record the transfer
            Transfer::create([
                'user_id' => $user->id,
                'source_field' => $sourceField,
                'source_label' => $sourceLabel,
                'amount' => $amount,
                'status' => Transfer::STATUS_COMPLETED,
            ]);

            return redirect()->route('user.transfer.index')->with('success', 
                "Successfully transferred " . showAmount($amount) . " from {$sourceLabel} to balance."
            );
        });
    }


    public function transferAll(Request $request)
    {
        $request->validate([
            'source_field' => 'required|in:stockist_rebate,unilevel_bonus,direct,indirect,matching_bonus,upgrade_bonus'
        ]);

        $user = auth()->user();
        $sourceField = $request->source_field;
        $amount = $user->$sourceField;

        if ($amount <= 0) {
            return redirect()->back()->withErrors([
                'amount' => 'No funds available to transfer from this source.'
            ]);
        }

        return $this->transfer(new TransferRequest([
            'source_field' => $sourceField,
            'amount' => $amount
        ]));
    }

    public function transferAllBonuses()
    {
        $user = auth()->user();
        $totalAmount = $user->getTotalAvailableBonus();

        if ($totalAmount <= 0) {
            return redirect()->back()->withErrors([
                'amount' => 'No funds available to transfer from any bonus fields.'
            ]);
        }

        return DB::transaction(function () use ($user, $totalAmount) {
            $fieldLabels = [
                'stockist_rebate' => 'Stockist Rebate',
                'unilevel_bonus' => 'Unilevel Bonus',
                'direct' => 'Direct Bonus',
                'indirect' => 'Indirect Bonus',
                'matching_bonus' => 'Matching Bonus',
                'upgrade_bonus' => 'Upgrade Bonus',
            ];

            $transferredFrom = [];

            foreach ($fieldLabels as $field => $label) {
                if ($user->$field > 0) {
                    $amount = $user->$field;
                    $user->$field = 0;
                    $transferredFrom[] = $label . ' ($' . number_format($amount, 2) . ')';

                    // Record individual transfers
                    Transfer::create([
                        'user_id' => $user->id,
                        'source_field' => $field,
                        'source_label' => $label,
                        'amount' => $amount,
                        'status' => Transfer::STATUS_COMPLETED,
                    ]);
                }
            }

            $user->balance += $totalAmount;
            $user->save();

            return redirect()->route('Template::user.transfer.index')->with('success', 
                "Successfully transferred " . showAmount($totalAmount, 2) . " from all bonus fields to balance. Sources: " . implode(', ', $transferredFrom)
            );
        });
    }
}