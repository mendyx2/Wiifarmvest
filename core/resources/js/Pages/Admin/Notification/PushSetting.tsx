import { useForm } from '@inertiajs/react';
import { useState, type FormEvent } from 'react';
import { DialogHeader, FieldError } from '@/Components/Admin/Form';
import { NotificationNav, type NotificationTabs } from '@/Components/Admin/NotificationNav';
import PageHeader from '@/Components/Admin/PageHeader';
import Modal from '@/Components/Modal';
import { useTrans } from '@/lib/i18n';

type Form = { apiKey: string; authDomain: string; projectId: string; storageBucket: string; messagingSenderId: string; appId: string; measurementId: string };

type Props = {
    form: Form;
    fileExists: boolean;
    helpImage: string;
    urls: { tabs: NotificationTabs; update: string; upload: string; download: string };
};

const STEPS = [
    'Go to your Firebase account and select "Go to console" in the upper-right corner of the page.',
    'Click on the "Add Project" button.',
    'Enter the project name and click on the "Continue" button.',
    'Enable Google Analytics and click on the "Continue" button.',
    'Choose the default account for the Google Analytics account and click on the "Create Project" button.',
    'Within your Firebase project, select the gear next to Project Overview and choose Project settings.',
    'Next, set up a web app under the General section of your project settings.',
    'Go to the Service accounts tab and generate a new private key.',
    'A JSON file will be downloaded. Upload the downloaded file here.',
];

/** Notification Setting → Push Notification Setting: the Firebase web config and the service-account key file. */
export default function PushSetting({ form: initial, fileExists, helpImage, urls }: Props) {
    const t = useTrans();
    const form = useForm<Form>(initial);
    const [dialog, setDialog] = useState<'help' | 'upload' | null>(null);
    const submit = (e: FormEvent) => {
        e.preventDefault();
        form.post(urls.update, { preserveScroll: true });
    };
    const labels: Record<keyof Form, string> = { apiKey: 'API Key', authDomain: 'Auth Domain', projectId: 'Project Id', storageBucket: 'Storage Bucket', messagingSenderId: 'Messaging Sender Id', appId: 'App Id', measurementId: 'Measurement Id' };

    return (
        <>
            <PageHeader title={t('Push Notification Settings')}>
                <button type="button" className="btn btn-outline--info" onClick={() => setDialog('help')}>
                    <i className="las la-question" /> {t('Help')}
                </button>
                <button type="button" className="btn btn-outline--primary" onClick={() => setDialog('upload')}>
                    <i className="las la-upload" /> {t('Upload Config File')}
                </button>
                {fileExists ? (
                    <a href={urls.download} className="btn btn-outline--info">
                        <i className="las la-download" /> {t('Download File')}
                    </a>
                ) : (
                    <button type="button" className="btn btn-outline--info" disabled>
                        <i className="las la-download" /> {t('Download File')}
                    </button>
                )}
            </PageHeader>
            <NotificationNav tabs={urls.tabs} active="push" />
            <div className="card bl--5 border--primary mb-4">
                <div className="card-body">
                    <p className="text--primary mb-0">{t('If you want to send push notification by the firebase, Your system must be SSL certified')}</p>
                </div>
            </div>
            <div className="card">
                <form onSubmit={submit}>
                    <div className="card-body">
                        <div className="row">
                            {(Object.keys(labels) as (keyof Form)[]).map((key) => (
                                <div className="col-md-4 form-group" key={key}>
                                    <label htmlFor={`fb-${key}`}>{t(labels[key])}</label>
                                    <input id={`fb-${key}`} className="form-control" required value={form.data[key]} onChange={(e) => form.setData(key, e.target.value)} />
                                    <FieldError message={form.errors[key]} />
                                </div>
                            ))}
                        </div>
                        <button type="submit" className="btn btn--primary w-100 h-45" disabled={form.processing}>
                            {t('Submit')}
                        </button>
                    </div>
                </form>
            </div>
            <Modal open={dialog === 'help'} onClose={() => setDialog(null)} labelledBy="fb-help" size="xl">
                <DialogHeader id="fb-help" title={t('Firebase Setup')} onClose={() => setDialog(null)} />
                <div className="modal-body">
                    <ol className="ps-3">
                        {STEPS.map((s) => (
                            <li key={s} className="mb-2">
                                {t(s)}
                            </li>
                        ))}
                    </ol>
                    <img src={helpImage} alt={t('Firebase Config')} className="img-fluid mt-3" />
                </div>
            </Modal>
            {dialog === 'upload' && <UploadDialog url={urls.upload} onClose={() => setDialog(null)} />}
        </>
    );
}

function UploadDialog({ url, onClose }: { url: string; onClose: () => void }) {
    const t = useTrans();
    const form = useForm<{ file: File | null }>({ file: null });
    const submit = (e: FormEvent) => {
        e.preventDefault();
        form.post(url, { preserveScroll: true, forceFormData: true, onSuccess: onClose });
    };
    return (
        <Modal open onClose={onClose} labelledBy="fb-upload">
            <DialogHeader id="fb-upload" title={t('Upload Push Notification Configuration File')} onClose={onClose} />
            <form onSubmit={submit}>
                <div className="modal-body">
                    <label htmlFor="fb-file">{t('File')}</label>
                    <input id="fb-file" type="file" className="form-control" accept=".json,application/json" required onChange={(e) => form.setData('file', e.target.files?.[0] ?? null)} />
                    <small className="text-muted">{t('Supported Files: .json')}</small>
                    <FieldError message={form.errors.file} />
                </div>
                <div className="modal-footer">
                    <button type="submit" className="btn btn--primary w-100 h-45" disabled={form.processing || !form.data.file}>
                        {t('Upload')}
                    </button>
                </div>
            </form>
        </Modal>
    );
}
