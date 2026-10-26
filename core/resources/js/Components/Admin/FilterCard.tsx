import { usePage } from '@inertiajs/react';
import { useState, type FormEvent, type ReactNode } from 'react';
import { useTrans } from '@/lib/i18n';

/**
 * The filter card above admin lists. On phones a "Filter" button above it
 * folds it away and back (admin/js/app.js did that with jQuery slideToggle).
 */
export default function FilterCard({ onSubmit, children }: { onSubmit: () => void; children: ReactNode }) {
    const t = useTrans();
    const [hidden, setHidden] = useState(false);
    // Searchable::dateFilter() answers an unreadable date with errors.error.
    const error = (usePage().props.errors as Record<string, string> | undefined)?.error;

    const submit = (e: FormEvent) => {
        e.preventDefault();
        onSubmit();
    };

    return (
        <>
            <div className="show-filter mb-3 text-end">
                <button className="btn btn-outline--primary btn-sm" type="button" aria-expanded={!hidden} onClick={() => setHidden((h) => !h)}>
                    <i className="las la-filter" /> {t('Filter')}
                </button>
            </div>
            <div className={`card responsive-filter-card mb-4 ${hidden ? 'is-hidden' : ''}`}>
                <div className="card-body">
                    {error && (
                        <div className="alert alert-danger py-2" role="alert">
                            {error}
                        </div>
                    )}
                    <form onSubmit={submit}>
                        <div className="d-flex flex-wrap gap-4">
                            {children}
                            <div className="flex-grow-1 align-self-end">
                                <button className="btn btn--primary w-100 h-45" type="submit">
                                    <i className="fas fa-filter" /> {t('Filter')}
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </>
    );
}
