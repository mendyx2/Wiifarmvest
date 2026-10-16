<?php

namespace App\Models;

use App\Constants\Status;
use App\Traits\GlobalStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;

class Product extends Model
{
    use GlobalStatus;

    protected $casts = [
        'specifications'    => 'array',
        'meta_keyword'      => 'array',
        'carton_size'       => 'integer',
        'price_per_carton'  => 'decimal:2',
    ];

    /** Blueprint: Standard carton = 15 packs */
    public const DEFAULT_CARTON_SIZE = 15;


    public function category()
    {
        return $this->belongsTo(Category::class);
    }
    public function productstate()
    {
        return $this->hasMany(ProductStatePrice::class);
    }

    public function images()
    {
        return $this->hasMany(ProductImage::class, 'product_id');
    }

    public function statusFeature(): Attribute
    {
        return new Attribute(function () {
            $html = '';
            if ($this->is_featured == Status::ENABLE) {
                $html = '<span class="badge badge--success">' . trans('Featured') . '</span>';
            } else {
                $html = '<span class="badge badge--warning">' . trans('UnFeatured') . '</span>';
            }
            return $html;
        });
    }

    public function scopeHasCategory($q)
    {
        return $q->whereHas('category', function ($q) {
            $q->active();
        });
    }

    public function getFormattedPriceAttribute()
    {
        return '₦' . number_format($this->price, 2);
    }

    /**
     * SKUs for a set of product ids in one query, for pages that list products
     * from stored snapshots (carts in the session, redemption JSON).
     *
     * @return array<int, int|string> product_id => sku
     */
    public static function skuMap(iterable $ids): array
    {
        $ids = collect($ids)->filter()->map(fn ($id) => (int) $id)->unique()->values();

        return $ids->isEmpty() ? [] : static::whereIn('id', $ids)->pluck('sku', 'id')->all();
    }

    /** The price shown and charged on the public /shop storefront. */
    public function getShopPriceAttribute()
    {
        return $this->selling_price ?: $this->price;
    }


     public function inventory()
    {
        return $this->hasMany(StockistInventory::class, 'product_id');
    }

    public function orderItems()
    {
        return $this->hasMany(StockistOrderItem::class, 'product_id');
    }

    public function getIsAvailableAttribute()
    {
        return $this->status && $this->quantity > 0;
    }

    public function statePrices()
    {
        return $this->hasMany(ProductStatePrice::class);
    }
    
    public function getPriceForState($stateId)
    {
        $statePrice = $this->statePrices()->where('state_id', $stateId)->first();
        return $statePrice ? $statePrice->price : $this->price; // fallback to default price
    }

    public function affiliateOrderItems()
    {
        return $this->hasMany(AffiliateOrderItem::class);
    }

    public function calculateAffiliateBonus(float $unitPrice, int $quantity): float
    {
        if (empty($this->affiliate_bonus_type) || $this->affiliate_bonus_value === null || $this->affiliate_bonus_value <= 0) {
            return 0.0;
        }

        if ($this->affiliate_bonus_type === 'percentage') {
            return round(($unitPrice * $quantity) * ($this->affiliate_bonus_value / 100), 2);
        }

        return round($this->affiliate_bonus_value * $quantity, 2);
    }

    // ── Wholesale Carton Packaging ────────────────────────────────────────

    /**
     * Number of individual packs per carton for this product.
     * Falls back to Blueprint default of 15 if not explicitly set.
     */
    public function getPacksPerCartonAttribute(): int
    {
        return (int) ($this->carton_size ?: self::DEFAULT_CARTON_SIZE);
    }

    /**
     * Price per carton. If not set, calculates from unit price × packs per carton.
     */
    public function getCartonPriceAttribute(): float
    {
        if ($this->price_per_carton > 0) {
            return (float) $this->price_per_carton;
        }

        return round((float) $this->price * $this->packs_per_carton, 2);
    }

    /**
     * Convert carton count to individual unit count.
     */
    public function cartonsToUnits(int $cartons): int
    {
        return $cartons * $this->packs_per_carton;
    }

    /**
     * PV value for a given number of cartons.
     */
    public function cartonPv(int $cartons): float
    {
        return round((float) ($this->pv ?? 0) * $this->cartonsToUnits($cartons), 2);
    }
}
