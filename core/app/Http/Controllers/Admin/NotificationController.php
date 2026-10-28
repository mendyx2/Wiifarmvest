<?php

namespace App\Http\Controllers\Admin;

use App\Constants\Status;
use App\Http\Controllers\Controller;
use App\Models\NotificationTemplate;
use App\Rules\FileTypeValidate;
use Illuminate\Http\Request;
use Inertia\Inertia;

/**
 * Notification Setting (Pages/Admin/Notification/*): the global email / SMS
 * / push templates, the email, SMS and push (Firebase) settings, and each
 * notification's own templates.
 *
 * Passwords and API secrets are never sent to the browser. The forms show
 * whether one is stored; leaving the field blank keeps it.
 */
class NotificationController extends Controller
{
    private const CHANNELS = ['email', 'sms', 'push'];

    /** Secret fields of the email methods, by form field => stored key. */
    private const MAIL_SECRETS = ['password' => 'password', 'appkey' => 'appkey', 'secret_key' => 'secret_key'];

    /** Secret fields of the SMS gateways: form field => [gateway, stored key]. */
    private const SMS_SECRETS = [
        'clickatell_api_key'     => ['clickatell', 'api_key'],
        'message_bird_api_key'   => ['message_bird', 'api_key'],
        'nexmo_api_secret'       => ['nexmo', 'api_secret'],
        'infobip_password'       => ['infobip', 'password'],
        'sms_broadcast_password' => ['sms_broadcast', 'password'],
        'auth_token'             => ['twilio', 'auth_token'],
        'apiv2_key'              => ['text_magic', 'apiv2_key'],
    ];

    /* ── Global templates ─────────────────────────────── */

    public function globalEmail()
    {
        return $this->globalPage('email', 'Global Email Template', ['email_from_name' => (string) gs('email_from_name'), 'email_from' => (string) gs('email_from'), 'email_template' => (string) gs('email_template')]);
    }

    public function globalSms()
    {
        return $this->globalPage('sms', 'Global Sms Template', ['sms_from' => (string) gs('sms_from'), 'sms_template' => (string) gs('sms_template')]);
    }

    public function globalPush()
    {
        return $this->globalPage('push', 'Global Push Notification Template', ['push_title' => (string) gs('push_title'), 'push_template' => (string) gs('push_template')]);
    }

    private function globalPage(string $channel, string $pageTitle, array $form)
    {
        return Inertia::render('Admin/Notification/Global', [
            'channel'    => $channel,
            'title'      => $pageTitle,
            'form'       => $form,
            'shortcodes' => ['fullname' => 'Full Name of User', 'username' => 'Username of User', 'message' => 'Message'] + $this->globalShortcodes(),
            'urls'       => $this->tabUrls() + ['update' => route("admin.setting.notification.global.$channel.update")],
        ])->withViewData(['pageTitle' => $pageTitle]);
    }

    public function globalEmailUpdate(Request $request)
    {
        $request->validate([
            'email_from'      => 'required|email|string|max:40',
            'email_from_name' => 'required|string|max:255',
            'email_template'  => 'required|string',
        ]);

        $general                  = gs();
        $general->email_from      = $request->email_from;
        $general->email_from_name = $request->email_from_name;
        $general->email_template  = $request->email_template;
        $general->save();

        $notify[] = ['success', 'Global email template updated successfully'];
        return back()->withNotify($notify);
    }

    public function globalSmsUpdate(Request $request)
    {
        $request->validate([
            'sms_from'     => 'required|string|max:40',
            'sms_template' => 'required|string',
        ]);

        $general               = gs();
        $general->sms_from     = $request->sms_from;
        $general->sms_template = $request->sms_template;
        $general->save();

        $notify[] = ['success', 'Global sms template updated successfully'];
        return back()->withNotify($notify);
    }

    public function globalPushUpdate(Request $request)
    {
        $request->validate([
            'push_template' => 'required|string',
            'push_title'    => 'required|string|max:255',
        ]);

        $general                = gs();
        $general->push_template = $request->push_template;
        $general->push_title    = $request->push_title;
        $general->save();

        $notify[] = ['success', 'Global push notification template updated successfully'];
        return back()->withNotify($notify);
    }

    /* ── Per-notification templates ───────────────────── */

