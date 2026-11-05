<?php

namespace Database\Seeders;

use App\Constants\Status;
use App\Models\Admin;
use App\Models\Matrix;
use App\Models\Project;
use App\Models\Rmatrix;
use App\Models\Stockist;
use App\Models\User;
use App\Services\MatchingBonusService;
use App\Services\RevenueAllocationService;
use App\Services\StockistEnrollmentBonusService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Demo content for a showcase site (php artisan demo:install, notes Step 129).
 *
 * Settings, home page, products and a binary network of fictional members.
 * The members are registered through the app's own code
 * (complete_registration_pin), so their bonuses, PV and tree are exactly
 * what real sign-ups produce; matching and a stockist activation then run
 * through the real services too. Notifications are switched off.
 *
 * Never for a live database: every name, e-mail and phone number is made up.
 */
class DemoSeeder extends Seeder
{
    public const ADMIN_USERNAME = 'admin';
    public const MAIN_MEMBER    = 'amaka1';
    public const COMPANY        = 'wiifarm';

    public function __construct(private string $adminPassword = 'Admin@2026', private string $memberPassword = 'Demo@2026')
    {
    }

    public function passwords(string $admin, string $member): static
    {
        $this->adminPassword  = $admin;
        $this->memberPassword = $member;

        return $this;
    }

    public function run(): void
    {
        $_SERVER['REMOTE_ADDR'] ??= '127.0.0.1';   // registration reads the visitor's IP
        Carbon::setTestNow(now()->subDays(30)->setTime(9, 0));

        $this->settings();
        $this->content();
        $this->admin();
        $this->shop();
        $this->paymentMethods();
        $this->members();

        Carbon::setTestNow();
        Cache::flush();
    }

    private function settings(): void
    {
        DB::table('general_settings')->updateOrInsert(['id' => 1], [
            'site_name'              => 'Wiifarm',
            'active_template'        => 'basic',
            'secure_password'        => 0,
            'base_color'             => '2E7D32',
            'secondary_color'        => 'C8973A',
            'paginate_number'        => 20,
            'cur_sym'                => '₦',
            'cur_text'               => 'NGN',
            'currency_format'        => 1,
            // e-mail, SMS and push off: a demo must not message anyone
            'en'                     => 0,
            'sn'                     => 0,
            'pn'                     => 0,
            'mail_config'            => json_encode(['name' => 'php']),
            'sms_config'             => json_encode(['name' => 'custom']),
            'firebase_config'        => json_encode([]),
            'email_template'         => '<p>{{message}}</p>',
            'sms_template'           => 'Hi {{fullname}}, {{message}}',
            'push_template'          => '{{message}}',
            'push_title'             => 'Wiifarm',
            'email_from'             => 'no-reply@example.com',
            'email_from_name'        => 'Wiifarm (demo)',
            'sms_from'               => 'Wiifarm',
            'bal_trans_fixed_charge' => 0,
            'bal_trans_per_charge'   => 0,
            'notice'                 => '<p>Welcome to the Wiifarm demo. Everything here is sample data.</p>',
            'free_user_notice'       => null,
            'bv_price'               => 600,
            'total_bv'               => 50,
            'max_bv'                 => 0,
            'cary_flash'             => 0,
            'matching_bonus_time'    => 'daily',
            'matching_when'          => '1',
            'maintenance_mode'       => 0,
            'multi_language'         => 0,
            'agree'                  => 1,
            'force_ssl'              => 0,
            'registration'           => 1,
            'kv'                     => 0,
            'ev'                     => 0,
            'sv'                     => 0,
            'global_shortcodes'      => json_encode(['site_name' => 'Name of your site', 'site_currency' => 'Currency of your site']),
            'created_at'             => now(),
            'updated_at'             => now(),
        ]);

        DB::table('languages')->insert(['name' => 'English', 'code' => 'en', 'is_default' => 1, 'created_at' => now(), 'updated_at' => now()]);
        DB::table('matrix_stages')->insert([
            ['name' => 'stage-one', 'level' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'stage-two', 'level' => 2, 'created_at' => now(), 'updated_at' => now()],
        ]);

        // The compensation plan as the repository defines it
        foreach ([StateSeeder::class, PermissionsSeeder::class, CompensationPlanProjectsSeeder::class, CompensationPlanAwardsSeeder::class, CompensationPlanUnilevelSeeder::class, CompensationPlanRepurchaseAwardsSeeder::class] as $seeder) {
            $this->call($seeder);
        }
        Project::query()->update(['description' => DB::raw("CONCAT(title, ' package')")]);
        Project::where('sort_order', 1)->update(['is_default' => 1]);
    }

