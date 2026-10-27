import { Link, router, useForm } from '@inertiajs/react';
import { useEffect, useRef, useState, type FormEvent, type ReactNode } from 'react';
import type { AdminUser } from '@/Components/Admin/Cells';
import LinkWidget, { type WidgetData } from '@/Components/Admin/LinkWidget';
import PageHeader from '@/Components/Admin/PageHeader';
import ConfirmDialog from '@/Components/ConfirmDialog';
import Modal from '@/Components/Modal';
import { useTrans } from '@/lib/i18n';
import './Detail.css';

type Member = {
    id: number;
    fullname: string;
    username: string;
    email: string;
    mobile: string;
    trx: string;
    state: string;
    address: string;
    joined: string;
    image: string;
    project: { title: string; color: string; icon: string } | null;
    sponsor: AdminUser;
    active: boolean;
    banReason: string | null;
    withdrawalBlocked: boolean;
    withdrawalBlockReason: string | null;
    ambassador: boolean;
    hasKyc: boolean;
    balance: number;
    balanceLabel: string;
    productWalletLabel: string;
};

type EditForm = {
    firstname: string;
    lastname: string;
    email: string;
    bname: string;
    aname: string;
    ano: string;
    mobile: string;
    address: string;
    city: string;
    state: string;
    zip: string;
    country: string;
    ev: boolean;
    sv: boolean;
    ts: boolean;
    kv: boolean;
};

type Can = Record<'edit' | 'sponsor' | 'username' | 'password' | 'wallets' | 'status' | 'withdrawal' | 'ambassador' | 'buyShares' | 'loginAs', boolean>;

type Props = {
    title: string;
    member: Member;
    widgets: {
        main: WidgetData[];
        wallets: WidgetData[];
        pv: { position: string | null; positionClass: string; widgets: WidgetData[] } | null;
    };
    form: EditForm;
    countries: { code: string; name: string; dial: string }[];
    plans: { id: number; name: string; price: number; priceLabel: string }[];
    currency: string;
    sponsorChangeCount: number;
    links: Record<'logins' | 'notifications' | 'welcomePacks' | 'kyc' | 'tree' | 'referrals' | 'sponsorHistory' | 'loginAs', string>;
    urls: Record<'update' | 'sponsor' | 'username' | 'password' | 'balance' | 'visa' | 'productWallet' | 'status' | 'withdrawal' | 'ambassador' | 'buyShares', string>;
    can: Can;
};

type WalletKind = 'balance' | 'visa' | 'productWallet';
type Dialog =
    | { kind: 'wallet'; wallet: WalletKind; act: 'add' | 'sub' }
    | { kind: 'status' }
    | { kind: 'withdrawal' }
    | { kind: 'ambassador' }
    | { kind: 'shares' }
    | { kind: 'kycOff' }
    | null;

const WALLET_NAMES: Record<WalletKind, string> = { balance: 'Money Box', visa: 'VISA wallet', productWallet: 'Re-purchase wallet' };

/**
 * Manage Users → member (/admin/users/detail/{id}): balances, quick links,
 * account controls and the edit forms. Buttons appear only for actions this
 * admin may use (`can`, from AdminAccessMiddleware).
 */