    public function templates()
    {
        $pageTitle = 'Notification Templates';

        return Inertia::render('Admin/Notification/Templates', [
            'templates' => NotificationTemplate::orderBy('name')->get()->map(fn (NotificationTemplate $t) => [
                'id'      => $t->id,
                'name'    => (string) __($t->name),
                'subject' => (string) __($t->subject),
                'email'   => (int) $t->email_status === Status::ENABLE,
                'sms'     => (int) $t->sms_status === Status::ENABLE,
                'push'    => (int) $t->push_status === Status::ENABLE,
            ]),
            'urls' => $this->tabUrls() + ['edit' => route('admin.setting.notification.template.edit', [':type', ':id'])],
        ])->withViewData(['pageTitle' => $pageTitle]);
    }

    public function templateEdit($type, $id)
    {
        // The type picked a Blade view by name; only the three channels exist
        abort_unless(in_array($type, self::CHANNELS, true), 404);
        $template = NotificationTemplate::findOrFail($id);

        return Inertia::render('Admin/Notification/TemplateEdit', [
            'channel'  => $type,
            'template' => [
                'id'                      => $template->id,
                'name'                    => (string) __($template->name),
                'subject'                 => (string) $template->subject,
                'email_body'              => (string) $template->email_body,
                'email_sent_from_name'    => (string) $template->email_sent_from_name,
                'email_sent_from_address' => (string) $template->email_sent_from_address,
                'email_status'            => (bool) $template->email_status,
                'sms_body'                => (string) $template->sms_body,
                'sms_sent_from'           => (string) $template->sms_sent_from,
                'sms_status'              => (bool) $template->sms_status,
                'push_title'              => (string) $template->push_title,
                'push_body'               => (string) $template->push_body,
                'push_status'             => (bool) $template->push_status,
            ],
            'shortcodes' => collect((array) ($template->shortcodes ?? []))->map(fn ($v) => (string) $v)->all() + $this->globalShortcodes(),
            'urls'       => $this->tabUrls() + [
                'update' => route('admin.setting.notification.template.update', [$type, $template->id]),
                'tab'    => route('admin.setting.notification.template.edit', [':type', $template->id]),
            ],
        ])->withViewData(['pageTitle' => $template->name]);
    }

    public function templateUpdate(Request $request, $type, $id)
    {
        abort_unless(in_array($type, self::CHANNELS, true), 404);

        $request->validate(match ($type) {
            'email' => [
                'subject'                 => 'required|string|max:255',
                'email_body'              => 'required|string',
                'email_sent_from_name'    => 'nullable|string|max:255',
                'email_sent_from_address' => 'nullable|email|max:255',
                'email_status'            => 'required|boolean',
            ],
            'sms' => ['sms_body' => 'required|string', 'sms_sent_from' => 'nullable|string|max:40', 'sms_status' => 'required|boolean'],
            'push' => ['push_body' => 'required|string', 'push_title' => 'nullable|string|max:255', 'push_status' => 'required|boolean'],
        });

        $template = NotificationTemplate::findOrFail($id);
        if ($type == 'email') {
            $template->subject                 = $request->subject;
            $template->email_body              = $request->email_body;
            $template->email_sent_from_name    = $request->email_sent_from_name;
            $template->email_sent_from_address = $request->email_sent_from_address;
            $template->email_status            = $request->boolean('email_status') ? Status::ENABLE : Status::DISABLE;
        }
        if ($type == 'sms') {
            $template->sms_body      = $request->sms_body;
            $template->sms_sent_from = $request->sms_sent_from;
            $template->sms_status    = $request->boolean('sms_status') ? Status::ENABLE : Status::DISABLE;
        }
        if ($type == 'push') {
            $template->push_title  = $request->push_title;
            $template->push_body   = $request->push_body;
            $template->push_status = $request->boolean('push_status') ? Status::ENABLE : Status::DISABLE;
        }
        $template->save();

        $notify[] = ['success', 'Notification template updated successfully'];
        return back()->withNotify($notify);
    }

    /* ── Email ────────────────────────────────────────── */

    public function emailSetting()
    {
        $pageTitle = 'Email Notification Settings';
        $config    = gs('mail_config');

        return Inertia::render('Admin/Notification/EmailSetting', [
            'form' => [
                'email_method' => (string) ($config->name ?? 'php'),
                'host'         => (string) ($config->host ?? ''),
                'port'         => (string) ($config->port ?? ''),
                'enc'          => (string) ($config->enc ?? 'ssl'),
                'username'     => (string) ($config->username ?? ''),
                'password'     => '',
                'appkey'       => '',
                'public_key'   => (string) ($config->public_key ?? ''),
                'secret_key'   => '',
            ],
            'stored' => collect(self::MAIL_SECRETS)->map(fn ($key) => !empty($config->$key))->all(),
            'urls'   => $this->tabUrls() + ['update' => route('admin.setting.notification.email'), 'test' => route('admin.setting.notification.email.test')],
        ])->withViewData(['pageTitle' => $pageTitle]);
    }

