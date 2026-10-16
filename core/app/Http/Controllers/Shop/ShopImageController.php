<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Support\Facades\Crypt;
use Symfony\Component\HttpFoundation\Response;

class ShopImageController extends Controller
{
    public static function tokenFor(Product $product): string
    {
        $encrypted = Crypt::encryptString('product:' . $product->id);

        // base64url-encode so the token is safe to drop straight into a URL path segment
        return rtrim(strtr(base64_encode($encrypted), '+/', '-_'), '=');
    }

    public function show(string $token)
    {
        try {
            $encrypted = base64_decode(strtr($token, '-_', '+/'));
            $payload   = Crypt::decryptString($encrypted);
        } catch (\Throwable $e) {
            return $this->placeholder();
        }

        if (!str_starts_with($payload, 'product:')) {
            return $this->placeholder();
        }

        $productId = (int) substr($payload, strlen('product:'));
        $product   = Product::find($productId);

        if (!$product || !$product->thumbnail) {
            return $this->placeholder();
        }

        $path = $this->resolveRealAsset(getFilePath('products') . '/' . $product->thumbnail);

        if (!$path) {
            return $this->placeholder();
        }

        return response()->file($path, [
            'Content-Type'           => mime_content_type($path) ?: 'image/jpeg',
            'Cache-Control'          => 'public, max-age=86400',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    private function placeholder(): Response
    {
        $path = $this->resolveRealAsset('assets/images/default.png');

        if ($path) {
            return response()->file($path, [
                'Content-Type'           => 'image/png',
                'Cache-Control'          => 'public, max-age=86400',
                'X-Content-Type-Options' => 'nosniff',
            ]);
        }

       
        return redirect()->route('placeholder.image', '400x400');
    }

   
    private function resolveRealAsset(string $relativePath): ?string
    {
        $webRoot     = dirname(base_path());
        $allowedBase = realpath($webRoot . '/assets');
        $realPath    = realpath($webRoot . '/' . ltrim($relativePath, '/'));

        if (!$realPath || !$allowedBase || !str_starts_with($realPath, $allowedBase . DIRECTORY_SEPARATOR)) {
            return null;
        }

        return is_file($realPath) ? $realPath : null;
    }
}
