import { Link } from '@inertiajs/react';

/**
 * <x-widget style="6">: a coloured box with an icon, a caption, a value and
 * an optional note. With a link the whole box opens it: a normal page load,
 * or an Inertia visit with `inertia` when the linked page is React.
 */
export default function BoxWidget({
    title,
    value,
    icon,
    bg,
    note,
    link,
    outline = false,
    inertia = false,
}: {
    title: string;
    value: string;
    icon: string;
    bg: string;
    note?: string | null;
    link?: string | null;
    outline?: boolean;
    inertia?: boolean;
}) {
    const box = (
        <div className={`widget-seven bg--${bg} ${outline ? 'outline' : ''}`}>
            <div className="widget-seven__content">
                <span className="widget-seven__content-icon">
                    <span className="icon">
                        <i className={icon} />
                    </span>
                </span>
                <div className="widget-seven__description">
                    <p className="widget-seven__content-title">{title}</p>
                    <h3 className="widget-seven__content-amount">{value}</h3>
                    {note && <p className="widget-seven__content-subheading">{note}</p>}
                </div>
            </div>
            {link && (
                <span className="widget-seven__arrow">
                    <i className="fas fa-chevron-right" />
                </span>
            )}
        </div>
    );
    if (!link) return box;
    return inertia ? <Link href={link}>{box}</Link> : <a href={link}>{box}</a>;
}
