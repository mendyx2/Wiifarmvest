import { Link } from '@inertiajs/react';

export type WidgetData = { title: string; value: string; icon: string; bg: string; link: string | null; /** The link is a React page (Inertia visit) */ react?: boolean };

/**
 * <x-widget style="7">: a coloured tile with a caption, a value and an icon.
 * With a link the tile opens it; React admin pages open as Inertia visits,
 * the rest (still Blade) with a normal load; `widget.react` says which.
 */
export default function LinkWidget({ widget }: { widget: WidgetData }) {
    const body = (
        <>
            <div className="widget-eight__description">
                <p className="widget-eight__content-title">{widget.title}</p>
                <h3 className="widget-eight__content-amount">{widget.value}</h3>
            </div>
            <span className="widget-eight__content-icon">
                <span className="icon">
                    <i className={widget.icon} />
                </span>
            </span>
        </>
    );
    const className = `widget-eight bg--${widget.bg} style-two`;

    if (!widget.link) return <div className={className}>{body}</div>;
    return widget.react ? (
        <Link href={widget.link} className={className}>
            {body}
        </Link>
    ) : (
        <a href={widget.link} className={className}>
            {body}
        </a>
    );
}
