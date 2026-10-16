<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Support\Facades\Session;

class ShopCartService
{
    const SESSION_KEY = 'shop_cart';

    public function raw(): array
    {
        return Session::get(self::SESSION_KEY, []);
    }

    public function add(int $productId, int $quantity): void
    {
        $quantity = max(1, $quantity);
        $cart     = $this->raw();
        $cart[$productId] = ($cart[$productId] ?? 0) + $quantity;
        Session::put(self::SESSION_KEY, $cart);
    }

    public function update(int $productId, int $quantity): void
    {
        $cart = $this->raw();

        if ($quantity <= 0) {
            unset($cart[$productId]);
        } else {
            $cart[$productId] = $quantity;
        }

        Session::put(self::SESSION_KEY, $cart);
    }

    public function remove(int $productId): void
    {
        $cart = $this->raw();
        unset($cart[$productId]);
        Session::put(self::SESSION_KEY, $cart);
    }

    public function clear(): void
    {
        Session::forget(self::SESSION_KEY);
    }

    public function count(): int
    {
        return array_sum($this->raw());
    }

    /**
     * Resolves the cart against live product data. Pricing on /shop is always
     * the flat selling_price (Product::shop_price) — state is collected at
     * checkout for pickup/stockist-region matching only, not for pricing.
     *
     * @return array{lines: array<int, array{product: Product, quantity: int, unit_price: float, line_total: float}>, subtotal: float}
     */
    public function resolve(?int $stateId = null): array
    {
        $lines    = [];
        $subtotal = 0.0;

        foreach ($this->raw() as $productId => $quantity) {
            $product = Product::active()->find($productId);

            if (!$product || $quantity <= 0) {
                continue;
            }

            $unitPrice = (float) $product->shop_price;
            $quantity  = min($quantity, max(1, (int) $product->quantity));
            $lineTotal = round($unitPrice * $quantity, 2);

            $lines[] = [
                'product'    => $product,
                'quantity'   => $quantity,
                'unit_price' => $unitPrice,
                'line_total' => $lineTotal,
            ];

            $subtotal += $lineTotal;
        }

        return ['lines' => $lines, 'subtotal' => round($subtotal, 2)];
    }
}