export default function UserDetail(props: Props) {
    const t = useTrans();
    const { member, can, links, urls } = props;
    const [dialog, setDialog] = useState<Dialog>(null);
    const close = () => setDialog(null);
    // Turning KYC off deletes the member's documents: asked once more before saving.
    const kycOffSubmit = useRef<(() => void) | null>(null);

    return (
        <div className="page-user-detail">
            <PageHeader title={props.title}>
                {can.loginAs && (
                    <a className="btn btn-sm btn-outline--primary" href={links.loginAs} target="_blank" rel="noopener noreferrer">
                        <i className="las la-sign-in-alt" />
                        {t('Login as User')}
                    </a>
                )}
            </PageHeader>

            {can.ambassador && (
                <div className="mb-3">
                    {member.ambassador ? (
                        <button type="button" className="btn btn-sm btn--warning btn--shadow udb-ambassador" onClick={() => setDialog({ kind: 'ambassador' })}>
                            <i className="las la-star" /> {t('Ambassador')} <span className="badge bg-white text-warning ms-1">{t('Active · Click to remove')}</span>
                        </button>
                    ) : (
                        <button type="button" className="btn btn-sm btn--outline--warning btn--shadow udb-ambassador" onClick={() => setDialog({ kind: 'ambassador' })}>
                            <i className="las la-star" /> {t('Tag as Ambassador')}
                        </button>
                    )}
                </div>
            )}

            <div className="row gy-4">
                {props.widgets.main.map((w) => (
                    <div className="col-6 col-xxl-3 col-lg-4" key={w.title}>
                        <LinkWidget widget={w} />
                    </div>
                ))}

                <div className="col-12 mt-2 mb-0">
                    <p className="udb-section-label">
                        <i className="las la-sitemap me-1" /> {t('MLM & Re-purchase wallets')}
                        {member.project && (
                            <>
                                {' · '}
                                <span className="badge" style={{ background: member.project.color }}>
                                    <i className={member.project.icon} /> {member.project.title}
                                </span>
                            </>
                        )}
                    </p>
                </div>
                {props.widgets.wallets.map((w) => (
                    <div className="col-6 col-xxl-3 col-lg-4" key={w.title}>
                        <LinkWidget widget={w} />
                    </div>
                ))}

                {props.widgets.pv && (
                    <>
                        <div className="col-12 mt-2 mb-0">
                            <p className="udb-section-label">
                                <i className="las la-project-diagram me-1" /> {t('Binary Tree PV')}
                                {props.widgets.pv.position && (
                                    <>
                                        {' · '}
                                        <span className={`badge bg-${props.widgets.pv.positionClass}`}>{t(':side leg', { side: props.widgets.pv.position })}</span>
                                    </>
                                )}
                            </p>
                        </div>
                        {props.widgets.pv.widgets.map((w) => (
                            <div className="col-6 col-xxl-3 col-lg-4" key={w.title}>
                                <LinkWidget widget={w} />
                            </div>
                        ))}
                    </>
                )}
            </div>

            <div className="udb-toolbar mt-4">
                {can.wallets && (
                    <div className="udb-group">
                        <p className="udb-group-label">
                            <i className="las la-wallet" />
                            {t('Wallet Adjustments')}
                        </p>
                        <div className="udb-group-row">
                            {(['balance', 'visa', 'productWallet'] as WalletKind[]).flatMap((wallet) =>
                                (['add', 'sub'] as const).map((act) => (
                                    <button
                                        key={wallet + act}
                                        type="button"
                                        className={`btn ${act === 'add' ? 'btn--success' : 'btn--danger'} btn--shadow udb-btn`}
                                        onClick={() => setDialog({ kind: 'wallet', wallet, act })}
                                    >
                                        <i className={act === 'add' ? 'las la-plus-circle' : 'las la-minus-circle'} /> {t(wallet === 'balance' ? 'Money Box' : wallet === 'visa' ? 'VISA Wallet' : 'Re-purchase Wallet')}
                                    </button>
                                )),
                            )}
                        </div>
                    </div>
                )}

                <div className="udb-group">
                    <p className="udb-group-label">
                        <i className="las la-link" />
                        {t('Quick Links')}
                    </p>
                    <div className="udb-group-row">
                        <Link className="btn btn--primary btn--shadow udb-btn" href={links.logins}>
                            <i className="las la-list-alt" />
                            {t('Logins')}
                        </Link>
                        <Link className="btn btn--secondary btn--shadow udb-btn" href={links.notifications}>
                            <i className="las la-bell" />
                            {t('Notifications')}
                        </Link>
                        <a className="btn btn--secondary btn--shadow udb-btn" href={links.welcomePacks}>
                            <i className="las la-gift" />
                            {t('Welcome Packages')}
                        </a>
                        {member.hasKyc && (
                            <a className="btn btn--dark btn--shadow udb-btn" href={links.kyc} target="_blank" rel="noopener noreferrer">
                                <i className="las la-user-check" />
                                {t('KYC Data')}
                            </a>
                        )}
                        <a className="btn btn--primary btn--shadow udb-btn" href={links.tree}>
                            <i className="las la-project-diagram" />
                            {t('User Tree')}
                        </a>
                        <Link className="btn btn--info btn--shadow udb-btn" href={links.referrals}>
                            <i className="las la-users" />
                            {t('User Referrals')}
                        </Link>
                    </div>
                </div>

                {(can.status || can.withdrawal || can.buyShares) && (
                    <div className="udb-group">
                        <p className="udb-group-label">
                            <i className="las la-shield-alt" />
                            {t('Account Controls')}
                        </p>
                        <div className="udb-group-row">
                            {can.status &&
                                (member.active ? (
                                    <button className="btn btn--warning btn--shadow udb-btn" type="button" onClick={() => setDialog({ kind: 'status' })}>
                                        <i className="las la-ban" />
                                        {t('Ban User')}
                                    </button>
                                ) : (
                                    <button className="btn btn--success btn--shadow udb-btn" type="button" onClick={() => setDialog({ kind: 'status' })}>
                                        <i className="las la-undo" />
                                        {t('Unban User')}
                                    </button>
                                ))}
                            {can.withdrawal &&
                                (member.withdrawalBlocked ? (
                                    <button className="btn btn--success btn--shadow udb-btn" type="button" onClick={() => setDialog({ kind: 'withdrawal' })}>
                                        <i className="las la-lock-open" /> {t('Unblock Withdrawal')}
                                    </button>
                                ) : (
                                    <button className="btn btn--danger btn--shadow udb-btn" type="button" onClick={() => setDialog({ kind: 'withdrawal' })}>
                                        <i className="las la-lock" /> {t('Block Withdrawal')}
                                    </button>
                                ))}
                            {can.buyShares && (
                                <button className="btn btn--primary btn--shadow udb-btn udb-btn-cta" type="button" onClick={() => setDialog({ kind: 'shares' })}>
                                    <i className="las la-chart-pie" />
                                    {t('Buy Shares')}
                                </button>
                            )}
                        </div>
                    </div>
                )}
            </div>

            <div className="row">
                <div className="col-12 col-md-5 col-xxl-4">
                    <ProfileCard member={member} />
                    {can.sponsor && <SponsorCard member={member} url={urls.sponsor} historyUrl={links.sponsorHistory} changes={props.sponsorChangeCount} />}
                    {can.username && <UsernameCard username={member.username} url={urls.username} />}
                </div>
                <div className="col-12 col-md-7 col-xxl-8">
                    {can.edit && <EditCard fullname={member.fullname} initial={props.form} countries={props.countries} url={urls.update} onKycOff={(submit) => { kycOffSubmit.current = submit; setDialog({ kind: 'kycOff' }); }} />}
                    {can.password && <PasswordCard url={urls.password} />}
                </div>
            </div>

            {dialog?.kind === 'wallet' && <WalletDialog {...dialog} url={urls[dialog.wallet]} currency={props.currency} productWalletLabel={member.productWalletLabel} onClose={close} />}
            {dialog?.kind === 'status' && <StatusDialog member={member} url={urls.status} onClose={close} />}
            {dialog?.kind === 'withdrawal' && <WithdrawalDialog member={member} url={urls.withdrawal} onClose={close} />}
            {dialog?.kind === 'shares' && <SharesDialog member={member} plans={props.plans} currency={props.currency} url={urls.buyShares} onClose={close} />}
            <ConfirmDialog
                open={dialog?.kind === 'ambassador'}
                question={member.ambassador ? t('Remove Ambassador status from :name?', { name: member.fullname }) : t('Tag :name as Ambassador?', { name: member.fullname })}
                onConfirm={() => router.post(urls.ambassador, { state: member.ambassador ? 'off' : 'on' }, { preserveScroll: true, onFinish: close })}
                onCancel={close}
            />
            <ConfirmDialog
                open={dialog?.kind === 'kycOff'}
                question={t('Turning KYC off deletes the documents this member submitted. Continue?')}
                onConfirm={() => {
                    kycOffSubmit.current?.();
                    close();
                }}
                onCancel={close}
            />
        </div>
    );
}