    public function emailSettingUpdate(Request $request)
    {
        // A blank secret keeps the stored one
        $stored = gs('mail_config');
        foreach (self::MAIL_SECRETS as $field => $key) {
            if (blank($request->input($field)) && !empty($stored->$key)) {
                $request->merge([$field => $stored->$key]);
            }
        }

        $request->validate([
            'email_method' => 'required|in:php,smtp,sendgrid,mailjet',
            'host'         => 'required_if:email_method,smtp|nullable|string|max:255',
            'port'         => 'required_if:email_method,smtp|nullable|integer|between:1,65535',
            'username'     => 'required_if:email_method,smtp|nullable|string|max:255',
            'password'     => 'required_if:email_method,smtp|nullable|string|max:255',
            'enc'          => 'required_if:email_method,smtp|nullable|in:ssl,tls',
            'appkey'       => 'required_if:email_method,sendgrid|nullable|string|max:255',
            'public_key'   => 'required_if:email_method,mailjet|nullable|string|max:255',
            'secret_key'   => 'required_if:email_method,mailjet|nullable|string|max:255',
        ], [
            'host.required_if'       => 'The :attribute is required for SMTP configuration',
            'port.required_if'       => 'The :attribute is required for SMTP configuration',
            'username.required_if'   => 'The :attribute is required for SMTP configuration',
            'password.required_if'   => 'The :attribute is required for SMTP configuration',
            'enc.required_if'        => 'The :attribute is required for SMTP configuration',
            'appkey.required_if'     => 'The :attribute is required for SendGrid configuration',
            'public_key.required_if' => 'The :attribute is required for Mailjet configuration',
            'secret_key.required_if' => 'The :attribute is required for Mailjet configuration',
        ]);

        $data = match ($request->email_method) {
            'php'      => ['name' => 'php'],
            'smtp'     => ['name' => 'smtp'] + $request->only('host', 'port', 'enc', 'username', 'password'),
            'sendgrid' => ['name' => 'sendgrid', 'appkey' => $request->appkey],
            'mailjet'  => ['name' => 'mailjet', 'public_key' => $request->public_key, 'secret_key' => $request->secret_key],
        };

        $general              = gs();
        $general->mail_config = $data;
        $general->save();

        $notify[] = ['success', 'Email settings updated successfully'];
        return back()->withNotify($notify);
    }

    public function emailTest(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        if (!gs('en')) {
            $notify[] = ['info', 'Please enable from general settings'];
            $notify[] = ['error', 'Your email notification is disabled'];
            return back()->withNotify($notify);
        }

        $config = gs('mail_config');
        notify([
            'username' => $request->email,
            'email'    => $request->email,
            'fullname' => explode('@', $request->email)[0],
        ], 'DEFAULT', [
            'subject' => strtoupper((string) ($config->name ?? 'php')) . ' Configuration Success',
            'message' => 'Your email notification setting is configured successfully for ' . gs('site_name'),
        ], ['email'], false);

        $notify[] = session('mail_error') ? ['error', session('mail_error')] : ['success', 'Email sent to ' . $request->email . ' successfully'];
        return back()->withNotify($notify);
    }

    /* ── SMS ──────────────────────────────────────────── */

    public function smsSetting()
    {
        $pageTitle = 'SMS Notification Settings';
        $c         = gs('sms_config');
        $pairs     = fn ($group) => collect((array) ($group->name ?? []))->map(fn ($name, $i) => ['name' => (string) $name, 'value' => (string) (((array) ($group->value ?? []))[$i] ?? '')])->values()->all();

        return Inertia::render('Admin/Notification/SmsSetting', [
            'form' => [
                'sms_method'             => (string) ($c->name ?? 'clickatell'),
                'clickatell_api_key'     => '',
                'message_bird_api_key'   => '',
                'nexmo_api_key'          => (string) ($c->nexmo->api_key ?? ''),
                'nexmo_api_secret'       => '',
                'infobip_username'       => (string) ($c->infobip->username ?? ''),
                'infobip_password'       => '',
                'sms_broadcast_username' => (string) ($c->sms_broadcast->username ?? ''),
                'sms_broadcast_password' => '',
                'account_sid'            => (string) ($c->twilio->account_sid ?? ''),
                'auth_token'             => '',
                'from'                   => (string) ($c->twilio->from ?? ''),
                'text_magic_username'    => (string) ($c->text_magic->username ?? ''),
                'apiv2_key'              => '',
                'custom_api_method'      => (string) ($c->custom->method ?? 'get'),
                'custom_api_url'         => (string) ($c->custom->url ?? ''),
                'headers'                => $pairs($c->custom->headers ?? null),
                'body'                   => $pairs($c->custom->body ?? null),
            ],
            'stored' => collect(self::SMS_SECRETS)->map(fn ($at) => !empty($c->{$at[0]}->{$at[1]} ?? null))->all(),
            'urls'   => $this->tabUrls() + ['update' => route('admin.setting.notification.sms'), 'test' => route('admin.setting.notification.sms.test')],
        ])->withViewData(['pageTitle' => $pageTitle]);
    }

