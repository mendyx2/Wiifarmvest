import { useEffect, useRef } from 'react';
import { useTrans } from '@/lib/i18n';

type Props = {
    value: string;
    onChange: (html: string) => void;
    id?: string;
    label?: string;
    minHeight?: number;
};

const TOOLS: { cmd: string; icon: string; title: string; arg?: string }[] = [
    { cmd: 'bold', icon: 'las la-bold', title: 'Bold' },
    { cmd: 'italic', icon: 'las la-italic', title: 'Italic' },
    { cmd: 'underline', icon: 'las la-underline', title: 'Underline' },
    { cmd: 'formatBlock', arg: 'h3', icon: 'las la-heading', title: 'Heading' },
    { cmd: 'formatBlock', arg: 'p', icon: 'las la-paragraph', title: 'Paragraph' },
    { cmd: 'insertUnorderedList', icon: 'las la-list-ul', title: 'Bulleted list' },
    { cmd: 'insertOrderedList', icon: 'las la-list-ol', title: 'Numbered list' },
    { cmd: 'createLink', icon: 'las la-link', title: 'Link' },
    { cmd: 'unlink', icon: 'las la-unlink', title: 'Remove link' },
    { cmd: 'removeFormat', icon: 'las la-remove-format', title: 'Clear formatting' },
];

/**
 * A small rich-text box for admin-written HTML (email bodies): replaces the
 * jQuery nicEdit editor. The text is edited in place (contentEditable) and
 * handed back as HTML. Only admins use it, and the HTML goes into emails
 * the server sends, as it did with nicEdit.
 */
export default function RichTextEditor({ value, onChange, id, label, minHeight = 220 }: Props) {
    const t = useTrans();
    const box = useRef<HTMLDivElement>(null);

    // Put outside changes (a reset, a value from the server) into the box,
    // but never while typing: that would move the caret.
    useEffect(() => {
        const el = box.current;
        if (el && el.innerHTML !== value && document.activeElement !== el) el.innerHTML = value;
    }, [value]);

    const run = (cmd: string, arg?: string) => {
        box.current?.focus();
        if (cmd === 'createLink') {
            const url = window.prompt(t('Link address (https://…)'));
            if (!url || !/^(https?:|mailto:)/i.test(url.trim())) return;
            arg = url.trim();
        }
        document.execCommand(cmd, false, arg);
        onChange(box.current?.innerHTML ?? '');
    };

    return (
        <div className="admin-rte">
            <div className="admin-rte__toolbar" role="toolbar" aria-label={t('Formatting')}>
                {TOOLS.map((tool) => (
                    <button
                        key={tool.cmd + (tool.arg ?? '')}
                        type="button"
                        className="admin-rte__btn"
                        title={t(tool.title)}
                        aria-label={t(tool.title)}
                        // Keep the selection in the text box when the button is pressed.
                        onMouseDown={(e) => e.preventDefault()}
                        onClick={() => run(tool.cmd, tool.arg)}
                    >
                        <i className={tool.icon} />
                    </button>
                ))}
            </div>
            <div
                ref={box}
                id={id}
                className="admin-rte__box form-control"
                contentEditable
                role="textbox"
                aria-multiline="true"
                aria-label={label}
                style={{ minHeight }}
                onInput={(e) => onChange(e.currentTarget.innerHTML)}
                suppressContentEditableWarning
            />
        </div>
    );
}