    private function content(): void
    {
        $row = fn (string $key, array $values, array $extra = []) => DB::table('frontends')->insert($extra + [
            'data_keys' => $key, 'data_values' => json_encode($values), 'tempname' => 'basic', 'created_at' => now(), 'updated_at' => now(),
        ]);

        $row('banner.content', [
            'heading'           => 'Grow with Wiifarm Cooperative',
            'sub_heading'       => 'Farm produce, a member network that rewards you, and savings that work for you.',
            'left_button'       => 'Join Now',
            'left_button_link'  => '/user/register',
            'right_button'      => 'Shop Products',
            'right_button_link' => '/shop',
            'image'             => '6669759fc99161718187423.jpg',
        ]);
        $row('breadcrumb.content', ['image' => '6669940eb31f41718195214.jpg']);
        $row('footer_section.content', ['description' => 'Wiifarm Cooperative Society: farm produce, member rewards and savings.']);
        $row('contact_us.content', ['title' => 'Get in touch', 'map_iframe_url' => '', 'image' => '666a9509b41ab1718261001.png']);
        $row('contact_us.element', ['title' => 'Address', 'content' => '12 Demo Street, Ikeja, Lagos', 'image' => '666a9523882101718261027.png']);
        $row('contact_us.element', ['title' => 'Email', 'content' => 'hello@example.com', 'image' => '666a953f8395b1718261055.png']);
        $row('contact_us.element', ['title' => 'Phone', 'content' => '+234 800 000 0000', 'image' => '666a955e809381718261086.png']);
        $row('social_icon.element', ['title' => 'Facebook', 'url' => 'https://facebook.com', 'social_icon' => '<i class="fab fa-facebook-f"></i>']);
        $row('social_icon.element', ['title' => 'Instagram', 'url' => 'https://instagram.com', 'social_icon' => '<i class="fab fa-instagram"></i>']);
        foreach (['Privacy Policy' => 'privacy-policy', 'Terms of Service' => 'terms-of-service'] as $title => $slug) {
            $row('policy_pages.element', ['title' => $title, 'details' => "<p>This is the demo {$title}. Replace it with your own in Admin → Frontend.</p>"], ['slug' => $slug]);
        }
        $row('cookie.data', ['short_desc' => 'We use cookies to keep you signed in.', 'description' => '<p>Cookies keep you signed in and remember your cart.</p>', 'status' => 1], ['tempname' => null]);
        $row('maintenance.data', ['description' => '<p>We will be back shortly.</p>', 'image' => null], ['tempname' => null]);
        $row('seo.data', ['description' => 'Wiifarm Cooperative Society', 'keywords' => ['farm', 'cooperative'], 'social_title' => 'Wiifarm', 'social_description' => 'Farm produce and member rewards', 'image' => null]);

        DB::table('pages')->insert(['name' => 'HOME', 'slug' => '/', 'tempname' => 'templates.basic.', 'secs' => '[]', 'is_default' => 1, 'created_at' => now(), 'updated_at' => now()]);
    }

    private function admin(): void
    {
        $admin = new Admin();
        $admin->forceFill([
            'name' => 'Demo Admin', 'username' => self::ADMIN_USERNAME, 'email' => 'admin@example.com',
            'password' => Hash::make($this->adminPassword), 'is_active' => 1,
        ])->save();
        $admin->assignRole('super-admin');
    }

