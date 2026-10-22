<?php

namespace App\Support;

use App\Constants\Status;
use App\Models\Order;
use App\Models\Product;

/**
 * Turns shop products, cart lines and orders into the small arrays the
 * React shop pages show. Prices are formatted here, exactly like the
 * <x-product.price> Blade component, so a currency change in General
 * Settings shows on the React pages too.
 */
class ShopPresenter
{
    /** "₦4,000" or "₦4,000.50", the same as <x-product.price>. */
    public static function money($value): string
    {
        $value = (float) $value;
        return gs('cur_sym') . number_format($value, fmod($value, 1.0) == 0.0 ? 0 : 2);
    }

    /**
     * A price, or a price range across states, as the <x-product.price> component shows it.
     *
     * @return array{amount: string, max: ?string, code: string}
     */
    public static function price($amount, $max = null): array
    {
        $isRange = $max !== null && (float) $max > (float) $amount;

        return [
            'amount' => self::money($amount),
            'max'    => $isRange ? self::money($max) : null,
            'code'   => (string) gs('cur_text'),
        ];
    }

    public static function image(?string $file): string
    {
        return getImage(getFilePath('products') . '/' . $file, getFileSize('products'));
    }

    /** One product tile for the product list and the "related products" row. */
    public static function card(Product $product): array
    {
        return [
            'id'      => $product->id,
            'name'    => __(shortDescription($product->name, 35)),
            'sku'     => $product->sku,
            'image'   => self::image($product->thumbnail),
            'url'     => route('user.product.details', ['id' => $product->id, 'slug' => slug($product->name)]),
            'inStock' => $product->quantity >= 0,
            'price'   => self::price($product->state_price_min ?? $product->price, $product->state_price_max),
            // The range only exists because prices differ per state.
            'variesByState' => $product->state_price_max > $product->state_price_min,
        ];
    }

    /**
     * Order status. Redeeming at a stockist sets 1 and the admin cancels
     * with 2. The old list page called 3 "Cancelled" and the old detail page
     * called 1 "Paid"; neither matched what the code really stores.
     *
     * @return array{key: string, label: string}
     */
    public static function orderStatus(Order $order): array
    {
        if ((int) $order->status === Status::ORDER_PENDING && !$order->invoice?->redeemed_at) {
            return ['key' => 'pending', 'label' => 'Pending'];
        }

        if ((int) $order->status === Status::ORDER_CANCELED) {
            return ['key' => 'cancelled', 'label' => 'Cancelled'];
        }

        return ['key' => 'redeemed', 'label' => 'Redeemed'];
    }
}
