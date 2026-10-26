import { Head } from '@inertiajs/react';
import type { ReactNode } from 'react';

/**
 * The admin page title row: admin/partials/breadcrumb.blade.php. Buttons
 * that Blade pages pushed to the "breadcrumb-plugins" stack go in children.
 * Also sets the browser tab title.
 */
export default function PageHeader({ title, children }: { title: string; children?: ReactNode }) {
    return (
        <>
            <Head title={title} />
            <div className="d-flex mb-30 flex-wrap gap-3 justify-content-between align-items-center">
                <h6 className="page-title">{title}</h6>
                {children && <div className="d-flex flex-wrap justify-content-end gap-2 align-items-center breadcrumb-plugins">{children}</div>}
            </div>
        </>
    );
}