    private function shop(): void
    {
        $cat = fn (string $name) => DB::table('categories')->insertGetId(['name' => $name, 'status' => 1, 'created_at' => now(), 'updated_at' => now()]);
        $grains = $cat('Grains'); $oils = $cat('Oils'); $spices = $cat('Spices & Seasoning');

        $images   = array_values(array_filter(scandir(base_path('../assets/images/products')) ?: [], fn ($f) => preg_match('/\.(jpe?g|png)$/i', $f)));
        $products = [
            [$grains, 'Local Rice 50kg', 52000, 40], [$grains, 'Brown Beans 25kg', 38000, 30], [$grains, 'Yellow Garri 10kg', 9500, 8],
            [$oils, 'Red Palm Oil 5L', 11000, 10], [$oils, 'Groundnut Oil 3L', 8500, 7],
            [$spices, 'Dried Pepper 1kg', 4500, 4], [$spices, 'Crayfish 1kg', 7000, 6],
        ];
        $states = DB::table('states')->pluck('id');
        foreach ($products as $i => [$category, $name, $price, $pv]) {
            $id = DB::table('products')->insertGetId([
                'category_id' => $category, 'name' => $name, 'slug' => Str::slug($name), 'sku' => 'WF-' . str_pad((string) ($i + 1), 3, '0', STR_PAD_LEFT),
                'price' => $price, 'selling_price' => $price, 'quantity' => 500, 'pv' => $pv, 'bv' => 0, 'prb' => 0,
                'thumbnail' => $images[$i % max(count($images), 1)] ?? null,
                'description' => "<p>{$name}, sourced from Wiifarm member farms.</p>", 'specifications' => json_encode([]),
                'min_order_quantity' => 1, 'max_order_quantity' => 0, 'is_featured' => $i < 4 ? 1 : 0, 'status' => 1,
                'created_at' => now(), 'updated_at' => now(),
            ]);
            DB::table('product_state_prices')->insert($states->map(fn ($s) => ['product_id' => $id, 'state_id' => $s, 'price' => $price, 'mega_stockist_bonus' => 0, 'micro_stockist_bonus' => 0, 'created_at' => now(), 'updated_at' => now()])->all());
        }
    }

