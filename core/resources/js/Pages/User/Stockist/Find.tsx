import { Head, router } from '@inertiajs/react';
import { useState, type FormEvent } from 'react';
import Modal from '@/Components/Modal';
import { useTrans } from '@/lib/i18n';
import type { PageProps } from '@/types';
import './Find.css';

type Result = {
    id: number;
    businessName: string;
    contact: string;
    phone: string | null;
    email: string | null;
    verified: boolean;
    address: string[];
    city: string;
    state: string;
    hours: string[];
    services: string[];
    directions: string;
};

type Props = PageProps<{ city: string; state: string; searched: boolean; results: Result[]; findUrl: string }>;

const QUICK = [
    { city: 'Lagos', state: 'Lagos' },
    { city: 'Abuja', state: 'FCT' },
    { city: 'Port Harcourt', state: 'Rivers' },
    { city: 'Ibadan', state: 'Oyo' },
    { city: 'Kano', state: 'Kano' },
];

const STEPS = [
    { tone: 'bg-success', icon: 'fa-receipt', title: '1. Get Invoice Code', text: 'Find your invoice code in order details' },
    { tone: 'bg-success', icon: 'fa-map-marker-alt', title: '2. Find Stockist', text: 'Search for stockists near your location' },
    { tone: 'bg-warning', icon: 'fa-store', title: '3. Visit Location', text: 'Go to stockist with invoice code' },
    { tone: 'bg-info', icon: 'fa-box-open', title: '4. Redeem Products', text: 'Present code and collect items' },
];

