<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdShare extends Model
{
    public const UPDATED_AT = null;

    /** Channels a member can share an ad through (keys stored in ad_shares.channel). */
    public const CHANNELS = [
        'native'    => 'Share sheet',
        'facebook'  => 'Facebook',
        'whatsapp'  => 'WhatsApp',
        'x'         => 'X (Twitter)',
        'telegram'  => 'Telegram',
        'instagram' => 'Instagram',
        'tiktok'    => 'TikTok',
        'youtube'   => 'YouTube',
        'copy'      => 'Copied link',
        'download'  => 'Downloaded',
    ];

    /** Font Awesome 6 icon for each channel (brand logos where there is one). */
    public const CHANNEL_ICONS = [
        'native'    => 'fa-solid fa-arrow-up-from-bracket',
        'facebook'  => 'fa-brands fa-facebook-f',
        'whatsapp'  => 'fa-brands fa-whatsapp',
        'x'         => 'fa-brands fa-x-twitter',
        'telegram'  => 'fa-brands fa-telegram',
        'instagram' => 'fa-brands fa-instagram',
        'tiktok'    => 'fa-brands fa-tiktok',
        'youtube'   => 'fa-brands fa-youtube',
        'copy'      => 'fa-solid fa-link',
        'download'  => 'fa-solid fa-download',
    ];

    protected $fillable = ['ad_id', 'user_id', 'channel'];

    public function ad()
    {
        return $this->belongsTo(Ad::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
