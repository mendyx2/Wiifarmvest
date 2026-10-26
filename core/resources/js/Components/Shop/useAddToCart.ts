import { router, usePage } from '@inertiajs/react';
import { useState } from 'react';
import { showNotifications } from '@/lib/notify';

/**
 * "Add to cart" on the public shop. ShopCartController@add answers with a
 * redirect back, so Inertia reloads the page (and the header's cart count)
 * and shows the toast; validation errors come back as toasts too.
 * `busy` holds the product id being added, to disable its button.
 */
export function useAddToCart() {
    const url = usePage().props.publicLayout?.links.cartAdd ?? '';
    const [busy, setBusy] = useState<number | null>(null);

    const add = (productId: number, quantity = 1) => {
        if (!url || busy !== null) return;
        router.post(
            url,
            { product_id: productId, quantity },
            {
                preserveScroll: true,
                preserveState: true,
                onStart: () => setBusy(productId),
                onFinish: () => setBusy(null),
                onError: (errors) => showNotifications(Object.values(errors).map((message) => ['error', message])),
            },
        );
    };

    return { add, busy };
}
