<?php

namespace App\Support;

use App\Http\Controllers\Shop\ShopImageController;
use App\Models\AffiliateOrder;
use App\Models\Product;
use App\Services\ShopCartService;

/**
 * Products, cart lines and orders as the public shop pages (Pages/Shop/*.tsx)
 * show them. The member shop has its own presenter (ShopPresenter): it prices
 * by state and links to the member pages; this one uses the flat shop price
 * (Product::shop_price) and the tokenised image URL that hides file names.
 */
class PublicShopPresenter
{
    public static function image(Product $product): string
    {
        return route('shop.image', ShopImageController::tokenFor($product));
    }

    /** One product tile, as shop/index.blade.php showed it. */
    public static function card(Product $product): array
    {
        return [
            'id'       => $product->id,
            'name'     => $product->name,
            'sku'      => $product->sku,
            'category' => $product->category->name ?? '',
            'image'    => self::image($product),
            'url'      => route('shop.product', $product->id),
            'price'    => ShopPresenter::price($product->shop_price),
            'featured' => (bool) $product->is_featured,
            'inStock'  => (int) $product->quantity > 0,
        ];
    }

    /** @param array{product: Product, quantity: int, unit_price: float, line_total: float} $line */
    public static function cartLine(array $line): array
    {
        $product = $line['product'];

        return [
            'id'        => $product->id,
            'name'      => $product->name,
            'sku'       => $product->sku,
            'image'     => self::image($product),
            'url'       => route('shop.product', $product->id),
            'quantity'  => $line['quantity'],
            // The stock, up to the cart's limit: ShopCartService::resolve() never shows more.
            'max'       => min(ShopCartService::MAX_QUANTITY, max(1, (int) $product->quantity)),
            'unitPrice' => ShopPresenter::price($line['unit_price']),
            'lineTotal' => showAmount($line['line_total']),
        ];
    }

    /**
     * Label and colour, the same as AffiliateOrder::status_badge without the HTML.
     *
     * @return array{label: string, class: string}
     */
    public static function orderStatus(AffiliateOrder $order): array
    {
        return [
            'label' => ucwords(str_replace('_', ' ', $order->status)),
            'class' => match ($order->status) {
                AffiliateOrder::STATUS_PENDING         => 'badge--warning',
                AffiliateOrder::STATUS_PAID            => 'badge--info',
                AffiliateOrder::STATUS_AWAITING_PICKUP => 'badge--primary',
                AffiliateOrder::STATUS_FULFILLED       => 'badge--success',
                AffiliateOrder::STATUS_CANCELLED       => 'badge--danger',
                AffiliateOrder::STATUS_EXPIRED         => 'badge--dark',
                default                                => 'badge--secondary',
            },
        ];
    }
}
