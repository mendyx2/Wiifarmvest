<?php

/*
|--------------------------------------------------------------------------
| USDT (TRC20) deposits to one Wiifarm address (App\Services\Usdt)
|--------------------------------------------------------------------------
|
| The Wiifarm wallet address and the TronGrid API key are set by an admin
| on Payment Gateways → Automatic → USDT (TRC20). What is here is not meant
| to change from the admin panel: which network and token count as USDT,
| and the matching rules. See notes Step 122.
|
*/

return [

    // "mainnet" for real money; "nile" is Tron's test network (free test USDT)
    'network' => env('USDT_TRON_NETWORK', 'mainnet'),

    'networks' => [
        'mainnet' => [
            'api'      => 'https://api.trongrid.io',
            // Tether's official USDT contract on Tron. Only this token counts:
            // anyone can create a token that calls itself "USDT".
            'contract' => 'TR7NHqjeKQxGTCi8q8ZY4pL8otSzgjLj6t',
            'explorer' => 'https://tronscan.org/#/transaction/',
        ],
        'nile' => [
            'api'      => 'https://nile.trongrid.io',
            'contract' => 'TXYZopYRdj2D9XRtbG411XZZ3kM5VkAeBf',
            'explorer' => 'https://nile.tronscan.org/#/transaction/',
        ],
    ],

    // How long a member has to send the payment after the amount is shown
    'window_minutes' => (int) env('USDT_WINDOW_MINUTES', 60),

    // Allowance for clocks: a payment may be stamped up to this long before
    // the deposit was created and still count
    'skew_seconds' => 300,

    // How long after starting a deposit a member may still submit its
    // transaction ID (a late payment then goes to an admin)
    'claim_days' => 7,

    // Seconds between automatic checks from the member's open payment page
    'page_check_seconds' => 15,
];
