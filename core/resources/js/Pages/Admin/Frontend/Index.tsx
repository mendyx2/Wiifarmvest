import { Link } from '@inertiajs/react';
import { useState } from 'react';
import PageHeader from '@/Components/Admin/PageHeader';
import { useTrans } from '@/lib/i18n';

type Props = {
    sections: { key: string; name: string; url: string }[];
    urls: { pages: string; seo: string };
};

/** Frontend Content: one card per section of the website, with a search. */
export default function FrontendIndex({ sections, urls }: Props) {
    const t = useTrans();
    const [query, setQuery] = useState('');
    const q = query.trim().toLowerCase();
    const shown = q ? sections.filter((s) => s.name.toLowerCase().includes(q)) : sections;

    return (
        <>
            <PageHeader title={t('Manage Frontend Content')}>
                <Link href={urls.pages} className="btn btn-sm btn-outline--primary">
                    <i className="las la-file-alt" /> {t('Manage Pages')}
                </Link>
                <Link href={urls.seo} className="btn btn-sm btn-outline--info">
                    <i className="las la-globe" /> {t('SEO Configuration')}
                </Link>
            </PageHeader>
            <div className="card">
                <div className="card-body">
                    <div className="d-flex mb-3 justify-content-between align-items-center flex-wrap gap-2">
                        <h4 className="mb-0">{t('Content Management Options')}</h4>
                        <div className="position-relative">
                            <i className="las la-search position-absolute top-50 translate-middle-y text-muted" style={{ left: 14 }} aria-hidden />
                            <input type="search" className="form-control" style={{ paddingLeft: 40 }} placeholder={t('Search') + '...'} aria-label={t('Search sections')} value={query} onChange={(e) => setQuery(e.target.value)} />
                        </div>
                    </div>
                    <div className="row gy-3">
                        {shown.map((s) => (
                            <div className="col-xl-3 col-md-4 col-sm-6" key={s.key}>
                                <Link href={s.url} className="d-flex justify-content-between align-items-center border rounded p-3 bg-white text-reset h-100">
                                    <h6 className="mb-0">{s.name}</h6>
                                    <span className="btn btn--light btn-sm" aria-hidden>
                                        <i className="las la-cog me-0" />
                                    </span>
                                </Link>
                            </div>
                        ))}
                        {shown.length === 0 && (
                            <div className="col-12 text-center py-4">
                                <i className="las la-search fs-1 text-muted d-block mb-2" aria-hidden />
                                <h5 className="text-muted">{t('No search result found.')}</h5>
                            </div>
                        )}
                    </div>
                </div>
            </div>
        </>
    );
}
