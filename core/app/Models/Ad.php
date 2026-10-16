<?php

namespace App\Models;

use App\Services\AdMediaService;
use Illuminate\Database\Eloquent\Model;

/**
 * An admin-uploaded image or video clip that members share on social media.
 * Every share link points at the shop's ad landing route with the member's
 * affiliate code, so visits and orders are credited to them (and to the ad).
 */
class Ad extends Model
{
    public const TYPE_IMAGE = 'image';
    public const TYPE_VIDEO = 'video';

    public const IMAGE_EXTENSIONS = ['jpg', 'jpeg', 'png', 'webp'];
    public const VIDEO_EXTENSIONS = ['mp4', 'webm', 'mov'];

    protected $fillable = ['title', 'caption', 'media_type', 'media', 'poster', 'product_id', 'is_active', 'sort_order'];

    protected $casts = ['is_active' => 'boolean'];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function shares()
    {
        return $this->hasMany(AdShare::class);
    }

    public function clicks()
    {
        return $this->hasMany(AffiliateClick::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->latest('id');
    }

    public function isVideo(): bool
    {
        return $this->media_type === self::TYPE_VIDEO;
    }

    public function mediaUrl(): string
    {
        return $this->fileUrl($this->media);
    }

    /** The picture behind the ad: the image itself, or a video's cover. */
    public function pictureSource(): ?string
    {
        return $this->isVideo() ? $this->poster : $this->media;
    }

    public function posterUrl(): ?string
    {
        return $this->poster ? $this->fileUrl($this->poster) : null;
    }

    /** Square thumbnail for lists; the original picture until the thumbnail exists. */
    public function thumbUrl(): ?string
    {
        $source = $this->pictureSource();
        if (!$source) {
            return null;
        }

        $thumb = AdMediaService::thumbName($source);

        return $this->fileUrl(is_file(AdMediaService::path($thumb)) ? $thumb : $source);
    }

    /** The picture social networks show in link previews (1200×630 once built). */
    public function previewImageUrl(): string
    {
        $file = $this->previewImageFile();

        return $file ? $this->fileUrl($file) : siteLogo();
    }

    /**
     * Width and height of the preview picture. Facebook only shows the picture on
     * the first share of a link when og:image:width/height are given.
     */
    public function previewImageSize(): ?array
    {
        $file = $this->previewImageFile();
        $size = $file ? @getimagesize(AdMediaService::path($file)) : false;

        return $size ? ['width' => $size[0], 'height' => $size[1], 'mime' => $size['mime']] : null;
    }

    private function previewImageFile(): ?string
    {
        $source = $this->pictureSource();
        if (!$source) {
            return null;
        }

        $share = AdMediaService::shareName($source);
        if (is_file(AdMediaService::path($share))) {
            return $share;
        }

        return is_file(AdMediaService::path($source)) ? $source : null;
    }

    private function fileUrl(string $filename): string
    {
        return asset(getFilePath('ads') . '/' . $filename);
    }

    /** The member's personal share link for this ad. */
    public function shareUrlFor(User $user): string
    {
        return route('shop.ad', ['ad' => $this->id, 'ref' => $user->getOrCreateAffiliateCode()]);
    }

    /** Where a visitor lands after the share link: the product, or the shop. */
    public function destinationUrl(): string
    {
        return $this->product_id && $this->product
            ? route('shop.product', $this->product_id)
            : route('shop.index');
    }
}