function Item({ label, children }: { label: string; children: ReactNode }) {
    return (
        <li className="list-group-item rounded-0 d-flex justify-content-between gap-2">
            <span>{label}</span>
            <span className="text-end">{children}</span>
        </li>
    );
}

function FieldError({ message }: { message?: string }) {
    return message ? <small className="text--danger d-block mt-1">{message}</small> : null;
}

function ProfileCard({ member }: { member: Member }) {
    const t = useTrans();
    return (
        <div className="card mt-30">
            <div className="card-body p-0">
                <div className="bg--white p-3">
                    <div className="profile-info-top">
                        <div className="profile-image">
                            <img src={member.image} alt="" />
                        </div>
                    </div>
                    <ul className="list-group mt-3">
                        <Item label={t('Project')}>{member.project?.title ?? '—'}</Item>
                        <Item label={t('Name')}>{member.fullname}</Item>
                        <Item label={t('Username')}>{member.username}</Item>
                        <Item label={t('TRX')}>{member.trx}</Item>
                        <Item label={t('Email')}>{member.email}</Item>
                        <Item label={t('Mobile Number')}>{member.mobile}</Item>
                        <Item label={t('Ref By')}>{member.sponsor ? <Link href={member.sponsor.url}>@{member.sponsor.username}</Link> : t('N/A')}</Item>
                        <Item label={t('State')}>{member.state}</Item>
                        <Item label={t('Address')}>{member.address}</Item>
                        <Item label={t('Joined at')}>{member.joined}</Item>
                    </ul>
                </div>
            </div>
        </div>
    );
}

