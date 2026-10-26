/** <x-widget style="2">: an icon box with a value and a caption. */
export default function StatWidget({ value, title, icon, color = 'primary' }: { value: string; title: string; icon: string; color?: string }) {
    return (
        <div className="widget-two box--shadow2 b-radius--5 bg--white">
            <i className={`${icon} overlay-icon text--${color}`} />
            <div className={`widget-two__icon b-radius--5 border border--${color} text--${color}`}>
                <i className={icon} />
            </div>
            <div className="widget-two__content">
                <h3>{value}</h3>
                <p>{title}</p>
            </div>
        </div>
    );
}
