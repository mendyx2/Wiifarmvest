import { Link, useForm } from '@inertiajs/react';
import { useEffect, useMemo, useState, type FormEvent, type ReactNode } from 'react';
import { ConfirmButton, FieldError, withId } from '@/Components/Admin/Form';
import ImageUploader from '@/Components/Admin/ImageUploader';
import PageHeader from '@/Components/Admin/PageHeader';
import RichTextEditor from '@/Components/Admin/RichTextEditor';
import { useTrans } from '@/lib/i18n';
import './ProductForm.css';

type Spec = { name: string; value: string };
type GalleryImage = { id: number; src: string };
type Option = { id: number; name: string };
type StateOption = Option & { code: string };
type StatePrice = { id: number; stateId: number; state: string; code: string; price: number; mega: number; micro: number };
type NewStatePrice = { state_id: string; price: string; mega_stockist_bonus: string; micro_stockist_bonus: string };

type Product = {
    id: number;
    name: string;
    category: string;
    sku: string;
    quantity: string;
    min_order_quantity: string;
    max_order_quantity: string;
    price: string;
    selling_price: string;
    pv: string;
    affiliate_bonus_type: string;
    affiliate_bonus_value: string;
    description: string;
    specification: Spec[];
    meta_title: string;
    meta_keywords: string[];
    meta_description: string;
    thumbnail: string;
    gallery: GalleryImage[];
};

type Props = {
    title: string;
    product: Product | null;
    statePrices: StatePrice[];
    categories: Option[];
    states: StateOption[];
    pvRate: number;
    imageSize: string;
    urls: { save: string; index: string; statePrice: string | null; statePriceDestroy: string | null };
};

type FormData = Omit<Product, 'id' | 'thumbnail' | 'gallery'> & {
    thumbnail: File | null;
    gallery: File[];
    /** Ids of the saved gallery images to keep */
    old: number[];
    state_prices: NewStatePrice[];
};

