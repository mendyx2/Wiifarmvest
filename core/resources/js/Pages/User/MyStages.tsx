import { Head, Link } from '@inertiajs/react';
import GenealogyTree, { type TreeNode } from '@/Components/GenealogyTree';
import TreeSearch from '@/Components/TreeSearch';
import { useTrans } from '@/lib/i18n';
import type { PageProps } from '@/types';

type Props = PageProps<{
    owner: { fullname: string; username: string };
    stage: number;
    stages: { number: number; url: string }[];
    nodes: Record<string, TreeNode>;
    images: { active: string; empty: string };
    searchUrl: string;
}>;

export default function MyStages({ owner, stage, stages, nodes, images, searchUrl }: Props) {
    const t = useTrans();
    return (
        <div className="tree-page">
            <Head title={t('My Tree')} />
            <div className="tree-header-card">
                <div className="tree-header-left">
                    <div className="tree-header-icon"><i className="las la-layer-group" /></div>
                    <div>
                        <h6 className="tree-header-title">{t('Stage :n Tree', { n: stage })}</h6>
                        <p className="tree-header-sub">{owner.fullname} &bull; @ {owner.username}</p>
                    </div>
                    <span className="tree-stage-badge">{t('Stage :n', { n: stage })}</span>
                </div>
                <TreeSearch url={searchUrl} />
            </div>
            <nav className="tree-stage-nav" aria-label={t('Stages')}>
                {stages.map((s) => (
                    <Link key={s.number} href={s.url} className={`tree-stage-pill ${s.number === stage ? 'active' : ''}`} aria-current={s.number === stage ? 'page' : undefined}>
                        {t('Stage :n', { n: s.number })}
                    </Link>
                ))}
            </nav>
            <div className="tree-legend">
                <span className="tree-legend-item"><span className="tree-legend-dot active" /> {t('Active member')}</span>
                <span className="tree-legend-item"><span className="tree-legend-dot empty" /> {t('Empty slot')}</span>
                <span className="tree-legend-item"><i className="las la-hand-pointer" style={{ color: '#9CA3AF', fontSize: '.8rem' }} /> {t('Click node for details')}</span>
            </div>
            <GenealogyTree nodes={nodes} images={images} />
        </div>
    );
}
