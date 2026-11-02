import { useEffect, useId, useRef, useState, type KeyboardEvent, type PointerEvent } from 'react';
import { useTrans } from '@/lib/i18n';

export type Series = { name: string; data: number[] };

type Props = {
    title: string;
    labels: string[];
    series: Series[];
    /** Formats a value for the tooltip, the table and the axis */
    format: (n: number) => string;
    /** Shown dimmed while new data loads (the frame is kept) */
    loading?: boolean;
    height?: number;
};

/*
 * Categorical slots 1–3 of the reference palette (validated together on a
 * light surface: CVD ΔE 9.2, normal 27.6). Slot 3 is under 3:1 contrast on
 * white, so the chart always offers its table view.
 */
const COLORS = ['#2a78d6', '#eb6834', '#1baf7a'];
const GRID = '#e8e8e6';
const PAD = { top: 12, right: 16, bottom: 28, left: 64 };

/** A rounded upper bound and tick step for the y axis: 0, 2K, 4K ... */
function niceScale(max: number): { top: number; step: number } {
    if (max <= 0) return { top: 1, step: 0.25 };
    const rough = max / 4;
    const mag = 10 ** Math.floor(Math.log10(rough));
    const step = [1, 2, 2.5, 5, 10].map((m) => m * mag).find((s) => s >= rough) ?? 10 * mag;
    return { top: Math.ceil(max / step) * step, step };
}

const compact = (n: number) => (Math.abs(n) >= 1e9 ? `${+(n / 1e9).toFixed(1)}B` : Math.abs(n) >= 1e6 ? `${+(n / 1e6).toFixed(1)}M` : Math.abs(n) >= 1e3 ? `${+(n / 1e3).toFixed(1)}K` : String(+n.toFixed(2)));

/**
 * A line chart drawn as SVG: one shared y axis, 2px lines, a legend with
 * line keys, a crosshair that snaps to the nearest date with one tooltip for
 * every series (pointer and arrow keys), and a table view of the same data.
 */