    public function smsSettingUpdate(Request $request)
    {
        // A blank secret keeps the stored one (for every gateway, so switching
        // gateways does not wipe the others' keys)
        $stored = gs('sms_config');
        foreach (self::SMS_SECRETS as $field => [$gateway, $key]) {
            if (blank($request->input($field)) && !empty($stored->$gateway->$key ?? null)) {
                $request->merge([$field => $stored->$gateway->$key]);
            }
        }

        $request->validate([
            'sms_method'             => 'required|in:clickatell,infobip,messageBird,nexmo,smsBroadcast,twilio,textMagic,custom',
            'clickatell_api_key'     => 'required_if:sms_method,clickatell',
            'message_bird_api_key'   => 'required_if:sms_method,messageBird',
            'nexmo_api_key'          => 'required_if:sms_method,nexmo',
            'nexmo_api_secret'       => 'required_if:sms_method,nexmo',
            'infobip_username'       => 'required_if:sms_method,infobip',
            'infobip_password'       => 'required_if:sms_method,infobip',
            'sms_broadcast_username' => 'required_if:sms_method,smsBroadcast',
            'sms_broadcast_password' => 'required_if:sms_method,smsBroadcast',
            'text_magic_username'    => 'required_if:sms_method,textMagic',
            'apiv2_key'              => 'required_if:sms_method,textMagic',
            'account_sid'            => 'required_if:sms_method,twilio',
            'auth_token'             => 'required_if:sms_method,twilio',
            'from'                   => 'required_if:sms_method,twilio',
            'custom_api_method'      => 'required_if:sms_method,custom|nullable|in:get,post',
            'custom_api_url'         => 'required_if:sms_method,custom|nullable|url:http,https|max:500',
            'headers'                => 'nullable|array|max:20',
            'headers.*.name'         => 'nullable|string|max:255',
            'headers.*.value'        => 'nullable|string|max:1000',
            'body'                   => 'nullable|array|max:30',
            'body.*.name'            => 'nullable|string|max:255',
            'body.*.value'           => 'nullable|string|max:1000',
        ]);

        $pairs = function (?array $rows) {
            $rows = array_values(array_filter($rows ?? [], fn ($r) => filled($r['name'] ?? null)));
            return ['name' => array_column($rows, 'name'), 'value' => array_map(fn ($r) => (string) ($r['value'] ?? ''), $rows)];
        };

        $general             = gs();
        $general->sms_config = [
            'name'          => $request->sms_method,
            'clickatell'    => ['api_key' => $request->clickatell_api_key],
            'infobip'       => ['username' => $request->infobip_username, 'password' => $request->infobip_password],
            'message_bird'  => ['api_key' => $request->message_bird_api_key],
            'nexmo'         => ['api_key' => $request->nexmo_api_key, 'api_secret' => $request->nexmo_api_secret],
            'sms_broadcast' => ['username' => $request->sms_broadcast_username, 'password' => $request->sms_broadcast_password],
            'twilio'        => ['account_sid' => $request->account_sid, 'auth_token' => $request->auth_token, 'from' => $request->from],
            'text_magic'    => ['username' => $request->text_magic_username, 'apiv2_key' => $request->apiv2_key],
            'custom'        => [
                'method'  => $request->custom_api_method,
                'url'     => $request->custom_api_url,
                'headers' => $pairs($request->input('headers')),
                'body'    => $pairs($request->input('body')),
            ],
        ];
        $general->save();

        $notify[] = ['success', 'Sms settings updated successfully'];
        return back()->withNotify($notify);
    }