function SponsorCard({ member, url, historyUrl, changes }: { member: Member; url: string; historyUrl: string; changes: number }) {
    const t = useTrans();
    const form = useForm({ nsponsor: '' });
    const submit = (e: FormEvent) => {
        e.preventDefault();
        form.post(url, { preserveScroll: true, onSuccess: () => form.reset() });
    };
    return (
        <div className="card mt-30">
            <div className="card-header">
                <h5 className="card-title mb-0">
                    {t('Current Sponsor')} {member.sponsor ? `@${member.sponsor.username}` : t('N/A')}
                </h5>
            </div>
            <div className="card-body">
                <form onSubmit={submit}>
                    <div className="form-group">
                        <label htmlFor="new-sponsor">{t('New sponsor')}</label>
                        <input id="new-sponsor" className="form-control" type="text" required value={form.data.nsponsor} onChange={(e) => form.setData('nsponsor', e.target.value)} placeholder={t('Username')} />
                        <FieldError message={form.errors.nsponsor} />
                    </div>
                    <button className="btn btn--primary w-100 h-45" type="submit" disabled={form.processing || !form.data.nsponsor.trim()}>
                        {t('Submit')}
                    </button>
                    {changes > 0 && (
                        <div className="mt-3 text-center">
                            <Link className="btn btn-sm btn-outline--dark" href={historyUrl}>
                                <i className="las la-history" /> {t('View Sponsor History')} <span className="badge badge--dark ms-1">{changes}</span>
                            </Link>
                        </div>
                    )}
                </form>
            </div>
        </div>
    );
}

function UsernameCard({ username, url }: { username: string; url: string }) {
    const t = useTrans();
    const form = useForm({ nusername: '' });
    const submit = (e: FormEvent) => {
        e.preventDefault();
        form.post(url, { preserveScroll: true, onSuccess: () => form.reset() });
    };
    return (
        <div className="card mt-30">
            <div className="card-header">
                <h5 className="card-title mb-0">{t('Change Username')}</h5>
            </div>
            <div className="card-body">
                <form onSubmit={submit}>
                    <div className="form-group">
                        <label htmlFor="current-username">{t('Current Username')}</label>
                        <input id="current-username" className="form-control" type="text" value={username} readOnly />
                    </div>
                    <div className="form-group">
                        <label htmlFor="new-username">{t('New username')}</label>
                        <input id="new-username" className="form-control" type="text" required value={form.data.nusername} onChange={(e) => form.setData('nusername', e.target.value)} />
                        <small className="text-muted">{t('At least 6 characters: letters, numbers and underscore.')}</small>
                        <FieldError message={form.errors.nusername} />
                    </div>
                    <button className="btn btn--primary w-100 h-45" type="submit" disabled={form.processing || !form.data.nusername.trim()}>
                        {t('Submit')}
                    </button>
                </form>
            </div>
        </div>
    );
}

