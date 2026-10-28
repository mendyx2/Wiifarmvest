import { useTrans } from '@/lib/i18n';
import type { Price } from './types';

/** React version of <x-product.price>: "₦4,000 NGN" or "₦4,000–₦4,200 NGN" (showCode false: no "NGN"). */
export default function ProductPrice({ price, className = '', showCode = true }: { price: Price; className?: string; showCode?: boolean }) {
    const t = useTrans();
    return (
        <span className={`pp-price ${className}`}>
            <span className="pp-price__amount">
                {price.amount}
                {price.max && (
                    <>
                        <span className="pp-price__dash">–</span>
                        {price.max}
                    </>
                )}
            </span>
            {showCode && (
                <abbr className="pp-price__code" title={t('Prices in :code', { code: price.code })}>
                    {price.code}
                </abbr>
            )}
        </span>
    );
}
