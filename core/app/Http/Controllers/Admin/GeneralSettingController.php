<?php

namespace App\Http\Controllers\Admin;

use App\Constants\Status;
use App\Http\Controllers\Controller;
use App\Http\Middleware\AdminAccessMiddleware;
use App\Models\Frontend;
use App\Rules\FileTypeValidate;
use App\Support\AdminLayout;
use App\Support\FormPresenter;
use Illuminate\Http\Request;
use Inertia\Inertia;

/**
 * System Setting (Pages/Admin/Settings/*): the settings hub and the pages
 * behind its cards that are plain site settings: general, system
 * configuration, logo & favicon, custom CSS, sitemap, robots.txt, GDPR
 * cookie, maintenance mode and the dashboard notices.
 */
class GeneralSettingController extends Controller
{
    /** Hub: one card per settings page this admin may open (the same rule as the sidebar and search). */
    public function systemSetting()
    {
        $pageTitle = 'System Settings';
        $admin     = auth('admin')->user();
        $cards     = [];

        foreach (json_decode(file_get_contents(resource_path('views/admin/setting/settings.json'))) as $key => $setting) {
            $route = (string) $setting->route_name;
            if (!AdminAccessMiddleware::allows($admin, $route)) {
                continue;
            }
            $cards[] = [
                'key'      => (string) $key,
                'title'    => (string) $setting->title,
                'subtitle' => (string) $setting->subtitle,
                'icon'     => (string) $setting->icon,
                'keywords' => array_values((array) $setting->keyword),
                'url'      => route($route),
                'react'    => AdminLayout::isReact($route),
            ];
        }

        return Inertia::render('Admin/Settings/Hub', ['cards' => $cards])->withViewData(['pageTitle' => $pageTitle]);
    }

    public function general()
    {
        $pageTitle = 'General Setting';
        $gs        = gs();
        $admin     = auth('admin')->user();
        $timezones = timezone_identifiers_list();

        return Inertia::render('Admin/Settings/General', [
            'form' => [
                'site_name'              => (string) $gs->site_name,
                'timezone'               => (string) (array_search(config('app.timezone'), $timezones) ?: 0),
                'cur_text'               => (string) $gs->cur_text,
                'cur_sym'                => (string) $gs->cur_sym,
                'base_color'             => (string) $gs->base_color,
                'secondary_color'        => (string) $gs->secondary_color,
                'paginate_number'        => (string) $gs->paginate_number,
                'currency_format'        => (string) $gs->currency_format,
                'bal_trans_fixed_charge' => (string) getAmount($gs->bal_trans_fixed_charge),
                'bal_trans_per_charge'   => (string) getAmount($gs->bal_trans_per_charge),
            ],
            'timezones' => $timezones,
            // The matching bonus form posts to a member route ("edit users"), so it shows only when allowed
            'matching'  => AdminAccessMiddleware::allows($admin, 'admin.users.matching-bonus.update', 'POST') ? [
                'total_bv'            => (string) getAmount($gs->total_bv),
                'bv_price'            => (string) getAmount($gs->bv_price),
                'max_bv'              => (string) getAmount($gs->max_bv),
                'cary_flash'          => (string) (int) $gs->cary_flash,
                'matching_bonus_time' => in_array($gs->matching_bonus_time, ['daily', 'weekly', 'monthly'], true) ? $gs->matching_bonus_time : 'daily',
                'matching_when'       => (string) $gs->matching_when,
            ] : null,
            'urls' => ['update' => route('admin.setting.general'), 'matching' => route('admin.users.matching-bonus.update')],
        ])->withViewData(['pageTitle' => $pageTitle]);
    }

