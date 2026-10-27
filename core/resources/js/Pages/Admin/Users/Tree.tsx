import { Link } from '@inertiajs/react';
import type { AdminUser } from '@/Components/Admin/Cells';
import PageHeader from '@/Components/Admin/PageHeader';
import GenealogyTree, { type TreeNode } from '@/Components/GenealogyTree';
import TreeSearch from '@/Components/TreeSearch';
import { useTrans } from '@/lib/i18n';

type Props = {
    title: string;
    owner: NonNullable<AdminUser>;
    /** The member above this one in the tree, if any */
    parent: { username: string; url: string } | null;
    nodes: Record<string, TreeNode>;
    images: { active: string; empty: string };
    urls: { tree: string };
};

/**
 * Users → a member's binary tree (/admin/user/tree/{username}). The same
 * tree as the member's "My Tree"; clicking a member opens their tree or
 * their admin page.
 */
export default function Tree({ title, owner, parent, nodes, images, urls }: Props) {
    const t = useTrans();
    return (
        <div className="tree-page">
            <PageHeader title={title}>
                <Link href={owner.url} className="btn btn-outline--primary">
                    <i className="las la-user" /> {t('Member Page')}
                </Link>
            </PageHeader>
            <div className="tree-header-card">
                <div className="tree-header-left">
                    <div className="tree-header-icon">
                        <i className="las la-project-diagram" />
                    </div>
                    <div>
                        <h6 className="tree-header-title">
                            {owner.fullname} &bull; @ {owner.username}
                        </h6>
                        <p className="tree-header-sub">
                            {parent ? (
                                <>
                                    {t('Parent')}: <Link href={parent.url}>{parent.username}</Link>
                                </>
                            ) : (
                                t('Top of the tree')
                            )}
                        </p>
                    </div>
                </div>
                <TreeSearch url={urls.tree} />
            </div>
            <div className="tree-legend">
                <span className="tree-legend-item">
                    <span className="tree-legend-dot active" /> {t('Active member')}
                </span>
                <span className="tree-legend-item">
                    <span className="tree-legend-dot empty" /> {t('Empty position')}
                </span>
                <span className="tree-legend-item">
                    <i className="las la-hand-pointer" style={{ color: '#9CA3AF', fontSize: '.8rem' }} /> {t('Click member for details')}
                </span>
            </div>
            <GenealogyTree nodes={nodes} images={images} />
        </div>
    );
}
