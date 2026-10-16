<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PermissionsSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            // Dashboard
            ['name' => 'view dashboard',             'group' => 'Dashboard'],
            ['name' => 'view partner dashboard',     'group' => 'Dashboard'],
            ['name' => 'view deposit withdraw chart','group' => 'Dashboard'],
            ['name' => 'view transaction chart',     'group' => 'Dashboard'],

            // Profile
            ['name' => 'view profile',               'group' => 'Profile'],
            ['name' => 'update profile',             'group' => 'Profile'],
            ['name' => 'update password',            'group' => 'Profile'],

            // Notifications
            ['name' => 'view notifications',         'group' => 'Notifications'],
            ['name' => 'manage notifications',       'group' => 'Notifications'],

            // Admin Management (super-admin)
            ['name' => 'view admins',                'group' => 'Admin Management'],
            ['name' => 'create admins',              'group' => 'Admin Management'],
            ['name' => 'edit admins',                'group' => 'Admin Management'],
            ['name' => 'delete admins',              'group' => 'Admin Management'],
            ['name' => 'toggle admin status',        'group' => 'Admin Management'],

            // Roles (super-admin)
            ['name' => 'view roles',                 'group' => 'Roles'],
            ['name' => 'create roles',               'group' => 'Roles'],
            ['name' => 'edit roles',                 'group' => 'Roles'],
            ['name' => 'delete roles',               'group' => 'Roles'],

            // Permissions (super-admin)
            ['name' => 'manage permissions',         'group' => 'Permissions'],
            ['name' => 'bulk create permissions',    'group' => 'Permissions'],

            // Users
            ['name' => 'view users',                 'group' => 'Users'],
            ['name' => 'edit users',                 'group' => 'Users'],
            ['name' => 'manage user balance',        'group' => 'Users'],
            ['name' => 'manage user kyc',            'group' => 'Users'],
            ['name' => 'manage user status',         'group' => 'Users'],
            ['name' => 'login as user',              'group' => 'Users'],
            ['name' => 'send user notification',     'group' => 'Users'],
            ['name' => 'view user tree',             'group' => 'Users'],
            ['name' => 'view user referral',         'group' => 'Users'],
            ['name' => 'manage user matching bonus', 'group' => 'Users'],
            ['name' => 'view user pins',             'group' => 'Users'],

            // Deposit
            ['name' => 'view deposits',              'group' => 'Deposits'],
            ['name' => 'approve deposits',           'group' => 'Deposits'],
            ['name' => 'reject deposits',            'group' => 'Deposits'],

            // Withdrawals
            ['name' => 'view withdrawals',           'group' => 'Withdrawals'],
            ['name' => 'approve withdrawals',        'group' => 'Withdrawals'],
            ['name' => 'reject withdrawals',         'group' => 'Withdrawals'],
            ['name' => 'bulk action withdrawals',    'group' => 'Withdrawals'],

            // Withdraw Methods
            ['name' => 'view withdraw methods',      'group' => 'Withdraw Methods'],
            ['name' => 'create withdraw methods',    'group' => 'Withdraw Methods'],
            ['name' => 'edit withdraw methods',      'group' => 'Withdraw Methods'],
            ['name' => 'toggle withdraw method status', 'group' => 'Withdraw Methods'],

            // Gateway - Automatic
            ['name' => 'view automatic gateways',   'group' => 'Payment Gateways'],
            ['name' => 'edit automatic gateways',   'group' => 'Payment Gateways'],
            ['name' => 'toggle automatic gateway status', 'group' => 'Payment Gateways'],
            ['name' => 'remove automatic gateway',  'group' => 'Payment Gateways'],

            // Gateway - Manual
            ['name' => 'view manual gateways',      'group' => 'Payment Gateways'],
            ['name' => 'create manual gateways',    'group' => 'Payment Gateways'],
            ['name' => 'edit manual gateways',      'group' => 'Payment Gateways'],
            ['name' => 'toggle manual gateway status', 'group' => 'Payment Gateways'],

            // Stockists
            ['name' => 'view stockists',             'group' => 'Stockists'],
            ['name' => 'manage stockists',           'group' => 'Stockists'],
            ['name' => 'activate stockists',         'group' => 'Stockists'],
            ['name' => 'topup stockist wallet',      'group' => 'Stockists'],
            ['name' => 'view stockist dashboard',    'group' => 'Stockists'],

            // Stockist Orders
            ['name' => 'view stockist orders',       'group' => 'Stockist Orders'],
            ['name' => 'manage stockist orders',     'group' => 'Stockist Orders'],

            // Orders
            ['name' => 'view orders',                'group' => 'Orders'],
            ['name' => 'manage order status',        'group' => 'Orders'],

            // Sub-Orders (Sorder)
            ['name' => 'view sub-orders',            'group' => 'Sub-Orders'],
            ['name' => 'manage sub-order status',    'group' => 'Sub-Orders'],

            // Supply
            ['name' => 'view supply',                'group' => 'Supply'],
            ['name' => 'manage supply status',       'group' => 'Supply'],

            // Plans
            ['name' => 'view plans',                 'group' => 'Plans'],
            ['name' => 'manage plans',               'group' => 'Plans'],
            ['name' => 'view user plans',            'group' => 'Plans'],

            // Categories
            ['name' => 'view categories',            'group' => 'Categories'],
            ['name' => 'manage categories',          'group' => 'Categories'],

            // Products
            ['name' => 'view products',              'group' => 'Products'],
            ['name' => 'create products',            'group' => 'Products'],
            ['name' => 'edit products',              'group' => 'Products'],
            ['name' => 'manage product status',      'group' => 'Products'],
            ['name' => 'manage product feature',     'group' => 'Products'],

            // Savings
            ['name' => 'view savings',               'group' => 'Savings'],
            ['name' => 'manage savings settings',    'group' => 'Savings'],
            ['name' => 'manage savings cycles',      'group' => 'Savings'],

            // Loans
            ['name' => 'view loans',                 'group' => 'Loans'],
            ['name' => 'view loan products',         'group' => 'Loans'],
            ['name' => 'manage loan products',       'group' => 'Loans'],
            ['name' => 'approve loans',              'group' => 'Loans'],
            ['name' => 'reject loans',               'group' => 'Loans'],

            // Dividends (super-admin)
            ['name' => 'view dividends',             'group' => 'Dividends'],
            ['name' => 'create dividends',           'group' => 'Dividends'],
            ['name' => 'buy shares',                 'group' => 'Dividends'],
            ['name' => 'cancel dividends',           'group' => 'Dividends'],
            ['name' => 'reverse dividends',          'group' => 'Dividends'],
            ['name' => 'view dividend investors',    'group' => 'Dividends'],
            ['name' => 'view user dividends',        'group' => 'Dividends'],

            // Reports
            ['name' => 'view transaction report',    'group' => 'Reports'],
            ['name' => 'view login history',         'group' => 'Reports'],
            ['name' => 'view notification history',  'group' => 'Reports'],
            ['name' => 'view investment report',     'group' => 'Reports'],
            ['name' => 'view bv log',                'group' => 'Reports'],
            ['name' => 'view referral commission',   'group' => 'Reports'],
            ['name' => 'view stageout commission',   'group' => 'Reports'],
            ['name' => 'view binary commission',     'group' => 'Reports'],

            // Support Tickets
            ['name' => 'view tickets',               'group' => 'Support Tickets'],
            ['name' => 'reply tickets',              'group' => 'Support Tickets'],
            ['name' => 'close tickets',              'group' => 'Support Tickets'],
            ['name' => 'delete tickets',             'group' => 'Support Tickets'],

            // KYC
            ['name' => 'manage kyc settings',        'group' => 'KYC'],

            // Notification Settings
            ['name' => 'manage email notifications', 'group' => 'Notification Settings'],
            ['name' => 'manage sms notifications',   'group' => 'Notification Settings'],
            ['name' => 'manage push notifications',  'group' => 'Notification Settings'],
            ['name' => 'manage notification templates', 'group' => 'Notification Settings'],

            // Extensions
            ['name' => 'view extensions',            'group' => 'Extensions'],
            ['name' => 'manage extensions',          'group' => 'Extensions'],

            // Flashcards
            ['name' => 'view flashcards',            'group' => 'Flashcards'],
            ['name' => 'manage flashcards',          'group' => 'Flashcards'],

            // Pins
            ['name' => 'view pins',                  'group' => 'Pins'],
            ['name' => 'generate pins',              'group' => 'Pins'],

            // Frontend
            ['name' => 'view frontend',              'group' => 'Frontend'],
            ['name' => 'manage frontend content',    'group' => 'Frontend'],
            ['name' => 'manage pages',               'group' => 'Frontend'],
            ['name' => 'manage frontend seo',        'group' => 'Frontend'],
            ['name' => 'manage frontend templates',  'group' => 'Frontend'],

            // Language
            ['name' => 'view language',              'group' => 'Language'],
            ['name' => 'manage language',            'group' => 'Language'],

            // General Settings
            ['name' => 'view settings',              'group' => 'Settings'],
            ['name' => 'manage general settings',    'group' => 'Settings'],
            ['name' => 'manage system configuration','group' => 'Settings'],
            ['name' => 'manage logo icon',           'group' => 'Settings'],
            ['name' => 'manage custom css',          'group' => 'Settings'],
            ['name' => 'manage sitemap',             'group' => 'Settings'],
            ['name' => 'manage robots txt',          'group' => 'Settings'],
            ['name' => 'manage cookie settings',     'group' => 'Settings'],
            ['name' => 'manage maintenance mode',    'group' => 'Settings'],
            ['name' => 'manage notice',              'group' => 'Settings'],
            ['name' => 'manage seo',                 'group' => 'Settings'],

            // Cron
            ['name' => 'view cron jobs',             'group' => 'Cron'],
            ['name' => 'manage cron jobs',           'group' => 'Cron'],
            ['name' => 'manage cron schedules',      'group' => 'Cron'],

            // System
            ['name' => 'view system info',           'group' => 'System'],
            ['name' => 'manage system',              'group' => 'System'],
            ['name' => 'view admin action log',      'group' => 'System'],
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(
                ['name' => $permission['name'], 'guard_name' => 'admin'],
                ['group' => $permission['group']]
            );
        }

        $this->command->info('Admin permissions seeded successfully. Total: ' . count($permissions));

        // Attach all admin permissions to the super-admin role
        $superAdmin = Role::firstOrCreate(
            ['name' => 'super-admin', 'guard_name' => 'admin']
        );

        $allPermissions = Permission::where('guard_name', 'admin')->pluck('id');
        $superAdmin->syncPermissions($allPermissions);

        $this->command->info('All permissions assigned to super-admin role. Total: ' . $allPermissions->count());
    }
}
