import { useTrans } from '@/lib/i18n';

/** React version of <x-product.sku>: a small SKU chip, nothing when the product has none. */
export default function Sku({ value, className = '' }: { value: string | null | undefined; className?: string }) {
    const t = useTrans();
    if (!value) return null;
    return (
        <span className={`pp-sku ${className}`} title={t('Stock keeping unit')}>
            <span className="pp-sku__label">SKU</span>
            {value}
        </span>
    );
}