function EditCard({ fullname, initial, countries, url, onKycOff }: { fullname: string; initial: EditForm; countries: Props['countries']; url: string; onKycOff: (submit: () => void) => void }) {
    const t = useTrans();
    const form = useForm<EditForm>(initial);
    const { data, setData, errors } = form;
    const dial = countries.find((c) => c.code === data.country)?.dial ?? '';

    // After a save the server sends the stored values back: show them.
    useEffect(() => {
        form.setDefaults(initial);
        form.reset();
    }, [initial]); // eslint-disable-line react-hooks/exhaustive-deps

    const send = () => form.post(url, { preserveScroll: true });
    const submit = (e: FormEvent) => {
        e.preventDefault();
        if (initial.kv && !data.kv) onKycOff(send);
        else send();
    };

    const text = (name: keyof EditForm, label: string, opts: { col?: string; required?: boolean; inputMode?: 'numeric' | 'email' | 'text' } = {}) => (
        <div className={opts.col ?? 'col-md-6'}>
            <div className="form-group">
                <label htmlFor={`edit-${name}`}>{label}</label>
                <input
                    id={`edit-${name}`}
                    className="form-control"
                    type={opts.inputMode === 'email' ? 'email' : 'text'}
                    inputMode={opts.inputMode === 'numeric' ? 'numeric' : undefined}
                    required={opts.required}
                    value={data[name] as string}
                    onChange={(e) => setData(name, e.target.value as never)}
                />
                <FieldError message={errors[name]} />
            </div>
        </div>
    );

    const toggle = (name: 'ev' | 'sv' | 'ts' | 'kv', label: string, on: string, off: string) => (
        <div className="col-sm-6 col-xxl-3">
            <div className="form-group">
                <span className="d-block mb-2">{label}</span>
                <div className="form-check form-switch udb-switch">
                    <input className="form-check-input" type="checkbox" role="switch" id={`edit-${name}`} checked={data[name]} onChange={(e) => setData(name, e.target.checked)} />
                    <label className="form-check-label" htmlFor={`edit-${name}`}>
                        {data[name] ? on : off}
                    </label>
                </div>
            </div>
        </div>
    );

    return (
        <div className="card mt-30">
            <div className="card-header">
                <h5 className="card-title mb-0">{t('Information of :name', { name: fullname })}</h5>
            </div>
            <div className="card-body">
                {/* The Blade form had the first and last name boxes twice. */}
                <form onSubmit={submit} noValidate>
                    <div className="row">
                        {text('firstname', t('First Name'), { required: true })}
                        {text('lastname', t('Last Name'), { required: true })}
                        {text('email', t('Email'), { required: true, inputMode: 'email' })}
                        <div className="col-md-6">
                            <div className="form-group">
                                <label htmlFor="edit-mobile">{t('Mobile Number')}</label>
                                <div className="input-group">
                                    <span className="input-group-text mobile-code">+{dial}</span>
                                    <input id="edit-mobile" className="form-control" type="text" inputMode="numeric" required value={data.mobile} onChange={(e) => setData('mobile', e.target.value)} />
                                </div>
                                <FieldError message={errors.mobile} />
                            </div>
                        </div>
                        {/* Text, not number: account numbers keep their leading zeros. */}
                        {text('bname', t('Bank Name'))}
                        {text('aname', t('Account name'))}
                        {text('ano', t('Account number'), { inputMode: 'numeric' })}
                        {text('address', t('Address'), { col: 'col-md-6' })}
                        {text('city', t('City'), { col: 'col-sm-6 col-xl-6 col-xxl-3' })}
                        {text('state', t('State'), { col: 'col-sm-6 col-xl-6 col-xxl-3' })}
                        {text('zip', t('Zip/Postal'), { col: 'col-sm-6 col-xl-6 col-xxl-3' })}
                        <div className="col-sm-6 col-xl-6 col-xxl-3">
                            <div className="form-group">
                                <label htmlFor="edit-country">
                                    {t('Country')} <span className="text--danger">*</span>
                                </label>
                                <select id="edit-country" className="form-control" value={data.country} onChange={(e) => setData('country', e.target.value)}>
                                    {countries.map((c) => (
                                        <option key={c.code} value={c.code}>
                                            {c.name}
                                        </option>
                                    ))}
                                </select>
                                <FieldError message={errors.country} />
                            </div>
                        </div>
                        {toggle('ev', t('Email Verification'), t('Verified'), t('Unverified'))}
                        {toggle('sv', t('Mobile Verification'), t('Verified'), t('Unverified'))}
                        {toggle('ts', t('2FA Verification'), t('Enable'), t('Disable'))}
                        {toggle('kv', t('KYC'), t('Verified'), t('Unverified'))}
                        <div className="col-md-12">
                            <button className="btn btn--primary w-100 h-45" type="submit" disabled={form.processing}>
                                {t('Submit')}
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    );
}

function PasswordCard({ url }: { url: string }) {
    const t = useTrans();
    const form = useForm({ password: '', password_confirmation: '' });
    const submit = (e: FormEvent) => {
        e.preventDefault();
        form.post(url, { preserveScroll: true, onFinish: () => form.reset() });
    };
    return (
        <div className="card mt-30">
            <div className="card-body">
                <h5 className="card-title mb-4 border-bottom pb-2">{t('Change Password')}</h5>
                <form onSubmit={submit}>
                    <div className="form-group">
                        <label htmlFor="member-password">{t('New Password')}</label>
                        <input id="member-password" className="form-control" type="password" autoComplete="new-password" required value={form.data.password} onChange={(e) => form.setData('password', e.target.value)} />
                        <FieldError message={form.errors.password} />
                    </div>
                    <div className="form-group">
                        <label htmlFor="member-password-confirm">{t('Confirm Password')}</label>
                        <input
                            id="member-password-confirm"
                            className="form-control"
                            type="password"
                            autoComplete="new-password"
                            required
                            value={form.data.password_confirmation}
                            onChange={(e) => form.setData('password_confirmation', e.target.value)}
                        />
                    </div>
                    <button type="submit" className="btn btn--primary w-100 btn-lg h-45" disabled={form.processing}>
                        {t('Submit')}
                    </button>
                </form>
            </div>
        </div>
    );
}

function DialogHeader({ id, title, onClose }: { id: string; title: ReactNode; onClose: () => void }) {
    const t = useTrans();
    return (
        <div className="modal-header">
            <h5 className="modal-title" id={id}>
                {title}
            </h5>
            <button type="button" className="close" aria-label={t('Close')} onClick={onClose}>
                <i className="las la-times" />
            </button>
        </div>
    );
}