export default function FindStockist({ city, state, searched, results, findUrl }: Props) {
    const t = useTrans();
    const [form, setForm] = useState({ city, state });
    const [busy, setBusy] = useState(false);
    const [open, setOpen] = useState<Result | null>(null);

    const search = (query: { city: string; state: string }) =>
        router.get(findUrl, query, { preserveState: true, preserveScroll: true, onStart: () => setBusy(true), onFinish: () => setBusy(false) });

    const submit = (event: FormEvent) => {
        event.preventDefault();
        search({ city: form.city.trim(), state: form.state.trim() });
    };

    return (
        <div className="page-stockist-find">
            <Head title={t('Find Stockist - Locate Nearby Stores')} />
            <div className="container-fluid py-5 bg-gradient-light">
                <div className="container">
                    <div className="row mb-5">
                        <div className="col-12 text-center">
                            <h1 className="display-5 fw-bold text-dark mb-3">{t('Find a Stockist Near You')}</h1>
                            <p className="lead text-muted">{t('Locate authorized stockists to redeem your products')}</p>
                        </div>
                    </div>

                    <div className="row justify-content-center">
                        <div className="col-lg-10">
                            <div className="card border-0 shadow-lg rounded-3 mb-5">
                                <div className="card-header bg-gradient-success text-white py-4 rounded-top">
                                    <h4 className="mb-0 text-center text-white"><i className="fas fa-map-marker-alt me-2" />{t('Search Stockists by Location')}</h4>
                                </div>
                                <div className="card-body p-4">
                                    <form onSubmit={submit} role="search">
                                        <div className="row g-3 align-items-end">
                                            <div className="col-md-5">
                                                <label className="form-label fw-semibold" htmlFor="cityInput">{t('City')}</label>
                                                <input type="text" className="form-control form-control-lg" id="cityInput" placeholder={t('Enter your city')} value={form.city} onChange={(e) => setForm({ ...form, city: e.target.value })} />
                                            </div>
                                            <div className="col-md-5">
                                                <label className="form-label fw-semibold" htmlFor="stateInput">{t('State')}</label>
                                                <input type="text" className="form-control form-control-lg" id="stateInput" placeholder={t('Enter your state')} value={form.state} onChange={(e) => setForm({ ...form, state: e.target.value })} />
                                            </div>
                                            <div className="col-md-2">
                                                <button type="submit" className="btn btn-success btn-lg w-100 h-100" disabled={busy || (!form.city.trim() && !form.state.trim())}>
                                                    <i className={`fas ${busy ? 'fa-spinner fa-spin' : 'fa-search'} me-2`} />{t('Search')}
                                                </button>
                                            </div>
                                        </div>
                                        <div className="row mt-4">
                                            <div className="col-12">
                                                <small className="text-muted">{t('Quick search popular locations:')}</small>
                                                <div className="d-flex flex-wrap gap-2 mt-2">
                                                    {QUICK.map((q) => (
                                                        <button
                                                            type="button"
                                                            key={q.city}
                                                            className="btn btn-outline-success btn-sm quick-location"
                                                            onClick={() => {
                                                                setForm(q);
                                                                search(q);
                                                            }}
                                                        >
                                                            {q.city}
                                                        </button>
                                                    ))}
                                                </div>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>

                            {searched && results.length > 0 && (
                                <div>
                                    <div className="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
                                        <h3 className="fw-bold mb-0">{t('Available Stockists')}</h3>
                                        <span className="badge bg-success fs-6" aria-live="polite">
                                            {results.length === 1 ? t('1 stockist found') : t(':count stockists found', { count: results.length })}
                                        </span>
                                    </div>
                                    <div className="row g-4">
                                        {results.map((s) => (
                                            <div className="col-md-6 col-lg-4" key={s.id}>
                                                <div className="card stockist-card border-0 shadow-sm rounded-3 h-100">
                                                    <div className="card-body d-flex flex-column">
                                                        <div className="d-flex justify-content-between align-items-start mb-3">
                                                            <div className="flex-grow-1">
                                                                <h6 className="fw-bold text-dark mb-1">{s.businessName}</h6>
                                                                <p className="text-muted small mb-0">{s.contact}</p>
                                                            </div>
                                                            {s.verified && <span className="badge bg-success ms-2"><i className="fas fa-check-circle me-1" />{t('Verified')}</span>}
                                                        </div>
                                                        <div className="contact-info mb-3">
                                                            <p className="small mb-2"><i className="fas fa-map-marker-alt text-danger me-2" />{s.city}, {s.state}</p>
                                                            {s.phone && <p className="small mb-2"><i className="fas fa-phone text-success me-2" /><a href={`tel:${s.phone}`}>{s.phone}</a></p>}
                                                        </div>
                                                        {s.services.length > 0 && (
                                                            <div className="services mb-3">
                                                                <small className="text-muted d-block mb-1">{t('Services:')}</small>
                                                                {s.services.slice(0, 2).map((service) => <span key={service} className="badge bg-light text-dark border small me-1 mb-1">{service}</span>)}
                                                                {s.services.length > 2 && <span className="badge bg-light text-dark border small">{t('+:count more', { count: s.services.length - 2 })}</span>}
                                                            </div>
                                                        )}
                                                        <div className="d-grid mt-auto">
                                                            <button type="button" className="btn btn-outline-success btn-sm rounded-pill" onClick={() => setOpen(s)}>
                                                                <i className="fas fa-eye me-1" />{t('View Details')}
                                                            </button>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        ))}
                                    </div>
                                </div>
                            )}

                            {searched && results.length === 0 && (
                                <div className="text-center py-5">
                                    <i className="fas fa-search fa-4x text-muted mb-4" />
                                    <h4 className="text-muted">{t('No Stockists Found')}</h4>
                                    <p className="text-muted mb-4">{t("We couldn't find any stockists in the specified location.")}</p>
                                    <button type="button" className="btn btn-success" onClick={() => document.getElementById('cityInput')?.focus()}>
                                        <i className="fas fa-undo me-2" />{t('Try Another Search')}
                                    </button>
                                </div>
                            )}

                            <div className="card border-0 shadow-sm rounded-3 mt-5">
                                <div className="card-header bg-white py-3">
                                    <h5 className="mb-0"><i className="fas fa-info-circle me-2 text-info" />{t('How to Redeem Your Products')}</h5>
                                </div>
                                <div className="card-body">
                                    <div className="row text-center">
                                        {STEPS.map((step) => (
                                            <div className="col-md-3 mb-3" key={step.title}>
                                                <div className={`step-icon ${step.tone} rounded-circle d-flex align-items-center justify-content-center mx-auto mb-3`} style={{ width: 80, height: 80 }}>
                                                    <i className={`fas ${step.icon} fa-2x text-white`} />
                                                </div>
                                                <h6>{t(step.title)}</h6>
                                                <p className="text-muted small">{t(step.text)}</p>
                                            </div>
                                        ))}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <Modal open={open !== null} onClose={() => setOpen(null)} size="lg" labelledBy="stockist-title">
                {open && (
                    <>
                        <div className="modal-header">
                            <h5 className="modal-title" id="stockist-title"><i className="fas fa-store me-2" />{open.businessName}</h5>
                            <button type="button" className="btn-close" aria-label={t('Close')} onClick={() => setOpen(null)} />
                        </div>
                        <div className="modal-body">
                            <div className="row">
                                <div className="col-md-6">
                                    <div className="mb-4">
                                        <h6 className="fw-semibold text-success mb-3"><i className="fas fa-info-circle me-2" />{t('Business Information')}</h6>
                                        <div className="ps-3">
                                            <p className="mb-2"><strong>{t('Contact Person:')}</strong><br />{open.contact}</p>
                                            <p className="mb-2"><strong>{t('Business Name:')}</strong><br />{open.businessName}</p>
                                            {open.phone && <p className="mb-2"><strong>{t('Phone:')}</strong><br /><a href={`tel:${open.phone}`} className="text-decoration-none">{open.phone}</a></p>}
                                            {open.email && <p className="mb-0"><strong>{t('Email:')}</strong><br /><a href={`mailto:${open.email}`} className="text-decoration-none">{open.email}</a></p>}
                                        </div>
                                    </div>
                                    <div className="mb-4">
                                        <h6 className="fw-semibold text-success mb-3"><i className="fas fa-map-marker-alt me-2" />{t('Location')}</h6>
                                        <div className="ps-3">
                                            {open.address.map((line, i) => <p className="mb-1" key={i}>{line}</p>)}
                                        </div>
                                    </div>
                                </div>
                                <div className="col-md-6">
                                    <div className="mb-4">
                                        <h6 className="fw-semibold text-warning mb-3"><i className="fas fa-clock me-2" />{t('Opening Hours')}</h6>
                                        <div className="ps-3">
                                            {open.hours.length > 0 ? open.hours.map((h) => <p className="small mb-1" key={h}>{h}</p>) : <p className="text-muted small">{t('Not specified')}</p>}
                                        </div>
                                    </div>
                                    {open.services.length > 0 && (
                                        <div className="mb-4">
                                            <h6 className="fw-semibold text-success mb-3"><i className="fas fa-concierge-bell me-2" />{t('Services')}</h6>
                                            <div className="ps-3">
                                                {open.services.map((s) => <span key={s} className="badge bg-success text-white me-1 mb-1">{s}</span>)}
                                            </div>
                                        </div>
                                    )}
                                    {open.verified && (
                                        <div className="alert alert-success small">
                                            <i className="fas fa-shield-alt me-2" />
                                            <strong>{t('Verified Stockist')}</strong>
                                            <br />
                                            {t('This business has been verified and approved by our team.')}
                                        </div>
                                    )}
                                </div>
                            </div>
                            <div className="alert alert-info mt-3">
                                <strong>{t('How to Redeem:')}</strong> {t('Visit this location with your invoice code and a valid ID to redeem your products. The stockist will process your redemption and provide your items.')}
                            </div>
                        </div>
                        <div className="modal-footer">
                            <button type="button" className="btn btn-secondary" onClick={() => setOpen(null)}>{t('Close')}</button>
                            <a className="btn btn-success" href={open.directions} target="_blank" rel="noopener noreferrer"><i className="fas fa-directions me-2" />{t('Get Directions')}</a>
                        </div>
                    </>
                )}
            </Modal>
        </div>
    );
}
