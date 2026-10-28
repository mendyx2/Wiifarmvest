import { router, useForm } from '@inertiajs/react';
import { useState, type FormEvent } from 'react';
import { BadgeCell, UserCell, type AdminUser, type Badge } from '@/Components/Admin/Cells';
import PageHeader from '@/Components/Admin/PageHeader';
import ConfirmDialog from '@/Components/ConfirmDialog';
import Lightbox from '@/Components/Lightbox';
import Modal from '@/Components/Modal';
import { useTrans } from '@/lib/i18n';

type FileLinks = { view: string; download: string; image: boolean };

type Props = {
    member: AdminUser;
    hasData: boolean;
    fields: { name: string; type: string; value: string | null; file: FileLinks | null }[];
    identity: { nin: string | null; cardType: string | null; card: FileLinks | null };
    guarantor: { name: string | null; username: string | null; status: Badge; responded: string | null } | null;
    pending: boolean;
    rejectionReason: string | null;
    urls: { approve: string; reject: string };
};

/** One member's KYC submission: the form answers, ID, guarantor, and approve / reject. */
export default function KycDetail({ member, hasData, fields, identity, guarantor, pending, rejectionReason, urls }: Props) {
    const t = useTrans();
    const [zoom, setZoom] = useState<string | null>(null);
    const [approving, setApproving] = useState(false);
    const [busy, setBusy] = useState(false);
    const [rejecting, setRejecting] = useState(false);
    const reject = useForm({ reason: '' });

    const approve = () =>
        router.post(urls.approve, {}, {
            onStart: () => setBusy(true),
            onFinish: () => {
                setBusy(false);
                setApproving(false);
            },
        });

    const submitReject = (e: FormEvent) => {
        e.preventDefault();
        reject.post(urls.reject, { onSuccess: () => setRejecting(false) });
    };

    const fileButtons = (file: FileLinks) => (
        <div className="d-flex gap-2 align-items-center">
            {file.image && (
                <button type="button" className="btn btn-sm btn-outline--primary py-1 px-2" onClick={() => setZoom(file.view)}>
                    <i className="fas fa-eye" /> {t('View')}
                </button>
            )}
            <a href={file.download} className="btn btn-sm btn-outline--dark py-1 px-2">
                <i className="fas fa-download" /> {t('Download')}
            </a>
        </div>
    );

    return (
        <>
            <PageHeader title={t('KYC Details')}>
                <div className="text-end">
                    <UserCell user={member} />
                </div>
            </PageHeader>
            <div className="row justify-content-center">
                <div className="col-lg-8">
                    <div className="card">
                        <div className="card-body">
                            {hasData && fields.length ? (
                                <ul className="list-group">
                                    {fields.map((f, i) => (
                                        <li key={i} className="list-group-item d-flex justify-content-between align-items-center flex-wrap gap-2">
                                            <span>{f.name}</span>
                                            <span>{f.file ? fileButtons(f.file) : f.value}</span>
                                        </li>
                                    ))}
                                </ul>
                            ) : (
                                <h5 className="text-center">{t('KYC data not found')}</h5>
                            )}

                            {(identity.nin || identity.cardType || identity.card) && (
                                <>
                                    <h6 className="mt-4 mb-2 text-muted">{t('Identity Verification')}</h6>
                                    <ul className="list-group mb-3">
                                        {identity.nin && (
                                            <li className="list-group-item d-flex justify-content-between align-items-center">
                                                {t('NIN')}
                                                <span className="fw-bold">{identity.nin}</span>
                                            </li>
                                        )}
                                        {identity.cardType && (
                                            <li className="list-group-item d-flex justify-content-between align-items-center">
                                                {t('ID Card Type')}
                                                <span>{identity.cardType}</span>
                                            </li>
                                        )}
                                        {identity.card && (
                                            <li className="list-group-item d-flex justify-content-between align-items-center flex-wrap gap-2">
                                                {t('ID Card Image')}
                                                <div className="d-flex gap-2 align-items-center">
                                                    <button type="button" className="kyc-thumb" onClick={() => setZoom(identity.card!.view)} aria-label={t('View')}>
                                                        <img src={identity.card.view} alt={t('ID Card')} />
                                                    </button>
                                                    {fileButtons(identity.card)}
                                                </div>
                                            </li>
                                        )}
                                    </ul>
                                </>
                            )}

                            {guarantor && (
                                <>
                                    <h6 className="mt-3 mb-2 text-muted">{t('Guarantor')}</h6>
                                    <ul className="list-group mb-3">
                                        <li className="list-group-item d-flex justify-content-between align-items-center">
                                            {t('Nominated Guarantor')}
                                            {guarantor.name ? (
                                                <span>
                                                    {guarantor.name} <span className="text-muted">({guarantor.username})</span>
                                                </span>
                                            ) : (
                                                <span className="text-muted">{t('Deleted user')}</span>
                                            )}
                                        </li>
                                        <li className="list-group-item d-flex justify-content-between align-items-center">
                                            {t('Guarantor Status')}
                                            <BadgeCell badge={guarantor.status} />
                                        </li>
                                        {guarantor.responded && (
                                            <li className="list-group-item d-flex justify-content-between align-items-center">
                                                {t('Responded At')}
                                                <span>{guarantor.responded}</span>
                                            </li>
                                        )}
                                    </ul>
                                </>
                            )}

                            {rejectionReason && (
                                <div className="my-3">
                                    <h6>{t('Rejection Reason')}</h6>
                                    <p className="admin-notify-text">{rejectionReason}</p>
                                </div>
                            )}

                            {pending && (
                                <div className="d-flex flex-wrap justify-content-end gap-3 mt-3">
                                    <button type="button" className="btn btn-outline--danger" onClick={() => setRejecting(true)}>
                                        <i className="las la-ban" /> {t('Reject')}
                                    </button>
                                    <button type="button" className="btn btn-outline--success" onClick={() => setApproving(true)}>
                                        <i className="las la-check" /> {t('Approve')}
                                    </button>
                                </div>
                            )}
                        </div>
                    </div>
                </div>
            </div>

            <Lightbox src={zoom} onClose={() => setZoom(null)} />
            <ConfirmDialog open={approving} question={t('Are you sure to approve this documents?')} busy={busy} onConfirm={approve} onCancel={() => setApproving(false)} />
            <Modal open={rejecting} onClose={() => setRejecting(false)} labelledBy="kyc-reject-title">
                <div className="modal-header">
                    <h5 className="modal-title" id="kyc-reject-title">
                        {t('Reject KYC Documents')}
                    </h5>
                    <button type="button" className="close" aria-label={t('Close')} onClick={() => setRejecting(false)}>
                        <i className="las la-times" />
                    </button>
                </div>
                <form onSubmit={submitReject}>
                    <div className="modal-body">
                        <div className="alert alert-primary p-3">
                            {t('If you reject these documents, the user will be able to re-submit new documents and these documents will be replaced by new documents.')}
                        </div>
                        <div className="form-group">
                            <label htmlFor="kyc-reason">{t('Rejection Reason')}</label>
                            <textarea id="kyc-reason" className="form-control" rows={4} maxLength={1000} required value={reject.data.reason} onChange={(e) => reject.setData('reason', e.target.value)} />
                            {reject.errors.reason && <small className="text--danger">{reject.errors.reason}</small>}
                        </div>
                    </div>
                    <div className="modal-footer">
                        <button type="submit" className="btn btn--primary h-45 w-100" disabled={reject.processing || !reject.data.reason.trim()}>
                            {t('Submit')}
                        </button>
                    </div>
                </form>
            </Modal>
        </>
    );
}
