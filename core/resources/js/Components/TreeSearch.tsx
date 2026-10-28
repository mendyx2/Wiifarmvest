import { router } from '@inertiajs/react';
import { useState, type FormEvent } from 'react';
import { useTrans } from '@/lib/i18n';

/** "Search username" box that opens a downline member's tree. */
export default function TreeSearch({ url }: { url: string }) {
    const t = useTrans();
    const [username, setUsername] = useState('');

    function submit(event: FormEvent) {
        event.preventDefault();
        if (username.trim()) router.get(url, { username: username.trim() });
    }

    return (
        <form className="tree-search-form" onSubmit={submit} role="search">
            <div className="tree-search-wrap">
                <i className="las la-search" />
                <input type="text" aria-label={t('Search username...')} placeholder={t('Search username...')} value={username} onChange={(e) => setUsername(e.target.value)} />
            </div>
            <button type="submit" className="tree-search-btn"><i className="las la-search" /> {t('Search')}</button>
        </form>
    );
}
