import { useTrans } from '@/lib/i18n';

/** Page title with the Cart button: the React version of layouts/breadcrumb.blade.php. */
export default function PageTitleBar({ title, cartUrl, cartCount }: { title: string; cartUrl: string; cartCount: number }) {
    const t = useTrans();
    return (
        <div className="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
            <h3 className="h3" style={{ color: '#135D26' }}>{title}</h3>
            <div className="btn-toolbar mb-2 mb-md-0">
                <div className="btn-group me-2">
                    {/* The cart page is not React yet, so a normal link. */}
                    <a href={cartUrl} className="btn btn-sm btn-outline-success">
                        {t('Cart')} <span className="badge bg-success rounded-pill">{cartCount}</span>
                    </a>
                </div>
            </div>
        </div>
    );
}
