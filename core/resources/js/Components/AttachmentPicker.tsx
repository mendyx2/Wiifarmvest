import { useTrans } from '@/lib/i18n';

const ACCEPT = '.jpeg,.jpg,.png,.pdf,.doc,.docx';
const MAX_FILES = 5;

type Props = {
    files: (File | null)[];
    onChange: (files: (File | null)[]) => void;
    maxUpload: string;
    error?: string;
};

/** Up to 5 ticket attachments, each with a remove button (as in the Blade ticket forms). */
export default function AttachmentPicker({ files, onChange, maxUpload, error }: Props) {
    const t = useTrans();
    const set = (i: number, file: File | null) => onChange(files.map((f, j) => (j === i ? file : f)));

    return (
        <>
            <button type="button" className="btn btn-dark btn-sm my-2" disabled={files.length >= MAX_FILES} onClick={() => onChange([...files, null])}>
                <i className="fas fa-plus" /> {t('Add Attachment')}
            </button>
            <p className="mb-2">
                <span className="text--info">{t('Max 5 files can be uploaded | Maximum upload size is :size | Allowed File Extensions: .jpg, .jpeg, .png, .pdf, .doc, .docx', { size: maxUpload })}</span>
            </p>
            <div className="row">
                {files.map((_, i) => (
                    <div className="col-lg-4 col-md-12" key={i}>
                        <div className="form--group">
                            <div className="input-group">
                                <input type="file" className="form-control" accept={ACCEPT} aria-label={t('Attachment :number', { number: i + 1 })} required onChange={(e) => set(i, e.target.files?.[0] ?? null)} />
                                <button type="button" className="input-group-text bg--danger border--danger" aria-label={t('Remove attachment')} onClick={() => onChange(files.filter((__, j) => j !== i))}>
                                    <i className="fas fa-times" />
                                </button>
                            </div>
                        </div>
                    </div>
                ))}
            </div>
            {error && (
                <small className="d-block" style={{ color: '#DC2626' }}>
                    {error}
                </small>
            )}
        </>
    );
}

/** Only the files actually chosen (empty pickers are left out of the upload). */
export const chosenFiles = (files: (File | null)[]) => files.filter((f): f is File => f !== null);

/** The first attachment error: "attachments" for the list, "attachments.N" for one file. */
export const attachmentError = (errors: Partial<Record<string, string>>) => errors.attachments ?? Object.entries(errors).find(([k]) => k.startsWith('attachments.'))?.[1];
