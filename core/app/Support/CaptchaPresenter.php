<?php

namespace App\Support;

use App\Constants\Status;
use App\Models\Extension;

/**
 * The captchas an admin can switch on (Extensions → Google reCAPTCHA 2 /
 * Custom Captcha), as data for the React <Captcha> component instead of the
 * HTML that App\Lib\Captcha prints. App\Lib\Captcha::verify() checks the
 * answer on submit, unchanged: the page sends the same field names
 * (g-recaptcha-response, captcha, captcha_secret).
 */
class CaptchaPresenter
{
    /**
     * @return array{google: ?string, custom: ?array{digits: array<array{0: string, 1: int}>, secret: string, color: string}}
     */
    public static function props(): array
    {
        return ['google' => self::googleSiteKey(), 'custom' => self::custom()];
    }

    private static function googleSiteKey(): ?string
    {
        $google = Extension::where('act', 'google-recaptcha2')->where('status', Status::ENABLE)->first();

        return $google ? (string) @$google->shortcode->site_key->value : null;
    }

    /** A new 6-digit code each time, drawn with random tilts like the Blade version. */
    private static function custom(): ?array
    {
        $captcha = Extension::where('act', 'custom-captcha')->where('status', Status::ENABLE)->first();
        if (!$captcha) {
            return null;
        }

        $code = (string) random_int(100000, 999999);

        return [
            'digits' => array_map(fn ($digit) => [$digit, random_int(-60, 60)], str_split($code)),
            // The answer, signed with the extension's key; Captcha::verifyCustomCaptcha() recomputes it.
            'secret' => hash_hmac('sha256', $code, $captcha->shortcode->random_key->value),
            'color'  => '#' . gs('base_color'),
        ];
    }
}
