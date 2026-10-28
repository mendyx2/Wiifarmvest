<?php

/*
|--------------------------------------------------------------------------
| Admin panel access map (deny by default)
|--------------------------------------------------------------------------
|
| Enforced for every authenticated admin route by AdminAccessMiddleware,
| and used by the sidebar to hide links an admin can't open.
|
|   1. super_role passes everything.
|   2. super_admin_only routes are refused to everyone else, whatever
|      permissions their role holds.
|   3. open routes are allowed for any logged-in admin.
|   4. permissions maps a route-name pattern to the Spatie permission it
|      needs (first match wins). A value may be keyed by HTTP method, with
|      '*' as the fallback — e.g. view with GET, change with anything else.
|   5. Any route not matched above is super-admin only.
|
| Patterns use Str::is() wildcards against the route NAME. Routes without
| their own name are all called "admin." and so fall through to rule 5.
|
| Permission names must match the ones in Admin → Permissions exactly; a
| name that doesn't exist there denies access rather than erroring.
|
*/

return [

    'super_role' => 'super-admin',

    'super_admin_only' => [
        'admin.users.login',                 // impersonate a member
        'admin.users.update_again',          // change sponsor
        'admin.users.update_again1',         // change username
        'admin.users.update_password',       // reset a member's password
        'admin.users.add.sub.*',             // credit / debit wallets
        'admin.users.buy.shares',            // buy shares from a member's balance
        'admin.users.matching-bonus.update',
        'admin.users.notification.all*',     // message every member at once
        'admin.system.*',                    // includes the code updater
        'admin.gateway.*',                   // payment gateway keys
        'admin.extensions.*',
        'admin.cron.*',
        'admin.stockist.transfers.cancel', // refunds a held stock-transfer payment
    ],

    'open' => [
        'admin.dashboard',
        'admin.chart.*',                     // dashboard widgets
        'admin.notifications*',
        'admin.notification.read',
        'admin.search.index',                // lists only pages the admin may open
        'admin.logout',
    ],

    'permissions' => [
        // Own account
        'admin.profile*'           => 'manage account',
        'admin.password'           => 'manage account',
        'admin.password.update'    => 'manage account',

        // Members
        'admin.users.*'            => ['GET' => 'view users', '*' => 'edit users'],
        'admin.download.attachment' => 'view users',
        'admin.view.attachment'    => 'view users',

        // Money out
        'admin.withdraw.data.*'    => 'Manage Withdrawals',
        'admin.withdraw.method.*'  => 'manage settings',

        // Catalogue & stockists
        'admin.category.*'         => 'manage categories',
        'admin.product.*'          => 'manage categories',
        'admin.stockist*'          => 'manage stockist',

        // Marketing
        'admin.ads.*'              => 'manage ads',
        'admin.affiliate.*'        => 'manage ads',

        // Reports
        'admin.report.*'           => 'Report',

        // Site settings
        'admin.setting.*'          => 'manage settings',
        'admin.frontend.*'         => 'manage settings',
        'admin.seo*'               => 'manage settings',
        'admin.language.*'         => 'manage settings',
        'admin.maintenance.*'      => 'manage settings',
        'admin.kyc.*'              => 'manage settings',
        'admin.notices.*'          => 'manage settings',

        // Staff
        'admin.admins.*'           => 'manage admin',
        'admin.roles.*'            => 'manage roles',
        'admin.permissions.*'      => 'manage permissions',
    ],

];
