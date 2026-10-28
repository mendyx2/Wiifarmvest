import { useEffect, useState } from 'react';

/** A list from the lookup: loading, loaded, or failed (then the page shows a text box). */
export type Geo = { status: 'idle' | 'loading' | 'ok' | 'failed'; options: string[] };

/** countriesnow.space answers { error, data: { states: [{ name }] } } or { error, data: [city names] }. */
type GeoData = { states?: { name: string }[] } & string[];

async function lookup(url: string, body: Record<string, string>, pick: (data: GeoData) => string[] | undefined): Promise<string[]> {
    const res = await fetch(url, { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(body) });
    const json = await res.json();
    if (!res.ok || json?.error) throw new Error('geo lookup failed');
    return pick(json.data) ?? [];
}

/**
 * Country → states → cities, from the free lookup service the Blade pages
 * used (called from the browser). Used by Add Distributor and Register.
 * When the service is down, `status` is 'failed' and the page lets the
 * person type the state and city instead of being stuck.
 */
export function useGeoLookup(base: string, country: string, state: string) {
    const [states, setStates] = useState<Geo>({ status: 'idle', options: [] });
    const [cities, setCities] = useState<Geo>({ status: 'idle', options: [] });

    useEffect(() => {
        if (!country) return;
        let cancelled = false;
        setStates({ status: 'loading', options: [] });
        setCities({ status: 'idle', options: [] });
        lookup(`${base}/states`, { country }, (data) => data?.states?.map((s) => s.name))
            .then((options) => !cancelled && setStates({ status: options.length ? 'ok' : 'failed', options }))
            .catch(() => !cancelled && setStates({ status: 'failed', options: [] }));
        return () => {
            cancelled = true;
        };
    }, [base, country]);

    useEffect(() => {
        if (!state || states.status !== 'ok') return;
        let cancelled = false;
        setCities({ status: 'loading', options: [] });
        lookup(`${base}/state/cities`, { country, state }, (data) => data)
            .then((options) => !cancelled && setCities({ status: options.length ? 'ok' : 'failed', options }))
            .catch(() => !cancelled && setCities({ status: 'failed', options: [] }));
        return () => {
            cancelled = true;
        };
    }, [state, states.status]); // eslint-disable-line react-hooks/exhaustive-deps

    // Cities cannot load without states: both become text boxes together.
    return { states, cities: states.status === 'failed' ? states : cities };
}