function WalletDialog({ wallet, act, url, currency, productWalletLabel, onClose }: { wallet: WalletKind; act: 'add' | 'sub'; url: string; currency: string; productWalletLabel: string; onClose: () => void }) {
    const t = useTrans();
    const form = useForm({ act, amount: '', remark: '' });
    const submit = (e: FormEvent) => {
        e.preventDefault();
        form.post(url, { preserveScroll: true, onSuccess: onClose });
    };
    const verb = wallet === 'productWallet' ? (act === 'add' ? t('Credit') : t('Debit')) : act === 'add' ? t('Add') : t('Subtract');
    return (
        <Modal open onClose={onClose} labelledBy="wallet-title" staticBackdrop={form.processing}>
            <DialogHeader id="wallet-title" title={`${verb} ${t(WALLET_NAMES[wallet])}`} onClose={onClose} />
            <form onSubmit={submit}>
                <div className="modal-body">
                    {wallet === 'productWallet' && (
                        <div className="alert alert-info py-2 px-3 mb-3 small" role="alert">
                            <i className="las la-info-circle" /> {t('Current balance:')} <strong>{productWalletLabel}</strong> — {t('Used exclusively for product purchases.')}
                        </div>
                    )}
                    <div className="form-group">
                        <label htmlFor="wallet-amount">{t('Amount')}</label>
                        <div className="input-group">
                            <input id="wallet-amount" className="form-control" type="number" step="any" min="0.01" required placeholder={t('Please provide positive amount')} value={form.data.amount} onChange={(e) => form.setData('amount', e.target.value)} />
                            <div className="input-group-text">{currency}</div>
                        </div>
                        <FieldError message={form.errors.amount} />
                    </div>
                    <div className="form-group">
                        <label htmlFor="wallet-remark">{t('Remark')}</label>
                        <textarea id="wallet-remark" className="form-control" rows={4} maxLength={255} required placeholder={t('Reason for this adjustment')} value={form.data.remark} onChange={(e) => form.setData('remark', e.target.value)} />
                        <FieldError message={form.errors.remark} />
                    </div>
                </div>
                <div className="modal-footer">
                    {/* One click only: the old form relied on a jQuery "disableSubmission" guard. */}
                    <button className="btn btn--primary h-45 w-100" type="submit" disabled={form.processing || !(parseFloat(form.data.amount) > 0) || !form.data.remark.trim()}>
                        {t('Submit')}
                    </button>
                </div>
            </form>
        </Modal>
    );
}

function StatusDialog({ member, url, onClose }: { member: Member; url: string; onClose: () => void }) {
    const t = useTrans();
    const form = useForm({ state: member.active ? 'ban' : 'unban', reason: '' });
    const submit = (e: FormEvent) => {
        e.preventDefault();
        form.post(url, { preserveScroll: true, onSuccess: onClose });
    };
    return (
        <Modal open onClose={onClose} labelledBy="status-title">
            <DialogHeader id="status-title" title={member.active ? t('Ban User') : t('Unban User')} onClose={onClose} />
            <form onSubmit={submit}>
                <div className="modal-body">
                    {member.active ? (
                        <>
                            <h6 className="mb-2">{t("If you ban this user they won't be able to access their dashboard.")}</h6>
                            <div className="form-group">
                                <label htmlFor="ban-reason">{t('Reason')}</label>
                                <textarea id="ban-reason" className="form-control" rows={4} maxLength={255} required value={form.data.reason} onChange={(e) => form.setData('reason', e.target.value)} />
                                <FieldError message={form.errors.reason} />
                            </div>
                        </>
                    ) : (
                        <>
                            <p>{t('Ban reason was')}:</p>
                            <p className="admin-notify-text">{member.banReason}</p>
                            <h4 className="mt-3 text-center">{t('Are you sure to unban this user?')}</h4>
                        </>
                    )}
                </div>
                <div className="modal-footer">
                    {member.active ? (
                        <button className="btn btn--primary h-45 w-100" type="submit" disabled={form.processing || !form.data.reason.trim()}>
                            {t('Submit')}
                        </button>
                    ) : (
                        <>
                            <button className="btn btn--dark" type="button" onClick={onClose}>
                                {t('No')}
                            </button>
                            <button className="btn btn--primary" type="submit" disabled={form.processing}>
                                {t('Yes')}
                            </button>
                        </>
                    )}
                </div>
            </form>
        </Modal>
    );
}

