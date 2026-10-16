<?php

/*
|--------------------------------------------------------------------------
| Financial Report — ledger classification
|--------------------------------------------------------------------------
|
| Maps `transactions.remark` / `stransactions.remark` values to report lines
| for App\Services\FinancialReportService. Keys are lowercase (remarks are
| matched case-insensitively, as MySQL's collation already does).
|
| Only credits (+) are classified here; income from subscriptions, shares,
| products, deposits and withdrawals comes from their own tables.
|
| A credit whose remark appears in neither `outflow` nor `ignore` shows up in
| the report as "Unclassified credits" — add it to one of the lists below.
|
*/

return [

    // Money the company paid to members, per ledger remark (members' ledger)
    'member_payouts' => [
        'direct_commission'         => 'Direct bonus',
        'indirect_bonus_commission' => 'Indirect bonus',
        'upgrade_commission'        => 'Upgrade bonus',
        'matching_bonus'            => 'Matching bonus',
        'binary_commission'         => 'Binary commission',
        'unilevel_bonus'            => 'Unilevel bonus',
        'key_in_bonus'              => 'Key-in bonus',
        'acb_bonus'                 => 'ACB bonus',
        'autoship'                  => 'Autoship credits',
        'affiliate_bonus'           => 'Affiliate bonus',
        'shares_commission'         => 'Shares (land) upline commission',
        'repurchase_award'          => 'Repurchase awards',
        'savings_service_fee'       => 'Commission share locked into savings (10%)',
        'referral_commission'       => 'Referral commission (matrix era)',
        'stageout_commission'       => 'Stage-out commission (matrix era)',
        'joining_commission'        => 'Joining commission (matrix era)',
        'stage_twice_refund'        => 'Stage refunds (matrix era)',
    ],

    // Money the company paid to stockists (members' ledger and stockists' ledger)
    'stockist_payouts' => [
        'stockist_bonus'               => 'Stockist bonus',
        'stockist_rebate'              => 'Stockist rebate',
        'stockist_commission'          => 'Stockist commission',
        'stockist_purchase_commission' => 'Stockist purchase commission',
        'stockist_sales'               => 'Paid for delivering member orders',
        'stockist_redemption'          => 'Welcome-pack & invoice redemptions',
    ],

    // Dividends to FFO shareholders; reversals (debits) are subtracted
    'dividends' => [
        'credit'   => 'shares_divident',
        'reversal' => 'dividend_reversal',
    ],

    // Admin wallet top-ups: credit remark => matching debit remark
    'admin_topups' => [
        'balance_add'        => ['label' => 'Money Box',      'debit' => 'balance_subtract'],
        'visa_wallet_add'    => ['label' => 'Visa wallet',    'debit' => 'visa_wallet_subtract'],
        'product_wallet_add' => ['label' => 'Product wallet', 'debit' => 'product_wallet_subtract'],
    ],

    // Credits that are not company income or cost: internal moves between a
    // member's own wallets, refunds of reversed debits, or money already
    // counted from its own table (deposits, loans, savings, POS logs)
    'ignore' => [
        'deposit', 'bonus_transfer', 'withdraw_reject', 'order_cancel_refund',
        'savings_maturity', 'farm_maturity_payout', 'savings_credit', 'savings_contribution',
        'savings_lock', 'savings_withdrawal', 'admin_savings_transfer',
        'loan_disbursement', 'loan_repayment', 'pos_sale',
        'balance_add', 'visa_wallet_add', 'product_wallet_add', 'shares_divident',
        // A Mini stockist paying a Mega for stock (and refunds of that): money moving
        // between two stockists, already paid to the company when the Mega bought it
        'stock_transfer_sale', 'stock_transfer_refund',
    ],

];
