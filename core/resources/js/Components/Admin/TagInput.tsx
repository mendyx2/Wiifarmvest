import { useState, type KeyboardEvent } from 'react';
import { useTrans } from '@/lib/i18n';

type Props = {
    id?: string;
    value: string[];
    onChange: (tags: string[]) => void;
    placeholder?: string;
    maxLength?: number;
};

/**
 * A list of short words (SEO keywords): typed words become tags on Enter or
 * comma, and each tag has a remove button. Replaces select2 "tags" mode.
 */
export default function TagInput({ id, value, onChange, placeholder, maxLength = 100 }: Props) {
    const t = useTrans();
    const [text, setText] = useState('');

    const add = (raw: string) => {
        const words = raw
            .split(',')
            .map((w) => w.trim())
            .filter((w) => w && !value.includes(w));
        if (words.length) onChange([...value, ...new Set(words)]);
        setText('');
    };

    const onKeyDown = (e: KeyboardEvent<HTMLInputElement>) => {
        if (e.key === 'Enter' || e.key === ',') {
            e.preventDefault();
            add(text);
        } else if (e.key === 'Backspace' && !text && value.length) {
            onChange(value.slice(0, -1));
        }
    };

    return (
        <div className="form-control d-flex flex-wrap align-items-center gap-1 h-auto" style={{ minHeight: 45 }}>
            {value.map((tag) => (
                <span key={tag} className="badge badge--primary d-inline-flex align-items-center gap-1" style={{ fontSize: 13 }}>
                    {tag}
                    <button type="button" className="btn p-0 border-0 bg-transparent text-reset lh-1" aria-label={t('Remove :tag', { tag })} onClick={() => onChange(value.filter((v) => v !== tag))}>
                        <i className="las la-times" />
                    </button>
                </span>
            ))}
            <input
                id={id}
                type="text"
                className="border-0 flex-grow-1"
                style={{ outline: 'none', minWidth: 120, background: 'transparent' }}
                maxLength={maxLength}
                placeholder={value.length ? '' : placeholder}
                value={text}
                onChange={(e) => (e.target.value.includes(',') ? add(e.target.value) : setText(e.target.value))}
                onKeyDown={onKeyDown}
                onBlur={() => text.trim() && add(text)}
            />
        </div>
    );
}