function WithdrawalDialog({ member, url, onClose }: { member: Member; url: string; onClose: () => void }) {
    const t = useTrans();
    const blocked = member.withdrawalBlocked;
    const form = useForm({ state: blocked ? 'unblock' : 'block', reason: '' });
    const submit = (e: FormEvent) => {
        e.preventDefault();
        form.post(url, { preserveScroll: true, onSuccess: onClose });
    };
    return (
        <Modal open onClose={onClose} labelledBy="withdrawal-title">
            <DialogHeader
                id="withdrawal-title"
                title={
                    blocked ? (
                        <>
                            <i className="las la-lock-open text-success me-1" /> {t('Unblock Withdrawal Access')}
                        </>
                    ) : (
                        <>
                            <i className="las la-lock text-danger me-1" /> {t('Block Withdrawal Access')}
                        </>
                    )
                }
                onClose={onClose}
            />
            <form onSubmit={submit}>
                <div className="modal-body">
                    {blocked ? (
                        <>
                            <div className="alert alert-warning">
                                <strong>{t('Current block reason:')}</strong>
                                <br />
                                <span className="admin-notify-text">{member.withdrawalBlockReason}</span>
                            </div>
                            <h5 className="text-center mt-3">{t("Restore this user's withdrawal access?")}</h5>
                        </>
                    ) : (
                        <>
                            <p className="mb-2">{t('This will prevent the user from submitting any withdrawal request until you unblock them.')}</p>
                            <div className="form-group">
                                <label htmlFor="block-reason">
                                    {t('Reason')} <span className="text-danger">*</span>
                                </label>
                                <textarea id="block-reason" className="form-control" rows={4} maxLength={500} required value={form.data.reason} onChange={(e) => form.setData('reason', e.target.value)} placeholder={t('Provide a reason for blocking withdrawals...')} />
                                <FieldError message={form.errors.reason} />
                            </div>
                        </>
                    )}
                </div>
                <div className="modal-footer">
                    {blocked ? (
                        <>
                            <button className="btn btn--dark" type="button" onClick={onClose}>
                                {t('Cancel')}
                            </button>
                            <button className="btn btn--success" type="submit" disabled={form.processing}>
                                {t('Yes, Unblock')}
                            </button>
                        </>
                    ) : (
                        <button className="btn btn--danger h-45 w-100" type="submit" disabled={form.processing || !form.data.reason.trim()}>
                            {t('Block Withdrawal')}
                        </button>
                    )}
                </div>
            </form>
        </Modal>
    );
}

