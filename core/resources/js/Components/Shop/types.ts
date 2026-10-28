/** A price as App\Support\ShopPresenter::price() sends it: already formatted. */
export type Price = { amount: string; max: string | null; code: string };

/** One product tile (App\Support\ShopPresenter::card). */
export type ProductCardData = {
    id: number;
    name: string;
    sku: string | null;
    image: string;
    url: string;
    inStock: boolean;
    price: Price;
    variesByState: boolean;
};

/** Order status key and label (App\Support\ShopPresenter::orderStatus). */
export type OrderStatus = { key: 'pending' | 'redeemed' | 'cancelled'; label: string };

/** One product tile on the public shop (App\Support\PublicShopPresenter::card). */
export type PublicProduct = {
    id: number;
    name: string;
    sku: string | null;
    category: string;
    image: string;
    url: string;
    price: Price;
    featured: boolean;
    inStock: boolean;
};
