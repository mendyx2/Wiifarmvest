import { Link } from '@inertiajs/react';
import { useState } from 'react';
import PageHeader from '@/Components/Admin/PageHeader';
import { useTrans } from '@/lib/i18n';

type Card = { key: string; title: string; subtitle: string; icon: string; keywords: string[]; url: string; react: boolean };

/** System Setting: one card per settings page, with a search over titles and keywords. */
export default function SettingsHub({ cards }: { cards: Card[] }) {
    const t = useTrans();
    const [query, setQuery] = useState('');
    const q = query.trim().toLowerCase();
    const shown = q ? cards.filter((c) => [c.title, c.subtitle, ...c.keywords].some((s) => s.toLowerCase().includes(q))) : cards;

    return (
        <>
            <PageHeader title={t('System Settings')} />
            <div className="card mb-4">
                <div className="card-body">
                    <div className="position-relative">
                        <i className="las la-search position-absolute top-50 translate-middle-y text-muted" style={{ left: 16 }} aria-hidden />
                        <input type="search" className="form-control" style={{ paddingLeft: 45 }} placeholder={t('Search') + '...'} aria-label={t('Search settings')} value={query} onChange={(e) => setQuery(e.target.value)} autoFocus />
                    </div>
                </div>
            </div>
            <div className="row gy-4">
                {shown.map((c) => (
                    <div className="col-xxl-4 col-md-6" key={c.key}>
                        <div className="widget-two box--shadow2 b-radius--5 has-link bg--white">
                            {c.react ? <Link href={c.url} className="item-link" aria-label={t(c.title)} /> : <a href={c.url} className="item-link" aria-label={t(c.title)} />}
                            <i className={`${c.icon} overlay-icon text--primary`} aria-hidden />
                            <div className="widget-two__icon b-radius--5 bg--primary">
                                <i className={c.icon} aria-hidden />
                            </div>
                            <div className="widget-two__content">
                                <h3>{t(c.title)}</h3>
                                <p>{t(c.subtitle)}</p>
                            </div>
                        </div>
                    </div>
                ))}
                {shown.length === 0 && (
                    <div className="col-12">
                        <div className="card">
                            <div className="card-body text-center py-5">
                                <i className="las la-search fs-1 text-muted d-block mb-2" aria-hidden />
                                <h5 className="text-muted">{t('No search result found.')}</h5>
                            </div>
                        </div>
                    </div>
                )}
            </div>
        </>
    );
}
