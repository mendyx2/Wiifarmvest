import { Head } from '@inertiajs/react';
import GenealogyTree, { type TreeNode } from '@/Components/GenealogyTree';
import TreeSearch from '@/Components/TreeSearch';
import { useTrans } from '@/lib/i18n';
import type { PageProps } from '@/types';

type Props = PageProps<{
    owner: { fullname: string; username: string };
    nodes: Record<string, TreeNode>;
    images: { active: string; empty: string };
    searchUrl: string;
}>;

export default function MyTree({ owner, nodes, images, searchUrl }: Props) {
    const t = useTrans();
    return (
        <div className="tree-page">
            <Head title={t('My Tree')} />
            <div className="tree-header-card">
                <div className="tree-header-left">
                    <div className="tree-header-icon"><i className="las la-project-diagram" /></div>
                    <div>
                        <h6 className="tree-header-title">{t('My Genealogy Tree')}</h6>
                        <p className="tree-header-sub">{owner.fullname} &bull; @ {owner.username}</p>
                    </div>
                </div>
                <TreeSearch url={searchUrl} />
            </div>
            <div className="tree-legend">
                <span className="tree-legend-item"><span className="tree-legend-dot active" /> {t('Active member')}</span>
                <span className="tree-legend-item"><span className="tree-legend-dot empty" /> {t('Empty — click to add')}</span>
                <span className="tree-legend-item"><i className="las la-hand-pointer" style={{ color: '#9CA3AF', fontSize: '.8rem' }} /> {t('Click member for details')}</span>
            </div>
            <GenealogyTree nodes={nodes} images={images} />
        </div>
    );
}