    public function generalUpdate(Request $request)
    {
        $timezones = timezone_identifiers_list();

        $request->validate([
            'site_name'              => 'required|string|max:40',
            'cur_text'               => 'required|string|max:40',
            'cur_sym'                => 'required|string|max:40',
            'base_color'             => ['nullable', 'regex:/^#?[a-f0-9]{6}$/i'],
            'secondary_color'        => ['nullable', 'regex:/^#?[a-f0-9]{6}$/i'],
            'timezone'               => 'required|integer|min:0|max:' . (count($timezones) - 1),
            'currency_format'        => 'required|in:1,2,3',
            // Every list divides by this: 0 or a huge number broke them
            'paginate_number'        => 'required|integer|in:20,50,100',
            'bal_trans_fixed_charge' => 'required|numeric|gte:0',
            'bal_trans_per_charge'   => 'required|numeric|gte:0|max:100',
        ]);

        $timezone = $timezones[(int) $request->timezone];

        $general                         = gs();
        $general->site_name              = $request->site_name;
        $general->cur_text               = $request->cur_text;
        $general->cur_sym                = $request->cur_sym;
        $general->paginate_number        = $request->paginate_number;
        $general->base_color             = str_replace('#', '', (string) $request->base_color);
        $general->secondary_color        = str_replace('#', '', (string) $request->secondary_color);
        $general->currency_format        = $request->currency_format;
        $general->bal_trans_fixed_charge = $request->bal_trans_fixed_charge;
        $general->bal_trans_per_charge   = $request->bal_trans_per_charge;
        $general->save();

        // The name comes from PHP's own list, so it is safe to write into PHP
        file_put_contents(config_path('timezone.php'), '<?php $timezone = ' . var_export($timezone, true) . ' ?>');

        $notify[] = ['success', 'General setting updated successfully'];
        return back()->withNotify($notify);
    }

    /** The on/off modules, in the order the page lists them. */
    private const MODULES = ['registration', 'force_ssl', 'agree', 'secure_password', 'kv', 'ev', 'en', 'sv', 'sn', 'pn', 'multi_language'];

    public function systemConfiguration()
    {
        $pageTitle = 'System Configuration';
        $gs        = gs();

        return Inertia::render('Admin/Settings/Configuration', [
            'modules' => collect(self::MODULES)->mapWithKeys(fn ($key) => [$key => (bool) $gs->$key]),
            'urls'    => [
                'update'   => route('admin.setting.system.configuration'),
                'policies' => route('admin.frontend.sections', 'policy_pages'),
                'kyc'      => route('admin.kyc.setting'),
                'push'     => route('admin.setting.notification.push'),
            ],
        ])->withViewData(['pageTitle' => $pageTitle]);
    }

    public function systemConfigurationSubmit(Request $request)
    {
        $request->validate(array_fill_keys(self::MODULES, 'required|boolean'));

        $general = gs();
        foreach (self::MODULES as $key) {
            $general->$key = $request->boolean($key) ? Status::ENABLE : Status::DISABLE;
        }
        $general->save();

        $notify[] = ['success', 'System configuration updated successfully'];
        return back()->withNotify($notify);
    }

    public function logoIcon()
    {
        $pageTitle = 'Logo & Favicon';
        $v         = '?' . time();

        return Inertia::render('Admin/Settings/LogoIcon', [
            'images' => ['logo' => siteLogo() . $v, 'logo_dark' => siteLogo('dark') . $v, 'favicon' => siteFavicon() . $v],
            'urls'   => ['update' => route('admin.setting.logo.icon'), 'clearCache' => route('admin.system.info')],
        ])->withViewData(['pageTitle' => $pageTitle]);
    }

    public function logoIconUpdate(Request $request)
    {
        $request->validate([
            'logo'      => ['nullable', 'image', new FileTypeValidate(['jpg', 'jpeg', 'png'])],
            'logo_dark' => ['nullable', 'image', new FileTypeValidate(['jpg', 'jpeg', 'png'])],
            'favicon'   => ['nullable', 'image', new FileTypeValidate(['png'])],
        ]);

        $path = getFilePath('logoIcon');
        foreach (['logo' => 'logo.png', 'logo_dark' => 'logo_dark.png', 'favicon' => 'favicon.png'] as $field => $name) {
            if (!$request->hasFile($field)) {
                continue;
            }
            try {
                fileUploader($request->file($field), $path, filename: $name);
            } catch (\Exception $e) {
                report($e);
                $notify[] = ['error', $field === 'favicon' ? 'Couldn\'t upload the favicon' : 'Couldn\'t upload the logo'];
                return back()->withNotify($notify);
            }
        }

        $notify[] = ['success', 'Logo & favicon updated successfully'];
        return back()->withNotify($notify);
    }