const MAX_IMAGES = 10;
const MAX_BYTES = 3 * 1024 * 1024;
const naira = (n: number) => `₦${n.toLocaleString('en-NG', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;

/**
 * Shop → Create / Edit Product. One page for both: the two Blade forms
 * were near-copies. On a new product the state prices are part of the
 * form; on an existing one each state price is saved on its own, as before.
 */
export default function ProductForm({ title, product, statePrices, categories, states, pvRate, imageSize, urls }: Props) {
    const t = useTrans();
    const form = useForm<FormData>({
        name: product?.name ?? '',
        category: product?.category ?? '',
        sku: product?.sku ?? '',
        quantity: product?.quantity ?? '',
        min_order_quantity: product?.min_order_quantity ?? '1',
        max_order_quantity: product?.max_order_quantity ?? '',
        price: product?.price ?? '',
        selling_price: product?.selling_price ?? '',
        pv: product?.pv ?? '0',
        affiliate_bonus_type: product?.affiliate_bonus_type ?? '',
        affiliate_bonus_value: product?.affiliate_bonus_value ?? '',
        description: product?.description ?? '',
        specification: product?.specification ?? [],
        meta_title: product?.meta_title ?? '',
        meta_keywords: product?.meta_keywords ?? [],
        meta_description: product?.meta_description ?? '',
        thumbnail: null,
        gallery: [],
        old: product?.gallery.map((g) => g.id) ?? [],
        state_prices: [],
    });
    const { data, setData, errors } = form;
    const err = errors as Record<string, string | undefined>;
    /** All messages whose key starts with a prefix ("specification.", "gallery."), once each. */
    const errorsFor = (prefix: string) => [...new Set(Object.entries(err).filter(([k, v]) => k.startsWith(prefix) && v).map(([, v]) => v as string))];

    // After a save the server sends the gallery again: keep only what is still there.
    const savedGallery = product?.gallery;
    useEffect(() => {
        if (savedGallery) setData((d) => ({ ...d, old: savedGallery.map((g) => g.id), gallery: [], thumbnail: null }));
    }, [savedGallery, setData]);

    const submit = (e: FormEvent) => {
        e.preventDefault();
        form.post(urls.save, { forceFormData: true, preserveScroll: true });
    };

    const pv = parseFloat(data.pv) || 0;
    const hasErrors = Object.keys(err).length > 0;

    return (
        <>
            <PageHeader title={title}>
                <Link className="btn btn-sm btn-outline--dark" href={urls.index}>
                    <i className="las la-undo" /> {t('Back')}
                </Link>
            </PageHeader>
            <form onSubmit={submit}>
                {hasErrors && (
                    <div className="alert alert-danger" role="alert">
                        {t('Please correct the fields marked below.')}
                    </div>
                )}
                <div className="pf-grid">
                    <div className="pf-col-main">
                        <Card icon="las la-tag" title={t('Basic Information')}>
                            <Field id="pf-name" label={t('Product Name')} required error={err.name}>
                                <input id="pf-name" className="pf-input" required maxLength={255} placeholder={t('Enter product name')} value={data.name} onChange={(e) => setData('name', e.target.value)} />
                            </Field>
                            <div className="pf-field-row">
                                <Field id="pf-category" label={t('Category')} required error={err.category}>
                                    <select id="pf-category" className="pf-input" required value={data.category} onChange={(e) => setData('category', e.target.value)}>
                                        <option value="">{t('Select category')}</option>
                                        {categories.map((c) => (
                                            <option key={c.id} value={c.id}>
                                                {c.name}
                                            </option>
                                        ))}
                                    </select>
                                </Field>
                                <Field id="pf-sku" label={t('SKU (kg)')} required error={err.sku}>
                                    <input id="pf-sku" className="pf-input" type="number" min={1} step={1} required placeholder={t('e.g. 25')} value={data.sku} onChange={(e) => setData('sku', e.target.value)} />
                                </Field>
                            </div>
                            <div className="pf-field-row">
                                <Field id="pf-qty" label={t('Stock Quantity')} required error={err.quantity}>
                                    <input id="pf-qty" className="pf-input" type="number" min={0} step={1} required placeholder={t('Available units')} value={data.quantity} onChange={(e) => setData('quantity', e.target.value)} />
                                </Field>
                                <Field id="pf-min" label={t('Min Order Qty')} required error={err.min_order_quantity}>
                                    <input id="pf-min" className="pf-input" type="number" min={1} step={1} required value={data.min_order_quantity} onChange={(e) => setData('min_order_quantity', e.target.value)} />
                                </Field>
                                <Field id="pf-max" label={t('Max Order Qty')} required error={err.max_order_quantity}>
                                    <input id="pf-max" className="pf-input" type="number" min={data.min_order_quantity || 1} step={1} required value={data.max_order_quantity} onChange={(e) => setData('max_order_quantity', e.target.value)} />
                                </Field>
                            </div>
                        </Card>

                        <Card icon="las la-coins" title={t('Pricing & Metrics')}>
                            <div className="pf-metrics-grid">
                                <Field id="pf-price" label={t('Price')} required error={err.price} block>
                                    <div className="pf-input-prefix">
                                        <span className="pf-prefix">₦</span>
                                        <input id="pf-price" className="pf-input" type="number" step="any" min={0.01} required placeholder="0.00" value={data.price} onChange={(e) => setData('price', e.target.value)} />
                                    </div>
                                </Field>
                                <Field id="pf-selling" label={t('Selling Price')} error={err.selling_price} hint={t('Public price shown on /shop. Leave blank to use the Price above.')} block>
                                    <div className="pf-input-prefix">
                                        <span className="pf-prefix">₦</span>
                                        <input id="pf-selling" className="pf-input" type="number" step="any" min={0.01} placeholder="0.00" value={data.selling_price} onChange={(e) => setData('selling_price', e.target.value)} />
                                    </div>
                                </Field>
                                <Field id="pf-pv" label={t('Point Value (PV)')} required error={err.pv} hint={t('Accumulated for member rewards')} block>
                                    <input id="pf-pv" className="pf-input" type="number" step="any" min={0} required placeholder="0.00" value={data.pv} onChange={(e) => setData('pv', e.target.value)} />
                                </Field>
                            </div>
                        </Card>

                        <Card icon="las la-link" title={t('Affiliate Bonus')}>
                            <div className="pf-metric-grid">
                                <Field id="pf-bonus-type" label={t('Bonus Type')} error={err.affiliate_bonus_type} hint={t('Paid to the referring member on /shop sales')} block>
                                    <select
                                        id="pf-bonus-type"
                                        className="pf-input"
                                        value={data.affiliate_bonus_type}
                                        onChange={(e) => setData((d) => ({ ...d, affiliate_bonus_type: e.target.value, affiliate_bonus_value: e.target.value ? d.affiliate_bonus_value : '' }))}
                                    >
                                        <option value="">{t('None')}</option>
                                        <option value="fixed">{t('Fixed Amount')}</option>
                                        <option value="percentage">{t('Percentage of Sale')}</option>
                                    </select>
                                </Field>
                                <Field id="pf-bonus-value" label={t('Bonus Value')} required={!!data.affiliate_bonus_type} error={err.affiliate_bonus_value} hint={data.affiliate_bonus_type === 'percentage' ? t('% of the sale (0–100)') : t('₦ amount per sale')} block>
                                    <input
                                        id="pf-bonus-value"
                                        className="pf-input"
                                        type="number"
                                        step="any"
                                        min={0}
                                        max={data.affiliate_bonus_type === 'percentage' ? 100 : undefined}
                                        placeholder="0.00"
                                        disabled={!data.affiliate_bonus_type}
                                        required={!!data.affiliate_bonus_type}
                                        value={data.affiliate_bonus_value}
                                        onChange={(e) => setData('affiliate_bonus_value', e.target.value)}
                                    />
                                </Field>
                            </div>
                        </Card>

                        {!product && (
                            <NewStatePrices
                                rows={data.state_prices}
                                onChange={(rows) => setData('state_prices', rows)}
                                states={states}
                                pv={pv}
                                pvRate={pvRate}
                                errors={errorsFor('state_prices')}
                            />
                        )}

                        <Card icon="las la-align-left" title={t('Description & Specifications')}>
                            <Field id="pf-description" label={t('Product Description')} required error={err.description}>
                                <RichTextEditor id="pf-description" label={t('Product Description')} value={data.description} onChange={(html) => setData('description', html)} minHeight={180} />
                            </Field>
                            <Specifications rows={data.specification} onChange={(rows) => setData('specification', rows)} errors={errorsFor('specification')} />
                        </Card>

                        <Card icon="las la-search" title={t('SEO')} muted optional>
                            <Field id="pf-meta-title" label={t('Meta Title')} error={err.meta_title}>
                                <input id="pf-meta-title" className="pf-input" maxLength={255} placeholder={t('Meta title')} value={data.meta_title} onChange={(e) => setData('meta_title', e.target.value)} />
                            </Field>
                            <Field id="pf-keywords" label={t('Meta Keywords')} error={errorsFor('meta_keywords')[0]}>
                                <TagInput id="pf-keywords" value={data.meta_keywords} onChange={(tags) => setData('meta_keywords', tags)} />
                            </Field>
                            <Field id="pf-meta-description" label={t('Meta Description')} error={err.meta_description}>
                                <textarea id="pf-meta-description" className="pf-input" rows={3} maxLength={1000} placeholder={t('Meta description')} value={data.meta_description} onChange={(e) => setData('meta_description', e.target.value)} />
                            </Field>
                        </Card>
                    </div>

                    <div className="pf-col-side">
                        <Card icon="las la-image" title={t('Thumbnail')} required={!product}>
                            <ImageUploader className="w-100" image={product?.thumbnail ?? ''} size={imageSize} required={!product} onChange={(file) => setData('thumbnail', file)} />
                            <FieldError message={err.thumbnail} />
                        </Card>
                        <Card icon="las la-images" title={t('Gallery')}>
                            <Gallery
                                saved={(product?.gallery ?? []).filter((g) => data.old.includes(g.id))}
                                files={data.gallery}
                                onRemoveSaved={(id) => setData('old', data.old.filter((x) => x !== id))}
                                onFiles={(files) => setData('gallery', files)}
                            />
                            {errorsFor('gallery').map((m) => (
                                <FieldError key={m} message={m} />
                            ))}
                        </Card>
                        <button className="pf-submit-btn" type="submit" disabled={form.processing}>
                            <i className="las la-save" /> {product ? t('Update Product') : t('Save Product')}
                        </button>
                        {form.progress && <progress className="w-100" value={form.progress.percentage} max={100} aria-label={t('Uploading')} />}
                    </div>
                </div>
            </form>

            {product && urls.statePrice && urls.statePriceDestroy && (
                <SavedStatePrices prices={statePrices} states={states} productPrice={parseFloat(product.price) || 0} pv={parseFloat(product.pv) || 0} pvRate={pvRate} url={urls.statePrice} destroyUrl={urls.statePriceDestroy} />
            )}
        </>
    );
}

function Card({ icon, title, children, muted, optional, required }: { icon: string; title: string; children: ReactNode; muted?: boolean; optional?: boolean; required?: boolean }) {
    const t = useTrans();
    return (
        <div className="pf-card">
            <div className={`pf-card-header ${muted ? 'pf-card-header--muted' : ''}`}>
                <i className={icon} /> {title} {required && <span className="pf-req">*</span>}
                {optional && <span className="pf-optional">{t('optional')}</span>}
            </div>
            <div className="pf-card-body">{children}</div>
        </div>
    );
}

function Field({ id, label, required, error, hint, block, children }: { id: string; label: string; required?: boolean; error?: string; hint?: string; block?: boolean; children: ReactNode }) {
    return (
        <div className={block ? 'pf-metric-block' : 'pf-field'}>
            <label className="pf-label" htmlFor={id}>
                {label} {required && <span className="pf-req">*</span>}
            </label>
            {children}
            {hint && <p className="pf-hint">{hint}</p>}
            <FieldError message={error} />
        </div>
    );
}

function Specifications({ rows, onChange, errors }: { rows: Spec[]; onChange: (rows: Spec[]) => void; errors: string[] }) {
    const t = useTrans();
    const set = (i: number, key: keyof Spec, value: string) => onChange(rows.map((r, j) => (j === i ? { ...r, [key]: value } : r)));
    return (
        <div className="pf-field pf-field--spec">
            <div className="pf-spec-header">
                <span className="pf-label">{t('Specifications')}</span>
                <button type="button" className="pf-spec-add-btn" onClick={() => onChange([...rows, { name: '', value: '' }])}>
                    <i className="las la-plus" /> {t('Add Row')}
                </button>
            </div>
            {rows.length ? (
                rows.map((row, i) => (
                    <div className="pf-spec-row" key={i}>
                        <input className="pf-input" required maxLength={255} placeholder={t('Name')} aria-label={t('Specification :n name', { n: i + 1 })} value={row.name} onChange={(e) => set(i, 'name', e.target.value)} />
                        <input className="pf-input" required maxLength={255} placeholder={t('Value')} aria-label={t('Specification :n value', { n: i + 1 })} value={row.value} onChange={(e) => set(i, 'value', e.target.value)} />
                        <button type="button" className="pf-spec-remove" aria-label={t('Remove row :n', { n: i + 1 })} onClick={() => onChange(rows.filter((_, j) => j !== i))}>
                            <i className="las la-times" />
                        </button>
                    </div>
                ))
            ) : (
                <p className="pf-spec-empty">{t('Click "Add Row" to add product specifications')}</p>
            )}
            {errors.map((m) => (
                <FieldError key={m} message={m} />
            ))}
        </div>
    );
}

/** Meta keywords: type a word and press Enter or comma (replaces select2 "tokenize"). */
function TagInput({ id, value, onChange }: { id: string; value: string[]; onChange: (tags: string[]) => void }) {
    const t = useTrans();
    const [text, setText] = useState('');
    const add = (raw: string) => {
        const words = raw.split(',').map((w) => w.trim()).filter((w) => w && !value.includes(w));
        if (words.length) onChange([...value, ...words]);
        setText('');
    };
    return (
        <div className="pf-tags">
            {value.map((tag) => (
                <span className="pf-tag" key={tag}>
                    {tag}
                    <button type="button" aria-label={t('Remove :name', { name: tag })} onClick={() => onChange(value.filter((x) => x !== tag))}>
                        ×
                    </button>
                </span>
            ))}
            <input
                id={id}
                className="pf-tags__input"
                placeholder={value.length ? '' : t('Type and press enter or comma')}
                value={text}
                maxLength={100}
                onChange={(e) => (e.target.value.includes(',') ? add(e.target.value) : setText(e.target.value))}
                onKeyDown={(e) => {
                    if (e.key === 'Enter') {
                        e.preventDefault();
                        add(text);
                    } else if (e.key === 'Backspace' && !text && value.length) {
                        onChange(value.slice(0, -1));
                    }
                }}
                onBlur={() => text.trim() && add(text)}
            />
        </div>
    );
}

/** Gallery: saved images (remove with ×) and new picks (previewed); at most 10 in all, 3 MB each. */
function Gallery({ saved, files, onRemoveSaved, onFiles }: { saved: GalleryImage[]; files: File[]; onRemoveSaved: (id: number) => void; onFiles: (files: File[]) => void }) {
    const t = useTrans();
    const [notice, setNotice] = useState('');
    const previews = useMemo(() => files.map((f) => URL.createObjectURL(f)), [files]);
    useEffect(() => () => previews.forEach((u) => URL.revokeObjectURL(u)), [previews]);
    const room = MAX_IMAGES - saved.length - files.length;

    const pick = (list: FileList | null) => {
        const chosen = Array.from(list ?? []);
        const ok = chosen.filter((f) => /^image\/(png|jpe?g)$/.test(f.type) && f.size <= MAX_BYTES);
        const kept = ok.slice(0, Math.max(0, room));
        const dropped = chosen.length - kept.length;
        setNotice(dropped ? t(':n file(s) skipped: only PNG/JPG up to 3 MB, at most :max images.', { n: dropped, max: MAX_IMAGES }) : '');
        if (kept.length) onFiles([...files, ...kept]);
    };

    return (
        <div className="pf-gallery">
            <div className="pf-gallery__grid">
                {saved.map((g) => (
                    <figure className="pf-gallery__item" key={`s${g.id}`}>
                        <img src={g.src} alt="" />
                        <button type="button" aria-label={t('Remove image')} onClick={() => onRemoveSaved(g.id)}>
                            ×
                        </button>
                    </figure>
                ))}
                {files.map((f, i) => (
                    <figure className="pf-gallery__item pf-gallery__item--new" key={`n${i}${f.name}`}>
                        <img src={previews[i]} alt="" />
                        <button type="button" aria-label={t('Remove :name', { name: f.name })} onClick={() => onFiles(files.filter((_, j) => j !== i))}>
                            ×
                        </button>
                    </figure>
                ))}
                {room > 0 && (
                    <label className="pf-gallery__add">
                        <i className="las la-cloud-upload-alt" />
                        <span>{t('Add images')}</span>
                        <input type="file" accept=".png,.jpg,.jpeg" multiple onChange={(e) => (pick(e.target.files), (e.target.value = ''))} />
                    </label>
                )}
            </div>
            <p className="pf-hint pf-hint--mt">{t('PNG, JPG, JPEG — max 10 images, 3MB each')}</p>
            {notice && (
                <p className="text--warning small mb-0" role="status">
                    {notice}
                </p>
            )}
        </div>
    );
}

function bonusHint(pct: number, pv: number, pvRate: number) {
    return naira((pct / 100) * pv * pvRate);
}

/** New product: state prices sent with the form. */
function NewStatePrices({ rows, onChange, states, pv, pvRate, errors }: { rows: NewStatePrice[]; onChange: (rows: NewStatePrice[]) => void; states: StateOption[]; pv: number; pvRate: number; errors: string[] }) {
    const t = useTrans();
    const set = (i: number, key: keyof NewStatePrice, value: string) => onChange(rows.map((r, j) => (j === i ? { ...r, [key]: value } : r)));
    const used = rows.map((r) => r.state_id);
    return (
        <div className="pf-card">
            <div className="pf-card-header pf-card-header--state">
                <div className="pf-state-header-left">
                    <i className="las la-map-marker" /> {t('State Prices & Stockist Bonus')}
                </div>
                <button
                    className="pf-state-add-toggle"
                    type="button"
                    disabled={rows.length >= states.length}
                    onClick={() => onChange([...rows, { state_id: '', price: '', mega_stockist_bonus: '', micro_stockist_bonus: '' }])}
                >
                    <i className="las la-plus" /> {t('Add State')}
                </button>
            </div>
            <div className="pf-card-body">
                <p className="pf-hint">
                    {t('The price in each state, and the rebate a Mega or Micro stockist there earns for every unit they redeem: bonus % × product PV × :rate. Stockists can only redeem this product in states listed here.', { rate: naira(pvRate) })}
                </p>
                {rows.map((row, i) => (
                    <StatePriceFields
                        key={i}
                        idPrefix={`sp-${i}`}
                        value={{ state: row.state_id, price: row.price, mega: row.mega_stockist_bonus, micro: row.micro_stockist_bonus }}
                        onChange={(k, v) => set(i, ({ state: 'state_id', price: 'price', mega: 'mega_stockist_bonus', micro: 'micro_stockist_bonus' } as const)[k], v)}
                        states={states.filter((s) => s.id === Number(row.state_id) || !used.includes(String(s.id)))}
                        hint={pv > 0 ? t('1% = :amount per unit', { amount: bonusHint(1, pv, pvRate) }) : null}
                    >
                        <button type="button" className="pf-state-del-btn" title={t('Remove')} aria-label={t('Remove state row :n', { n: i + 1 })} onClick={() => onChange(rows.filter((_, j) => j !== i))}>
                            <i className="las la-trash-alt" />
                        </button>
                    </StatePriceFields>
                ))}
                {errors.map((m) => (
                    <FieldError key={m} message={m} />
                ))}
            </div>
        </div>
    );
}

type PriceFieldKey = 'state' | 'price' | 'mega' | 'micro';

function StatePriceFields({ idPrefix, value, onChange, states, hint, children }: { idPrefix: string; value: Record<PriceFieldKey, string>; onChange: (key: PriceFieldKey, value: string) => void; states: StateOption[]; hint: string | null; children: ReactNode }) {
    const t = useTrans();
    return (
        <div className="pf-state-add-row pf-state-row">
            <div className="pf-field">
                <label className="pf-label" htmlFor={`${idPrefix}-state`}>
                    {t('State')}
                </label>
                <select id={`${idPrefix}-state`} className="pf-input" required value={value.state} onChange={(e) => onChange('state', e.target.value)}>
                    <option value="">{t('Select state')}</option>
                    {states.map((s) => (
                        <option key={s.id} value={s.id}>
                            {s.name} ({s.code})
                        </option>
                    ))}
                </select>
            </div>
            <div className="pf-field">
                <label className="pf-label" htmlFor={`${idPrefix}-price`}>
                    {t('Price (₦)')}
                </label>
                <div className="pf-input-prefix">
                    <span className="pf-prefix">₦</span>
                    <input id={`${idPrefix}-price`} className="pf-input" type="number" step="any" min={0.01} required placeholder="0.00" value={value.price} onChange={(e) => onChange('price', e.target.value)} />
                </div>
            </div>
            {(['mega', 'micro'] as const).map((tier) => (
                <div className="pf-field" key={tier}>
                    <label className="pf-label" htmlFor={`${idPrefix}-${tier}`}>
                        {tier === 'mega' ? t('Mega Stockist Bonus') : t('Micro Stockist Bonus')}
                    </label>
                    <div className="pf-input-suffix">
                        <input id={`${idPrefix}-${tier}`} className="pf-input" type="number" step="0.01" min={0} max={100} placeholder="0" value={value[tier]} onChange={(e) => onChange(tier, e.target.value)} />
                        <span className="pf-suffix">%</span>
                    </div>
                </div>
            ))}
            <div className="pf-state-add-actions">{children}</div>
            {hint && <p className="pf-hint pf-state-row-hint">{hint}</p>}
        </div>
    );
}

/** Existing product: each state price is saved (or removed) on its own. */
function SavedStatePrices({ prices, states, productPrice, pv, pvRate, url, destroyUrl }: { prices: StatePrice[]; states: StateOption[]; productPrice: number; pv: number; pvRate: number; url: string; destroyUrl: string }) {
    const t = useTrans();
    // null: closed; stateId 0: adding
    const [editing, setEditing] = useState<StatePrice | null>(null);
    const form = useForm({ state_id: '', price: '', mega_stockist_bonus: '', micro_stockist_bonus: '' });
    const taken = prices.map((p) => p.stateId);

    const open = (p: StatePrice | null) => {
        form.clearErrors();
        form.setData(p ? { state_id: String(p.stateId), price: String(p.price), mega_stockist_bonus: p.mega ? String(p.mega) : '', micro_stockist_bonus: p.micro ? String(p.micro) : '' } : { state_id: '', price: '', mega_stockist_bonus: '', micro_stockist_bonus: '' });
        setEditing(p ?? { id: 0, stateId: 0, state: '', code: '', price: 0, mega: 0, micro: 0 });
    };
    const save = (e: FormEvent) => {
        e.preventDefault();
        form.post(url, { preserveScroll: true, onSuccess: () => setEditing(null) });
    };
    const choices = editing?.id ? states.filter((s) => s.id === editing.stateId) : states.filter((s) => !taken.includes(s.id));

    return (
        <div className="pf-card pf-state-prices-card">
            <div className="pf-card-header pf-card-header--state">
                <div className="pf-state-header-left">
                    <i className="las la-map-marker" /> {t('State-Specific Prices')}
                </div>
                {!editing && (
                    <button className="pf-state-add-toggle" type="button" disabled={taken.length >= states.length} onClick={() => open(null)}>
                        <i className="las la-plus" /> {t('Add State Price')}
                    </button>
                )}
            </div>
            <div className="pf-card-body">
                {editing && (
                    <form className="pf-state-add-form" onSubmit={save}>
                        <StatePriceFields
                            idPrefix="sp-edit"
                            value={{ state: form.data.state_id, price: form.data.price, mega: form.data.mega_stockist_bonus, micro: form.data.micro_stockist_bonus }}
                            onChange={(k, v) => form.setData(({ state: 'state_id', price: 'price', mega: 'mega_stockist_bonus', micro: 'micro_stockist_bonus' } as const)[k], v)}
                            states={choices}
                            hint={pv > 0 ? t('For this product (PV :pv), 1% = :amount per unit.', { pv, amount: bonusHint(1, pv, pvRate) }) : null}
                        >
                            <button type="submit" className="pf-btn-save" disabled={form.processing}>
                                {editing.id ? t('Update') : t('Save')}
                            </button>
                            <button type="button" className="pf-btn-cancel" onClick={() => setEditing(null)}>
                                {t('Cancel')}
                            </button>
                        </StatePriceFields>
                        {Object.values(form.errors).map((m) => (
                            <FieldError key={m} message={m} />
                        ))}
                    </form>
                )}
                {prices.length ? (
                    <div className="table-responsive">
                        <table className="pf-state-table">
                            <thead>
                                <tr>
                                    <th>{t('State')}</th>
                                    <th>{t('Code')}</th>
                                    <th>{t('State Price')}</th>
                                    <th>{t('vs Default')}</th>
                                    <th>{t('Stockist Bonus')}</th>
                                    <th aria-label={t('Actions')} />
                                </tr>
                            </thead>
                            <tbody>
                                {prices.map((sp) => {
                                    const diff = sp.price - productPrice;
                                    return (
                                        <tr key={sp.id}>
                                            <td className="pf-state-name">{sp.state}</td>
                                            <td>
                                                <span className="pf-state-code-chip">{sp.code}</span>
                                            </td>
                                            <td className="pf-state-price">{naira(sp.price)}</td>
                                            <td>
                                                {diff > 0 ? <span className="pf-diff pf-diff-up">+{naira(diff)}</span> : diff < 0 ? <span className="pf-diff pf-diff-down">-{naira(-diff)}</span> : <span className="pf-diff pf-diff-same">{t('Same')}</span>}
                                            </td>
                                            <td>
                                                {sp.mega > 0 || sp.micro > 0 ? (
                                                    (
                                                        [
                                                            ['Mega', sp.mega],
                                                            ['Micro', sp.micro],
                                                        ] as const
                                                    ).map(([tier, pct]) => (
                                                        <div className="pf-state-bonus-line" key={tier}>
                                                            <span className="pf-state-bonus-tier">{t(tier)}</span>
                                                            {pct > 0 ? (
                                                                <>
                                                                    <span className="pf-state-bonus">{pct}%</span>
                                                                    <span className="pf-state-bonus-unit">≈ {bonusHint(pct, pv, pvRate)} / {t('unit')}</span>
                                                                </>
                                                            ) : (
                                                                <span className="pf-state-bonus-unset">{t('Not set')}</span>
                                                            )}
                                                        </div>
                                                    ))
                                                ) : (
                                                    <span className="pf-state-bonus-unset" title={t('Stockists in this state earn no bonus on this product')}>
                                                        {t('Not set')}
                                                    </span>
                                                )}
                                            </td>
                                            <td className="text-right">
                                                <div className="pf-state-actions">
                                                    <button type="button" className="pf-state-edit-btn" title={t('Edit')} aria-label={t('Edit :name', { name: sp.state })} onClick={() => open(sp)}>
                                                        <i className="las la-pen" />
                                                    </button>
                                                    <ConfirmButton url={withId(destroyUrl, sp.id)} method="delete" className="pf-state-del-btn" title={t('Remove :name', { name: sp.state })} question={t('Remove state price for :name?', { name: sp.state })}>
                                                        <i className="las la-trash-alt" />
                                                    </ConfirmButton>
                                                </div>
                                            </td>
                                        </tr>
                                    );
                                })}
                            </tbody>
                        </table>
                    </div>
                ) : (
                    !editing && <p className="pf-hint mb-0">{t('No state prices yet: stockists cannot redeem this product anywhere until one is added.')}</p>
                )}
            </div>
        </div>
    );
}
