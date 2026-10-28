import type { ReactNode } from 'react';
import Pagination from '@/Components/Pagination';
import { useTrans } from '@/lib/i18n';
import type { PaginationLink } from '@/types';

export type Column<T> = {
    label: string;
    cell: (row: T) => ReactNode;
    className?: string;
};

type Props<T> = {
    columns: Column<T>[];
    rows: T[];
    rowKey: (row: T) => string | number;
    /** Laravel paginator links: shown in the card footer when there is more than one page */
    links?: PaginationLink[];
    empty?: string;
    /** table-responsive--lg (default), --md or --sm: below that width rows become cards */
    responsive?: 'lg' | 'md' | 'sm';
};

/**
 * The admin list table: a card with "table--light style--two" and the page
 * links in its footer, like the Blade report pages. Each cell carries
 * data-label (its column title): on small screens app.css shows rows as
 * cards with those labels. admin/js/app.js used to add them with jQuery.
 */
export default function DataTable<T>({ columns, rows, rowKey, links, empty, responsive = 'lg' }: Props<T>) {
    const t = useTrans();
    return (
        <div className="card">
            <div className="card-body p-0">
                <div className={`table-responsive--${responsive} table-responsive`}>
                    <table className="table--light style--two table">
                        <thead>
                            <tr>
                                {columns.map((col, i) => (
                                    <th key={i}>{col.label}</th>
                                ))}
                            </tr>
                        </thead>
                        <tbody>
                            {rows.length ? (
                                rows.map((row) => (
                                    <tr key={rowKey(row)}>
                                        {columns.map((col, i) => (
                                            <td key={i} data-label={col.label} className={col.className}>
                                                {col.cell(row)}
                                            </td>
                                        ))}
                                    </tr>
                                ))
                            ) : (
                                <tr>
                                    <td className="text-muted text-center" colSpan={columns.length}>
                                        {empty ?? t('Data not found')}
                                    </td>
                                </tr>
                            )}
                        </tbody>
                    </table>
                </div>
            </div>
            {links && links.length > 3 && (
                <div className="card-footer py-4">
                    <Pagination links={links} className="d-flex justify-content-end" />
                </div>
            )}
        </div>
    );
}