    /* ── Files the site serves: custom CSS, sitemap.xml, robots.txt ── */

    /**
     * Where the public files live: the site root, where index.php and
     * server.php are, one level above core/. The old code wrote to the
     * process's working directory, which is only the site root under the
     * web server (and not under artisan or a queue worker).
     */
    private function publicPath(string $file): string
    {
        return dirname(base_path()) . '/' . $file;
    }

    private function textFilePage(string $component, string $pageTitle, string $file, array $extra = [])
    {
        return Inertia::render($component, [
            'title'   => $pageTitle,
            'content' => (string) @file_get_contents($this->publicPath($file)),
            'url'     => url()->current(),
        ] + $extra)->withViewData(['pageTitle' => $pageTitle]);
    }

    private function saveTextFile(Request $request, string $field, string $file, string $done)
    {
        $request->validate([$field => 'nullable|string|max:500000']);

        if (file_put_contents($this->publicPath($file), (string) $request->input($field)) === false) {
            $notify[] = ['error', 'The file could not be written. Check the folder permissions.'];
            return back()->withNotify($notify);
        }

        $notify[] = ['success', $done];
        return back()->withNotify($notify);
    }

    public function customCss()
    {
        return $this->textFilePage('Admin/Settings/TextFile', 'Custom CSS', activeTemplate(true) . 'css/custom.css', [
            'field' => 'css',
            'mode'  => 'css',
            'note'  => 'From this page, you can add/update CSS for the user interface. Changing content on this page required programming knowledge.',
        ]);
    }

    public function customCssSubmit(Request $request)
    {
        return $this->saveTextFile($request, 'css', activeTemplate(true) . 'css/custom.css', 'CSS updated successfully');
    }

    public function sitemap()
    {
        return $this->textFilePage('Admin/Settings/TextFile', 'Sitemap XML', 'sitemap.xml', [
            'field'  => 'sitemap',
            'mode'   => 'xml',
            'note'   => 'Served at /sitemap.xml.',
            'public' => url('sitemap.xml'),
        ]);
    }

    public function sitemapSubmit(Request $request)
    {
        // Must be well-formed XML, or search engines reject the whole file. A
        // sitemap never needs a DOCTYPE, so entity tricks are refused outright.
        if (stripos((string) $request->sitemap, '<!DOCTYPE') !== false || stripos((string) $request->sitemap, '<!ENTITY') !== false) {
            return back()->withErrors(['sitemap' => 'A sitemap must not contain a DOCTYPE or ENTITY declaration.']);
        }
        if (trim((string) $request->sitemap) !== '') {
            libxml_use_internal_errors(true);
            $ok = simplexml_load_string((string) $request->sitemap, options: LIBXML_NONET) !== false;
            libxml_clear_errors();
            if (!$ok) {
                return back()->withErrors(['sitemap' => 'This is not valid XML.']);
            }
        }

        return $this->saveTextFile($request, 'sitemap', 'sitemap.xml', 'Sitemap updated successfully');
    }

    /**
     * robots.txt. The old page saved to "robots.xml", a name crawlers never
     * ask for, so nothing written here ever reached them. The first visit
     * picks up what was saved in robots.xml.
     */
    public function robot()
    {
        $file = is_file($this->publicPath('robots.txt')) || !is_file($this->publicPath('robots.xml')) ? 'robots.txt' : 'robots.xml';

        return $this->textFilePage('Admin/Settings/TextFile', 'Robots TXT', $file, [
            'field'  => 'robots',
            'mode'   => 'text',
            'note'   => 'Served at /robots.txt.',
            'public' => url('robots.txt'),
        ]);
    }

    public function robotSubmit(Request $request)
    {
        return $this->saveTextFile($request, 'robots', 'robots.txt', 'Robots txt updated successfully');
    }