    public function smsTest(Request $request)
    {
        $request->validate(['mobile' => 'required|string|max:20']);

        if (!gs('sn')) {
            $notify[] = ['info', 'Please enable from general settings'];
            $notify[] = ['error', 'Your sms notification is disabled'];
            return back()->withNotify($notify);
        }

        notify(['username' => $request->mobile, 'mobileNumber' => $request->mobile, 'fullname' => ''], 'DEFAULT', [
            'subject' => '',
            'message' => 'Your sms notification setting is configured successfully for ' . gs('site_name'),
        ], ['sms'], false);

        $notify[] = session('sms_error') ? ['error', session('sms_error')] : ['success', 'SMS sent to ' . $request->mobile . ' successfully'];
        return back()->withNotify($notify);
    }

    /* ── Push (Firebase) ──────────────────────────────── */

    private const FIREBASE = ['apiKey', 'authDomain', 'projectId', 'storageBucket', 'messagingSenderId', 'appId', 'measurementId'];

    public function pushSetting()
    {
        $pageTitle = 'Push Notification Settings';
        $config    = gs('firebase_config');

        return Inertia::render('Admin/Notification/PushSetting', [
            // The web config is public by design (it is written into a browser script)
            'form'       => collect(self::FIREBASE)->mapWithKeys(fn ($k) => [$k => (string) ($config->$k ?? '')])->all(),
            'fileExists' => file_exists(getFilePath('pushConfig') . '/push_config.json'),
            'helpImage'  => getImage('assets/images/firebase/configs.png'),
            'urls'       => $this->tabUrls() + [
                'update'   => route('admin.setting.notification.push'),
                'upload'   => route('admin.setting.notification.push.upload'),
                'download' => route('admin.setting.notification.push.download'),
            ],
        ])->withViewData(['pageTitle' => $pageTitle]);
    }

    public function pushSettingUpdate(Request $request)
    {
        $request->validate(array_fill_keys(self::FIREBASE, 'required|string|max:255'));

        $general                  = gs();
        $general->firebase_config = $request->only(self::FIREBASE);
        $general->save();

        // The browser script that registers for push; in the site root, not the working directory
        $script = dirname(base_path()) . '/assets/global/js/firebase/configs.js';
        $json   = json_encode($request->only(self::FIREBASE), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_SLASHES);
        if (@file_put_contents($script, 'var firebaseConfig = ' . $json) === false) {
            $notify[] = ['error', 'Settings saved, but the Firebase script could not be written. Check the folder permissions.'];
            return back()->withNotify($notify);
        }

        $notify[] = ['success', 'Firebase settings updated successfully'];
        return back()->withNotify($notify);
    }

    public function pushSettingUpload(Request $request)
    {
        $request->validate(['file' => ['required', 'file', 'max:100', new FileTypeValidate(['json'])]]);

        // A service-account key: it must at least be JSON of that shape
        $json = json_decode((string) file_get_contents($request->file('file')->getRealPath()), true);
        if (!is_array($json) || empty($json['private_key']) || empty($json['client_email'])) {
            return back()->withErrors(['file' => 'This is not a Firebase service-account key (JSON with private_key and client_email).']);
        }

        try {
            fileUploader($request->file, getFilePath('pushConfig'), filename: 'push_config.json');
        } catch (\Exception $e) {
            report($e);
            $notify[] = ['error', 'Couldn\'t upload your file'];
            return back()->withNotify($notify);
        }

        $notify[] = ['success', 'Configuration file uploaded successfully'];
        return back()->withNotify($notify);
    }

    public function pushSettingDownload()
    {
        $filePath = getFilePath('pushConfig') . '/push_config.json';
        if (!file_exists($filePath)) {
            $notify[] = ['error', 'File not found'];
            return back()->withNotify($notify);
        }

        return response()->download($filePath);
    }

    /* ── Shared ───────────────────────────────────────── */

    private function globalShortcodes(): array
    {
        return collect((array) (gs('global_shortcodes') ?? []))->map(fn ($v) => (string) $v)->all();
    }

    /** The tab bar shared by every notification page. */
    private function tabUrls(): array
    {
        return [
            'tabs' => [
                'global'    => route('admin.setting.notification.global.email'),
                'email'     => route('admin.setting.notification.email'),
                'sms'       => route('admin.setting.notification.sms'),
                'push'      => route('admin.setting.notification.push'),
                'templates' => route('admin.setting.notification.templates'),
            ],
            'global' => [
                'email' => route('admin.setting.notification.global.email'),
                'sms'   => route('admin.setting.notification.global.sms'),
                'push'  => route('admin.setting.notification.global.push'),
            ],
        ];
    }
}
