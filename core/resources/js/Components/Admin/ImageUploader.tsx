import { useEffect, useId, useState } from 'react';
import { useTrans } from '@/lib/i18n';

type Props = {
    /** The current image, shown until a new file is picked */
    image: string;
    onChange: (file: File | null) => void;
    /** "400x400": the size the server resizes to */
    size?: string;
    accept?: string;
    required?: boolean;
    className?: string;
};

/**
 * React version of <x-image-uploader>: a preview box with an upload button.
 * The preview of a picked file comes from the file itself (admin/js/app.js
 * did this with FileReader and jQuery).
 */
export default function ImageUploader({ image, onChange, size, accept = '.png, .jpg, .jpeg', required = false, className = '' }: Props) {
    const t = useTrans();
    const id = useId();
    const [preview, setPreview] = useState<string | null>(null);

    // Free the object URL of the previous pick.
    useEffect(() => () => {
        if (preview) URL.revokeObjectURL(preview);
    }, [preview]);

    return (
        <div className={`image--uploader ${className}`}>
            <div className="image-upload-wrapper">
                <div className={`image-upload-preview ${preview ? 'has-image' : ''}`} style={{ backgroundImage: `url(${preview ?? image})` }} />
                <div className="image-upload-input-wrapper">
                    <input
                        type="file"
                        className="image-upload-input"
                        id={id}
                        accept={accept}
                        required={required}
                        onChange={(e) => {
                            const file = e.target.files?.[0] ?? null;
                            setPreview(file ? URL.createObjectURL(file) : null);
                            onChange(file);
                        }}
                    />
                    <label htmlFor={id} className="bg--primary" aria-label={t('Upload image')}>
                        <i className="la la-cloud-upload" />
                    </label>
                </div>
            </div>
            <div className="mt-2">
                <small className="mt-3 text-muted">
                    {t('Supported Files:')} <b>{accept}.</b>
                    {size && (
                        <>
                            {' '}
                            {t('Image will be resized into')} <b>{size}</b>
                            {t('px')}
                        </>
                    )}
                </small>
            </div>
        </div>
    );
}
