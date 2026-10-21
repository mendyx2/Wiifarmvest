import { Link } from '@inertiajs/react';
import { useTrans } from '@/lib/i18n';
import type { PaginationLink } from '@/types';

type Props = {
    links: PaginationLink[];
    className?: string;
};

/**
 * Page links for any Laravel paginator, using the same Bootstrap markup as
 * the Blade {{ paginateLinks() }} output. Visits keep the scroll position and
 * the current filters (the controller calls ->withQueryString()).
 */
export default function Pagination({ links, className = '' }: Props) {
    const t = useTrans();

    // Laravel always returns at least "previous", one page and "next".
    if (links.length <= 3) {
        return null;
    }

    const last = links.length - 1;

    return (
        <nav className={className} aria-label={t('Pagination')}>
            <ul className="pagination justify-content-center flex-wrap mb-0">
                {links.map((link, i) => {
                    // The first and last entries are previous/next; their labels can contain
                    // HTML entities depending on the language file, so use icons instead.
                    const label =
                        i === 0 ? <i className="las la-angle-left" aria-label={t('Previous')} />
                        : i === last ? <i className="las la-angle-right" aria-label={t('Next')} />
                        : link.label;

                    return (
                        <li key={i} className={`page-item ${link.active ? 'active' : ''} ${link.url ? '' : 'disabled'}`}>
                            {link.url && !link.active ? (
                                <Link href={link.url} className="page-link" preserveScroll>
                                    {label}
                                </Link>
                            ) : (
                                <span className="page-link" aria-current={link.active ? 'page' : undefined}>
                                    {label}
                                </span>
                            )}
                        </li>
                    );
                })}
            </ul>
        </nav>
    );
}