    private function paymentMethods(): void
    {
        $form = DB::table('forms')->insertGetId(['act' => 'withdraw_method', 'form_data' => json_encode([
            'bank_name'      => ['name' => 'Bank Name', 'label' => 'bank_name', 'is_required' => 'required', 'instruction' => '', 'extensions' => '', 'options' => [], 'type' => 'text', 'width' => '12'],
            'account_number' => ['name' => 'Account Number', 'label' => 'account_number', 'is_required' => 'required', 'instruction' => '', 'extensions' => '', 'options' => [], 'type' => 'text', 'width' => '12'],
            'account_name'   => ['name' => 'Account Name', 'label' => 'account_name', 'is_required' => 'required', 'instruction' => '', 'extensions' => '', 'options' => [], 'type' => 'text', 'width' => '12'],
        ]), 'created_at' => now(), 'updated_at' => now()]);
        DB::table('withdraw_methods')->insert([
            'form_id' => $form, 'name' => 'Bank Transfer', 'image' => null, 'min_limit' => 1000, 'max_limit' => 1000000,
            'fixed_charge' => 100, 'percent_charge' => 0, 'rate' => 1, 'currency' => 'NGN',
            'description' => '<p>Paid to your bank account within 24 hours.</p>', 'status' => 1, 'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    /**
     * The network. Each row: username, first, last, package slug, sponsor, tree parent, leg.
     * Registered in this order, each through complete_registration_pin().
     */
    private const MEMBERS = [
        ['amaka1',  'Amaka',   'Okafor',   'wiifarm-mega',        'wiifarm', 'wiifarm', 'left'],
        ['tunde1',  'Tunde',   'Bakare',   'wiifarm-executive',   'amaka1',  'amaka1',  'left'],
        ['zainab',  'Zainab',  'Bello',    'wiifarm-executive',   'amaka1',  'amaka1',  'right'],
        ['chidi1',  'Chidi',   'Nwosu',    'wiifarm-booster',     'tunde1',  'tunde1',  'left'],
        ['funmi1',  'Funmi',   'Adeyemi',  'wiifarm-builder',     'tunde1',  'tunde1',  'right'],
        ['ibrahim', 'Ibrahim', 'Musa',     'wiifarm-booster',     'zainab',  'zainab',  'left'],
        ['ngozi1',  'Ngozi',   'Eze',      'wiifarm-advance',     'zainab',  'zainab',  'right'],
        ['emeka1',  'Emeka',   'Obi',      'wiifarm-starter',     'chidi1',  'chidi1',  'left'],
        ['aisha1',  'Aisha',   'Lawal',    'wiifarm-pre-starter', 'chidi1',  'chidi1',  'right'],
        ['segun1',  'Segun',   'Afolabi',  'wiifarm-builder',     'funmi1',  'funmi1',  'left'],
        ['kemi01',  'Kemi',    'Ojo',      'wiifarm-starter',     'ibrahim', 'ibrahim', 'left'],
        ['yusuf1',  'Yusuf',   'Danjuma',  'wiifarm-mega',        'ibrahim', 'ibrahim', 'right'],
        ['bisi01',  'Bisi',    'Alade',    'wiifarm-starter',     'ngozi1',  'ngozi1',  'left'],
        ['uche01',  'Uche',    'Okeke',    'wiifarm-booster',     'wiifarm', null,      null],   // no parent chosen: spillover
    ];

    private function members(): void
    {
        // Registration looks up the visitor's location unless the IP signed in before
        DB::table('user_logins')->insert(['user_id' => 0, 'user_ip' => $_SERVER['REMOTE_ADDR'], 'city' => 'Lagos', 'country' => 'Nigeria', 'country_code' => 'NG', 'created_at' => now(), 'updated_at' => now()]);

        $state = DB::table('states')->where('name', 'Lagos')->value('id');

        // Member #1: the company account at the top of the tree. Sign-ups
        // without a referral are given to member #1 (RegisterController).
        $company = new User();
        $company->forceFill([
            'username' => self::COMPANY, 'firstname' => 'Wiifarm', 'lastname' => 'Cooperative', 'email' => 'company@example.com',
            'mobile' => '08000000000', 'dial_code' => '234', 'country_code' => 'NG', 'country_name' => 'Nigeria', 'state' => 'Lagos', 'city' => 'Ikeja',
            'password' => Hash::make($this->memberPassword), 'ref_by' => 0, 'pos_id' => 1, 'section' => 1,
            'project_id' => Project::where('slug', 'wiifarm-mega')->value('id'), 'trx' => getTrx(20),
            'kv' => Status::KYC_VERIFIED, 'ev' => Status::YES, 'sv' => Status::YES, 'ts' => Status::DISABLE, 'tv' => Status::ENABLE,
            'status' => Status::USER_ACTIVE, 'profile_complete' => Status::YES, 'balance' => 0,
        ])->save();
        Matrix::forceCreate(['user_id' => $company->id, 'parent_id' => 0, 'position' => 'root', 'stage_id' => 1, 'is_active' => 1, 'left' => 0, 'right' => 0, 'pv_left' => 0, 'pv_right' => 0, 'pv_left_pairing' => 0, 'pv_right_pairing' => 0]);
        \App\Models\Useridcard::create(['user_id' => $company->id]);
        \App\Models\UserStageProgress::firstOrCreate(['user_id' => $company->id, 'stage_id' => 1]);
        $ids = [self::COMPANY => $company->id];
        foreach (self::MEMBERS as $i => [$username, $first, $last, $package, $sponsor, $parent, $leg]) {
            Carbon::setTestNow(now()->addHours(36));
            $user = new User();
            $user->forceFill([
                'username' => $username, 'firstname' => $first, 'lastname' => $last,
                'email' => "{$username}@example.com", 'mobile' => '0800000' . str_pad((string) ($i + 1), 4, '0', STR_PAD_LEFT),
                'dial_code' => '234', 'country_code' => 'NG', 'country_name' => 'Nigeria', 'state' => 'Lagos', 'city' => 'Ikeja',
                'password' => Hash::make($this->memberPassword), 'ref_by' => $ids[$sponsor], 'pos_id' => 1, 'section' => 1,
                'project_id' => Project::where('slug', $package)->value('id'), 'trx' => getTrx(20),
                'kv' => Status::KYC_VERIFIED, 'ev' => Status::YES, 'sv' => Status::YES, 'ts' => Status::DISABLE, 'tv' => Status::ENABLE,
                'status' => Status::USER_ACTIVE, 'balance' => 0,
            ] + ($parent ? ['parent' => $parent, 'position' => $leg] : []))->save();
            $ids[$username] = $user->id;

            // The package payment, split into the four pools as a real payment is
            $pkg = Project::find($user->project_id);
            app(RevenueAllocationService::class)->allocate((float) $pkg->amount, 'registration', $user->trx, "{$pkg->title} registration by {$username}");

            $rmatrix = Rmatrix::forceCreate(['user_id' => $user->id, 'parent_id' => $parent ? $ids[$parent] : 0, 'position' => $leg ?? 0, 'stage_id' => 1]);
            complete_registration_pin($user->fresh(), $rmatrix, 0, null);
        }

        // A day of matching for everyone with 50 PV on both legs
        Carbon::setTestNow(now()->addDay()->setTime(1, 30));
        $matching = app(MatchingBonusService::class);
        Matrix::where('stage_id', 1)->where('pv_left_pairing', '>=', 50)->where('pv_right_pairing', '>=', 50)->get()->each(fn ($m) => $matching->processMatrix($m));

        // Zainab opens a Basic store; her upline is paid the enrollment bonus
        Carbon::setTestNow(now()->addHours(5));
        $stockist = Stockist::forceCreate([
            'user_id' => $ids['zainab'], 'business_name' => 'Zainab Farm Store', 'state_id' => $state,
            'business_email' => 'zainab.store@example.com', 'business_phone' => '08000000003', 'store_type' => 2,
            'is_verified' => 1, 'is_active' => 1, 'verified_at' => now(), 'status' => Status::ACTIVE, 'wallet' => 0,
        ]);
        app(RevenueAllocationService::class)->allocate(3000000.0, 'stockist', getTrx(), 'Basic Store (₦3M) by zainab');
        app(StockistEnrollmentBonusService::class)->process($stockist->load('user'));

        // A withdrawal waiting for the admin
        $amaka = User::find($ids[self::MAIN_MEMBER]);
        $amount = 20000.0;
        if ((float) $amaka->balance >= $amount) {
            $trx = getTrx();
            DB::table('withdrawals')->insert([
                'user_id' => $amaka->id, 'method_id' => DB::table('withdraw_methods')->value('id'), 'amount' => $amount, 'charge' => 100, 'rate' => 1,
                'final_amount' => $amount - 100, 'after_charge' => $amount - 100, 'currency' => 'NGN', 'trx' => $trx, 'status' => Status::PAYMENT_PENDING,
                'withdraw_information' => json_encode([
                    ['name' => 'Bank Name', 'type' => 'text', 'value' => 'Demo Bank'],
                    ['name' => 'Account Number', 'type' => 'text', 'value' => '0123456789'],
                    ['name' => 'Account Name', 'type' => 'text', 'value' => 'Amaka Okafor'],
                ]),
                'created_at' => now(), 'updated_at' => now(),
            ]);
            $amaka->balance = bcsub((string) $amaka->balance, (string) $amount, 2);
            $amaka->save();
            DB::table('transactions')->insert([
                'user_id' => $amaka->id, 'amount' => $amount, 'charge' => 100, 'post_balance' => $amaka->balance, 'trx_type' => '-',
                'details' => 'Withdraw request via Bank Transfer', 'trx' => $trx, 'remark' => 'withdraw', 'created_at' => now(), 'updated_at' => now(),
            ]);
        }
    }
}