    /* ── Frontend-stored settings ─────────────────────── */

    public function maintenanceMode()
    {
        $pageTitle   = 'Maintenance Mode';
        $maintenance = Frontend::where('data_keys', 'maintenance.data')->firstOrFail();

        return Inertia::render('Admin/Settings/Maintenance', [
            'form'  => ['status' => (bool) gs('maintenance_mode'), 'description' => (string) @$maintenance->data_values->description],
            'image' => getImage(getFilePath('maintenance') . '/' . @$maintenance->data_values->image, getFileSize('maintenance')),
            'size'  => getFileSize('maintenance'),
            'url'   => route('admin.maintenance.mode'),
        ])->withViewData(['pageTitle' => $pageTitle]);
    }

    public function maintenanceModeSubmit(Request $request)
    {
        $request->validate([
            'status'      => 'required|boolean',
            'description' => 'required|string',
            'image'       => ['nullable', 'image', new FileTypeValidate(['jpg', 'jpeg', 'png'])],
        ]);

        $maintenance = Frontend::where('data_keys', 'maintenance.data')->firstOrFail();
        $image       = @$maintenance->data_values->image;
        if ($request->hasFile('image')) {
            try {
                $image = fileUploader($request->image, getFilePath('maintenance'), getFileSize('maintenance'), $image);
            } catch (\Exception $e) {
                report($e);
                $notify[] = ['error', 'Couldn\'t upload your image'];
                return back()->withNotify($notify);
            }
        }

        // Saved only once the image (if any) is in place
        $general                   = gs();
        $general->maintenance_mode = $request->boolean('status') ? Status::ENABLE : Status::DISABLE;
        $general->save();

        // The maintenance page prints this as HTML: scripts and handlers removed
        $maintenance->data_values = ['description' => FormPresenter::safeHtml($request->description), 'image' => $image];
        $maintenance->save();

        $notify[] = ['success', 'Maintenance mode updated successfully'];
        return back()->withNotify($notify);
    }

    public function cookie()
    {
        $pageTitle = 'GDPR Cookie';
        $cookie    = Frontend::where('data_keys', 'cookie.data')->firstOrFail();

        return Inertia::render('Admin/Settings/Cookie', [
            'form' => [
                'status'      => (bool) @$cookie->data_values->status,
                'short_desc'  => (string) @$cookie->data_values->short_desc,
                'description' => (string) @$cookie->data_values->description,
            ],
            'url' => route('admin.setting.cookie'),
        ])->withViewData(['pageTitle' => $pageTitle]);
    }

    public function cookieSubmit(Request $request)
    {
        $request->validate([
            'status'      => 'required|boolean',
            'short_desc'  => 'required|string|max:255',
            'description' => 'required|string',
        ]);

        $cookie              = Frontend::where('data_keys', 'cookie.data')->firstOrFail();
        $cookie->data_values = [
            'short_desc'  => $request->short_desc,
            'description' => FormPresenter::safeHtml($request->description),
            'status'      => $request->boolean('status') ? Status::ENABLE : Status::DISABLE,
        ];
        $cookie->save();

        $notify[] = ['success', 'Cookie policy updated successfully'];
        return back()->withNotify($notify);
    }

    public function noticeIndex()
    {
        $pageTitle = 'Notice Settings';

        return Inertia::render('Admin/Settings/Notice', [
            'form' => ['notice' => (string) gs('notice'), 'free_user_notice' => (string) gs('free_user_notice')],
            'url'  => route('admin.setting.notice.update'),
        ])->withViewData(['pageTitle' => $pageTitle]);
    }

    public function noticeUpdate(Request $request)
    {
        $request->validate([
            'notice'           => 'nullable|string|max:65000',
            'free_user_notice' => 'nullable|string|max:65000',
        ]);

        $gs                   = gs();
        $gs->notice           = $request->notice;
        $gs->free_user_notice = $request->free_user_notice;
        $gs->save();

        $notify[] = ['success', 'Notice has been updated'];
        return back()->withNotify($notify);
    }
}
