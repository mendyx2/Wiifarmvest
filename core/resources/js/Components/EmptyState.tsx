type Props = {
    icon: string;
    title: string;
    text?: string;
    /** CSS class prefix of the page, e.g. "txn" gives .txn-empty / .txn-empty-icon */
    prefix: string;
};

/** "Nothing here yet" block, styled by the page's existing *-empty classes. */
export default function EmptyState({ icon, title, text, prefix }: Props) {
    return (
        <div className={`${prefix}-empty`}>
            <div className={`${prefix}-empty-icon`}>
                <i className={`las ${icon}`} />
            </div>
            <h6>{title}</h6>
            {text && <p>{text}</p>}
        </div>
    );
}
