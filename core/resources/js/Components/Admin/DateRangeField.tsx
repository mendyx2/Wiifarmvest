import { useTrans } from '@/lib/i18n';

export type DateRange = { from: string; to: string };

/** The value the server's dateFilter() reads: "2026-10-01 - 2026-10-26", one day, or nothing. */
export function dateQuery({ from, to }: DateRange): string {
    if (from && to) return from === to ? from : `${from} - ${to}`;
    return from || to;
}

const iso = (d: Date) => `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;

/** The quick ranges the jQuery date-range picker offered. */
function presets(): { label: string; range: DateRange }[] {
    const today = new Date();
    const day = (offset: number) => {
        const d = new Date(today);
        d.setDate(d.getDate() + offset);
        return iso(d);
    };
    const y = today.getFullYear();
    const m = today.getMonth();
    return [
        { label: 'Today', range: { from: day(0), to: day(0) } },
        { label: 'Yesterday', range: { from: day(-1), to: day(-1) } },
        { label: 'Last 7 Days', range: { from: day(-6), to: day(0) } },
        { label: 'Last 15 Days', range: { from: day(-14), to: day(0) } },
        { label: 'Last 30 Days', range: { from: day(-30), to: day(0) } },
        { label: 'This Month', range: { from: iso(new Date(y, m, 1)), to: iso(new Date(y, m + 1, 0)) } },
        { label: 'Last Month', range: { from: iso(new Date(y, m - 1, 1)), to: iso(new Date(y, m, 0)) } },
        { label: 'Last 6 Months', range: { from: iso(new Date(y, m - 6, 1)), to: iso(new Date(y, m + 1, 0)) } },
        { label: 'This Year', range: { from: iso(new Date(y, 0, 1)), to: iso(new Date(y, 11, 31)) } },
    ];
}

/**
 * "Date" filter: a From and a To date plus quick ranges. Replaces the
 * jQuery daterangepicker (moment.js + daterangepicker.js, ~70 KB) with the
 * browser's own date inputs.
 */
export default function DateRangeField({ value, onChange, id = 'date-range' }: { value: DateRange; onChange: (range: DateRange) => void; id?: string }) {
    const t = useTrans();
    const max = iso(new Date());
    return (
        <div className="admin-date-range">
            <label htmlFor={`${id}-from`}>{t('Date')}</label>
            <div className="d-flex gap-2 flex-wrap">
                <input
                    id={`${id}-from`}
                    type="date"
                    className="form-control bg--white"
                    aria-label={t('Start Date')}
                    value={value.from}
                    max={value.to || max}
                    onChange={(e) => onChange({ from: e.target.value, to: value.to && e.target.value > value.to ? e.target.value : value.to })}
                />
                <input
                    type="date"
                    className="form-control bg--white"
                    aria-label={t('End Date')}
                    value={value.to}
                    min={value.from || undefined}
                    max={max}
                    onChange={(e) => onChange({ from: value.from, to: e.target.value })}
                />
                <select
                    className="form-control bg--white admin-date-range__preset"
                    aria-label={t('Quick range')}
                    value=""
                    onChange={(e) => {
                        if (e.target.value === 'clear') onChange({ from: '', to: '' });
                        const preset = presets().find((p) => p.label === e.target.value);
                        if (preset) onChange(preset.range);
                    }}
                >
                    <option value="">{t('Quick range')}</option>
                    {presets().map((p) => (
                        <option key={p.label} value={p.label}>
                            {t(p.label)}
                        </option>
                    ))}
                    <option value="clear">{t('Clear')}</option>
                </select>
            </div>
        </div>
    );
}