export default function LineChart({ title, labels, series, format, loading = false, height = 260 }: Props) {
    const t = useTrans();
    const box = useRef<HTMLDivElement>(null);
    const [width, setWidth] = useState(600);
    const [active, setActive] = useState<number | null>(null);
    const [table, setTable] = useState(false);
    const titleId = useId();

    useEffect(() => {
        const el = box.current;
        if (!el) return;
        const ro = new ResizeObserver(([e]) => setWidth(Math.max(280, Math.floor(e.contentRect.width))));
        ro.observe(el);
        return () => ro.disconnect();
    }, []);

    const n = labels.length;
    const max = Math.max(0, ...series.flatMap((s) => s.data));
    const { top, step } = niceScale(max);
    const plotW = width - PAD.left - PAD.right;
    const plotH = height - PAD.top - PAD.bottom;
    const x = (i: number) => PAD.left + (n <= 1 ? plotW / 2 : (i / (n - 1)) * plotW);
    const y = (v: number) => PAD.top + plotH - (v / top) * plotH;
    const ticks = Array.from({ length: Math.round(top / step) + 1 }, (_, i) => i * step);
    // About one x label per 90px
    const every = Math.max(1, Math.ceil(n / Math.max(2, Math.floor(plotW / 90))));

    const onMove = (e: PointerEvent<SVGSVGElement>) => {
        const rect = e.currentTarget.getBoundingClientRect();
        const px = ((e.clientX - rect.left) / rect.width) * width;
        const i = n <= 1 ? 0 : Math.round(((px - PAD.left) / plotW) * (n - 1));
        setActive(Math.min(n - 1, Math.max(0, i)));
    };
    const onKey = (e: KeyboardEvent<SVGSVGElement>) => {
        if (e.key === 'ArrowRight' || e.key === 'ArrowLeft') {
            e.preventDefault();
            setActive((a) => Math.min(n - 1, Math.max(0, (a ?? (e.key === 'ArrowRight' ? -1 : n)) + (e.key === 'ArrowRight' ? 1 : -1))));
        }
    };
    const tipLeft = active === null ? 0 : x(active) / width;

    return (
        <div className="card h-100">
            <div className="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
                <h5 className="card-title mb-0" id={titleId}>
                    {title}
                </h5>
                <button type="button" className="btn btn-sm btn-outline--dark" aria-pressed={table} onClick={() => setTable((v) => !v)}>
                    <i className={`las ${table ? 'la-chart-line' : 'la-table'}`} /> {table ? t('Chart') : t('Table')}
                </button>
            </div>
            <div className="card-body">
                {series.length > 1 && (
                    <ul className="list-inline mb-2 small" aria-label={t('Legend')}>
                        {series.map((s, i) => (
                            <li className="list-inline-item me-3" key={s.name}>
                                <svg width="16" height="8" aria-hidden className="me-1">
                                    <line x1="0" y1="4" x2="16" y2="4" stroke={COLORS[i % COLORS.length]} strokeWidth="2" strokeLinecap="round" />
                                </svg>
                                {t(s.name)}
                            </li>
                        ))}
                    </ul>
                )}
                {table ? (
                    <div className="table-responsive" style={{ maxHeight: height + 20, overflowY: 'auto' }}>
                        <table className="table table-sm mb-0" aria-labelledby={titleId}>
                            <thead>
                                <tr>
                                    <th scope="col">{t('Date')}</th>
                                    {series.map((s) => (
                                        <th scope="col" className="text-end" key={s.name}>
                                            {t(s.name)}
                                        </th>
                                    ))}
                                </tr>
                            </thead>
                            <tbody style={{ fontVariantNumeric: 'tabular-nums' }}>
                                {labels.map((l, i) => (
                                    <tr key={l}>
                                        <th scope="row" className="fw-normal">
                                            {l}
                                        </th>
                                        {series.map((s) => (
                                            <td className="text-end" key={s.name}>
                                                {format(s.data[i] ?? 0)}
                                            </td>
                                        ))}
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                ) : (
                    <div ref={box} className="position-relative" style={{ opacity: loading ? 0.45 : 1, transition: 'opacity .2s' }}>
                        <svg
                            width="100%"
                            viewBox={`0 0 ${width} ${height}`}
                            role="img"
                            aria-labelledby={titleId}
                            tabIndex={0}
                            onPointerMove={onMove}
                            onPointerLeave={() => setActive(null)}
                            onFocus={() => setActive((a) => a ?? n - 1)}
                            onBlur={() => setActive(null)}
                            onKeyDown={onKey}
                            style={{ display: 'block', touchAction: 'pan-y', outline: 'none' }}
                        >
                            {ticks.map((v) => (
                                <g key={v}>
                                    <line x1={PAD.left} x2={width - PAD.right} y1={y(v)} y2={y(v)} stroke={GRID} strokeWidth="1" />
                                    <text x={PAD.left - 8} y={y(v)} textAnchor="end" dominantBaseline="middle" fontSize="11" fill="#6b6a66" style={{ fontVariantNumeric: 'tabular-nums' }}>
                                        {compact(v)}
                                    </text>
                                </g>
                            ))}
                            {labels.map((l, i) =>
                                i === n - 1 || (i % every === 0 && n - 1 - i >= every / 2) ? (
                                    <text key={l} x={x(i)} y={height - 8} textAnchor={i === 0 ? 'start' : i === n - 1 ? 'end' : 'middle'} fontSize="11" fill="#6b6a66">
                                        {l}
                                    </text>
                                ) : null,
                            )}
                            {active !== null && <line x1={x(active)} x2={x(active)} y1={PAD.top} y2={PAD.top + plotH} stroke="#9a9993" strokeWidth="1" />}
                            {series.map((s, si) => (
                                <polyline key={s.name} points={s.data.map((v, i) => `${x(i)},${y(v)}`).join(' ')} fill="none" stroke={COLORS[si % COLORS.length]} strokeWidth="2" strokeLinejoin="round" strokeLinecap="round" />
                            ))}
                            {active !== null && series.map((s, si) => <circle key={s.name} cx={x(active)} cy={y(s.data[active] ?? 0)} r="4" fill={COLORS[si % COLORS.length]} stroke="#fff" strokeWidth="2" />)}
                        </svg>
                        {active !== null && (
                            <div
                                role="status"
                                className="position-absolute bg-white border rounded shadow-sm px-2 py-1 small"
                                style={{ top: 4, left: `${tipLeft * 100}%`, transform: `translateX(${tipLeft > 0.6 ? 'calc(-100% - 10px)' : '10px'})`, pointerEvents: 'none', whiteSpace: 'nowrap' }}
                            >
                                <div style={{ color: '#52514e' }}>{labels[active]}</div>
                                {series.map((s, si) => (
                                    <div key={s.name} className="d-flex align-items-center gap-2">
                                        <svg width="12" height="8" aria-hidden>
                                            <line x1="0" y1="4" x2="12" y2="4" stroke={COLORS[si % COLORS.length]} strokeWidth="2" strokeLinecap="round" />
                                        </svg>
                                        <b style={{ color: '#0b0b0b' }}>{format(s.data[active] ?? 0)}</b>
                                        <span className="text-muted">{t(s.name)}</span>
                                    </div>
                                ))}
                            </div>
                        )}
                    </div>
                )}
            </div>
        </div>
    );
}
