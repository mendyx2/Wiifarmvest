<?php

namespace App\Http\Middleware;

use App\Models\AffiliateClick;
use App\Support\Crawler;
use App\Models\AffiliateSetting;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;

/**
 * Resolves the affiliate a /shop visitor arrived through and keeps that
 * attribution alive in a cookie for the configured window. A new ?ref= always
 * wins (last-click attribution); an existing cookie otherwise persists silently.
 *
 * SECURITY: this app's web middleware group omits EncryptCookies (see
 * bootstrap/app.php), so cookies are plaintext by default here — a plaintext
 * aff_ref would let anyone hand-craft the cookie in devtools and redirect a
 * real affiliate bonus payout to any user id. The value is therefore
 * self-encrypted with Crypt (same mechanism as ShopImageController's image
 * tokens) rather than relying on that missing app-wide middleware.
 */
class TrackAffiliateClick
{
    const COOKIE_NAME = 'aff_ref';

    public function handle(Request $request, Closure $next)
    {
        $refCode = $request->query('ref');

        // Link-preview bots (Facebook, WhatsApp, …) fetch every shared link:
        // they are not visitors and must not count as clicks
        if ($refCode && !Crawler::is($request)) {
            $affiliate = User::where('affiliate_code', $refCode)->first();

            $isSelfClick = $affiliate && auth()->check() && auth()->id() === $affiliate->id;

            if ($affiliate && !$isSelfClick) {
                $click = AffiliateClick::create([
                    'affiliate_user_id' => $affiliate->id,
                    'session_token'     => Str::random(40),
                    'ip_address'        => $request->ip(),
                    'user_agent'        => (string) $request->userAgent(),
                    'referer'           => (string) $request->headers->get('referer'),
                    'landing_url'       => $request->fullUrl(),
                ]);

                // Lets the page being visited tag the click (e.g. the ad it came from)
                $request->attributes->set('affiliate_click_id', $click->id);

                $days = AffiliateSetting::current()->cookie_days ?: 30;

                $payload = Crypt::encryptString(json_encode([
                    'click_id'          => $click->id,
                    'affiliate_user_id' => $affiliate->id,
                ]));

                Cookie::queue(Cookie::make(
                    self::COOKIE_NAME,
                    $payload,
                    $days * 24 * 60,
                    null, null, null, true, false, 'Lax'
                ));
            }
        }

        return $next($request);
    }

    public static function attribution(Request $request): ?array
    {
        $raw = $request->cookie(self::COOKIE_NAME);

        if (!$raw) {
            return null;
        }

        try {
            $decrypted = Crypt::decryptString($raw);
        } catch (\Throwable $e) {
            // Tampered, forged, or encrypted under a since-rotated app key — never trust it.
            return null;
        }

        $data = json_decode($decrypted, true);

        if (!is_array($data) || empty($data['affiliate_user_id'])) {
            return null;
        }

        return $data;
    }
}