const QUICK = [5, 10, 20, 50, 100];
const money = (n: number) => n.toLocaleString('en-NG', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

/** Buy Shares (super-admins): allocates plan units from the member's Money Box. */
function SharesDialog({ member, plans, currency, url, onClose }: { member: Member; plans: Props['plans']; currency: string; url: string; onClose: () => void }) {
    const t = useTrans();
    const form = useForm({ plan_id: plans[0] ? String(plans[0].id) : '', quantity: 1, force: false });
    const plan = plans.find((p) => String(p.id) === form.data.plan_id) ?? null;
    const qty = Math.max(1, Math.trunc(Number(form.data.quantity)) || 1);
    const total = plan ? plan.price * qty : 0;
    const sufficient = member.balance >= total;
    const setQty = (n: number) => form.setData('quantity', Math.max(1, Math.trunc(n) || 1));

    // Enough balance again (plan or quantity changed): untick "Force".
    useEffect(() => {
        if (sufficient && form.data.force) form.setData('force', false);
    }, [sufficient]); // eslint-disable-line react-hooks/exhaustive-deps

    const submit = () => form.post(url, { preserveScroll: true, onSuccess: onClose });

    return (
        <Modal open onClose={onClose} labelledBy="shares-title" contentClassName="buy-shares-modal" staticBackdrop={form.processing}>
            <div className="bsm-header">
                <div className="bsm-header-icon">
                    <i className="las la-chart-pie" />
                </div>
                <div>
                    <h5 className="bsm-title" id="shares-title">
                        {t('Buy Shares')}
                    </h5>
                    <p className="bsm-subtitle mb-0">
                        {t('Allocate shares to')} <strong>{member.username}</strong>
                    </p>
                </div>
                <button type="button" className="bsm-close ms-auto" aria-label={t('Close')} onClick={onClose}>
                    <i className="las la-times" />
                </button>
            </div>
            <div className="bsm-balance-bar">
                <i className="las la-wallet me-2" />
                <span>{t('User Balance:')}</span>
                <strong className="ms-1">{member.balanceLabel}</strong>
            </div>
            <div className="modal-body px-4 pb-2 pt-3">
                <div className="mb-3">
                    <label className="bsm-label" htmlFor="bsm-plan">
                        {t('Select Plan')}
                    </label>
                    <select id="bsm-plan" className="form-select bsm-select" value={form.data.plan_id} onChange={(e) => form.setData('plan_id', e.target.value)}>
                        {plans.length === 0 && <option value="">{t('— No active plan —')}</option>}
                        {plans.map((p) => (
                            <option key={p.id} value={p.id}>
                                {p.name}
                            </option>
                        ))}
                    </select>
                </div>
                <div className="mb-3">
                    <span className="bsm-label">
                        <i className="las la-bolt me-1" />
                        {t('Quick Action')}
                    </span>
                    <div className="bsm-quick-actions">
                        {QUICK.map((n) => (
                            <button key={n} type="button" className={`bsm-quick-btn ${qty === n ? 'active' : ''}`} onClick={() => setQty(n)}>
                                <span className="bsm-quick-num">{n}</span>
                                <span className="bsm-quick-unit">{t('units')}</span>
                                <i className="las la-check-circle bsm-quick-check" />
                            </button>
                        ))}
                    </div>
                </div>
                <div className="mb-3">
                    <label className="bsm-label" htmlFor="bsm-qty">
                        {t('Number of Units')}
                    </label>
                    <div className="bsm-qty-group">
                        <button type="button" className="bsm-qty-btn" aria-label={t('Fewer')} onClick={() => setQty(qty - 1)} disabled={qty <= 1}>
                            <i className="las la-minus" />
                        </button>
                        <input id="bsm-qty" type="number" className="form-control bsm-qty-input text-center" min={1} step={1} value={form.data.quantity} onChange={(e) => form.setData('quantity', e.target.value === '' ? ('' as unknown as number) : Number(e.target.value))} onBlur={() => setQty(Number(form.data.quantity))} />
                        <button type="button" className="bsm-qty-btn" aria-label={t('More')} onClick={() => setQty(qty + 1)}>
                            <i className="las la-plus" />
                        </button>
                    </div>
                    <p className="bsm-hint mt-1 mb-0">
                        <i className="las la-info-circle me-1" />
                        {t('Minimum purchase is 1 unit.')}
                    </p>
                </div>
                {plan && (
                    <div className="bsm-total-card">
                        <div className="bsm-calc-row">
                            <span className="bsm-calc-label">{t('Unit Price')}</span>
                            <span className="bsm-calc-value">{money(plan.price)}</span>
                        </div>
                        <div className="bsm-calc-row">
                            <span className="bsm-calc-label">× {t('Quantity')}</span>
                            <span className="bsm-calc-value">{qty}</span>
                        </div>
                        <div className="bsm-calc-divider" />
                        <div className="bsm-calc-row bsm-calc-total">
                            <span className="bsm-calc-label">{t('Total Cost')}</span>
                            <span className="bsm-calc-value text--primary fw-bold" id="bsm-total">
                                {money(total)} {currency}
                            </span>
                        </div>
                        <div className={`bsm-balance-check mt-2 ${sufficient ? 'bsm-balance-ok' : 'bsm-balance-warn'}`}>
                            {sufficient ? (
                                <>
                                    <i className="las la-check-circle me-1" />
                                    {t('User has sufficient balance (:amount)', { amount: money(member.balance) })}
                                </>
                            ) : (
                                <>
                                    <i className="las la-exclamation-triangle me-1" />
                                    {t('Insufficient balance — shortfall: :amount.', { amount: money(total - member.balance) })}
                                </>
                            )}
                        </div>
                    </div>
                )}
                {plan && !sufficient && (
                    <div className="bsm-force-row mt-3">
                        <div className="form-check">
                            <input className="form-check-input" type="checkbox" id="bsm-force" checked={form.data.force} onChange={(e) => form.setData('force', e.target.checked)} />
                            <label className="form-check-label" htmlFor="bsm-force">
                                <i className="las la-exclamation-triangle me-1 text-warning" />
                                {/* The old label said "skip balance deduction"; the server has always deducted. */}
                                <strong>{t('Force allocate')}</strong> — {t('the balance goes below zero by the shortfall')}
                            </label>
                        </div>
                    </div>
                )}
                {form.errors.quantity && <div className="bsm-balance-check mt-3 bsm-balance-warn">{form.errors.quantity}</div>}
            </div>
            <div className="modal-footer px-4 pb-4 pt-2 border-0">
                <button type="button" className="btn btn-outline--dark" onClick={onClose}>
                    {t('Cancel')}
                </button>
                <button type="button" className="btn btn--primary px-4" onClick={submit} disabled={!plan || form.processing || (!sufficient && !form.data.force)}>
                    {form.processing ? (
                        <>
                            <span className="spinner-border spinner-border-sm me-1" />
                            {t('Processing…')}
                        </>
                    ) : (
                        <>
                            <i className="las la-check me-1" />
                            {t('Allocate Shares')}
                        </>
                    )}
                </button>
            </div>
        </Modal>
    );
}
