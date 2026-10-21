import { usePage } from '@inertiajs/react';

type Replacements = Record<string, string | number>;

/**
 * Mirrors Laravel's __() helper: looks the key up in the current language's
 * JSON file (core/resources/lang/{locale}.json) and falls back to the key
 * itself, so untranslated strings still render in English.
 * Placeholders use Laravel's syntax: t('Hello :name', { name: 'Ada' }).
 */
export function translate(translations: Record<string, string>, key: string, replace: Replacements = {}): string {
    let line = translations[key] ?? key;

    for (const [name, value] of Object.entries(replace)) {
        line = line.split(`:${name}`).join(String(value));
    }

    return line;
}

export function useTrans() {
    const { translations } = usePage().props;

    return (key: string, replace?: Replacements) => translate(translations, key, replace);
}
