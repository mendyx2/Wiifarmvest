<?php

namespace App\Support;

use Illuminate\Http\Request;

/**
 * Recognises link-preview bots and search crawlers.
 *
 * When a link is pasted into Facebook, WhatsApp, X, Telegram and the rest,
 * their servers fetch it to build the preview card. Those fetches are not
 * people: they must not count as affiliate clicks, and they need to see the
 * page's Open Graph tags rather than be redirected.
 */
class Crawler
{
    // Only fetchers. In-app browsers (Instagram, TikTok, Snapchat, LINE, Facebook's
    // FBAN/FBAV) are real people tapping a link and must NOT match.
    private const PATTERN = '/facebookexternalhit|facebot|meta-externalagent|twitterbot|whatsapp|telegrambot|'
        . 'linkedinbot|slackbot|discordbot|pinterestbot|skypeuripreview|redditbot|applebot|googlebot|'
        . 'bingbot|yandexbot|duckduckbot|baiduspider|embedly|quora link preview|vkshare|w3c_validator|'
        . 'bytespider|crawler|spider/i';

    public static function is(Request $request): bool
    {
        $agent = (string) $request->userAgent();

        return $agent === '' || preg_match(self::PATTERN, $agent) === 1;
    }
}
