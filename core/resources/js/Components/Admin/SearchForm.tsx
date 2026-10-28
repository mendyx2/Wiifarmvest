import { useEffect, useState, type FormEvent } from 'react';
import { useTrans } from '@/lib/i18n';
import { visitWithQuery } from '@/lib/search';

/** The small search box in the page title row: <x-search-form />. */
export default function SearchForm({ url, value, placeholder = 'Search...', extra }: { url: string; value: string; placeholder?: string; /** Other query values to keep, e.g. a type filter */ extra?: Record<string, string> }) {
    const t = useTrans();
    const [search, setSearch] = useState(value);
    useEffect(() => setSearch(value), [value]);

    const submit = (e: FormEvent) => {
        e.preventDefault();
        visitWithQuery(url, { ...extra, search: search.trim() });
    };

    return (
        <form className="d-flex flex-wrap gap-2" role="search" onSubmit={submit}>
            <div className="input-group w-auto flex-fill">
                <input type="search" name="search" className="form-control bg--white" placeholder={t(placeholder)} aria-label={t(placeholder)} value={search} onChange={(e) => setSearch(e.target.value)} />
                <button className="btn btn--primary" type="submit" aria-label={t('Search')}>
                    <i className="la la-search" />
                </button>
            </div>
        </form>
    );
}
