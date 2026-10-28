import { Link } from '@inertiajs/react';
import { useCallback, useEffect, useLayoutEffect, useRef, useState } from 'react';
import Modal from '@/Components/Modal';
import { useTrans } from '@/lib/i18n';
import './GenealogyTree.css';

export type TreeMember = {
    username: string;
    stage: string | null;
    refBy: string | null;
    pvLeft: number;
    pvRight: number;
    pvLeftRank: number;
    pvRightRank: number;
    treeUrl: string;
    /** Admin tree: the member's admin page */
    detailUrl?: string;
};
/** An empty position under an existing member: links to the distributor form. */
export type TreeSlot = { slot: { side: 'left' | 'right'; parent: string; url: string } };
export type TreeNode = TreeMember | TreeSlot | null;

type Props = {
    nodes: Record<string, TreeNode>;
    images: { active: string; empty: string };
};

const LEVELS = [['a'], ['b', 'c'], ['d', 'e', 'f', 'g'], ['h', 'i', 'j', 'k', 'l', 'm', 'n', 'o']];
const isMember = (n: TreeNode): n is TreeMember => n !== null && 'username' in n;

/**
 * The 4-level binary tree from the Blade "My Tree" / "Stage" pages: same
 * markup and CSS classes (tree-level-*, tree-connector-*), drawn from data.
 */
export default function GenealogyTree({ nodes, images }: Props) {
    const t = useTrans();
    const [selected, setSelected] = useState<TreeMember | null>(null);
    const outer = useRef<HTMLDivElement>(null);
    const canvas = useRef<HTMLDivElement>(null);

    // Phones: shrink the tree to fit the screen width (same rule as the Blade page).
    const scale = useCallback(() => {
        const c = canvas.current, o = outer.current;
        if (!c || !o) return;
        c.style.transform = 'scale(1)';
        c.style.marginBottom = '0';
        if (window.innerWidth >= 768 || c.offsetWidth <= o.offsetWidth) return;
        const factor = o.offsetWidth / c.offsetWidth;
        c.style.transform = `scale(${factor})`;
        c.style.marginBottom = `${-(c.offsetHeight - c.offsetHeight * factor)}px`;
    }, []);
    useLayoutEffect(scale, [scale, nodes]);
    useEffect(() => {
        window.addEventListener('resize', scale);
        return () => window.removeEventListener('resize', scale);
    }, [scale]);

    function renderNode(key: string) {
        const node = nodes[key] ?? null;
        if (isMember(node)) {
            return (
                <>
                    <button type="button" className="user showDetails" onClick={() => setSelected(node)} aria-label={t('Details for :name', { name: node.username })}>
                        <img src={images.active} alt="" className={node.stage ?? ''} />
                        <p className="user-name">{node.username}</p>
                    </button>
                    <span className="line" />
                </>
            );
        }
        if (node && 'slot' in node) {
            const label = node.slot.side === 'left' ? t('Left slot') : t('Right slot');
            return (
                <>
                    <a href={node.slot.url} className="user tree-slot-link" title={t("Fill :name's :slot", { name: node.slot.parent, slot: label })}>
                        <img src={images.empty} alt="" className="no-user tree-slot-img" />
                        <p className="user-name tree-slot-label"><i className="las la-plus-circle" /> {label}</p>
                    </a>
                    <span className="line" />
                </>
            );
        }
        return (
            <>
                <div className="user">
                    <img src={images.empty} alt="" className="no-user" />
                    <p className="user-name">{key === 'a' ? t('No user') : t('Empty')}</p>
                </div>
                <span className="line" />
            </>
        );
    }

    const pvMax = selected ? Math.max(selected.pvLeft, selected.pvRight, 1) : 1;
    const bar = (v: number) => `${Math.min(100, Math.round((v / pvMax) * 100))}%`;

    return (
        <>
            <div className="tree-card">
                <div className="tree-scroll-wrap">
                    <div className="tree-canvas-outer" ref={outer}>
                        <div className="tree-canvas" ref={canvas}>
                            {LEVELS.map((keys, i) => (
                                <div key={i} style={{ display: 'contents' }}>
                                    {i > 0 && <Connector level={i} />}
                                    <div className={`tree-level tree-level-${i + 1}`}>
                                        {keys.map((k) => <div className="tree-node-wrap" key={k}>{renderNode(k)}</div>)}
                                    </div>
                                </div>
                            ))}
                        </div>
                    </div>
                </div>
            </div>

            <Modal open={selected !== null} onClose={() => setSelected(null)} contentClassName="tree-modal-content" labelledBy="tree-modal-name">
                {selected && (
                    <>
                        <div className="tree-modal-header">
                            <div className="tree-modal-avatar"><img src={images.active} alt="" className="tree_image" /></div>
                            <div className="tree-modal-info">
                                <h6 className="tree-modal-name tree_name" id="tree-modal-name">{t('Username: :name', { name: selected.username })}</h6>
                                <p className="tree-modal-status tree_status">{selected.stage}</p>
                            </div>
                            <button type="button" className="tree-modal-close" aria-label={t('Close')} onClick={() => setSelected(null)}><i className="las la-times" /></button>
                        </div>
                        <div className="tree-modal-body">
                            <div className="tree-modal-row">
                                <span className="tree-modal-label"><i className="las la-user-friends" /> {t('Referred By')}</span>
                                <span className="tree-modal-value tree_ref">{selected.refBy || '—'}</span>
                            </div>
                            <PvRow title={t('Binary Pairing PV')} left={selected.pvLeft} right={selected.pvRight} leftBar={bar(selected.pvLeft)} rightBar={bar(selected.pvRight)} />
                            <PvRow title={t('Ranking PV')} left={selected.pvLeftRank} right={selected.pvRightRank} />
                            <Link href={selected.treeUrl} className="tree-modal-btn tree_url" onClick={() => setSelected(null)}>
                                <i className="las la-project-diagram" /> {t('View Their Tree')}
                            </Link>
                            {selected.detailUrl && (
                                <Link href={selected.detailUrl} className="tree-modal-btn tree-modal-btn--secondary mt-2" onClick={() => setSelected(null)}>
                                    <i className="las la-user" /> {t('Open Member Page')}
                                </Link>
                            )}
                        </div>
                    </>
                )}
            </Modal>
        </>
    );
}

function PvRow({ title, left, right, leftBar, rightBar }: { title: string; left: number; right: number; leftBar?: string; rightBar?: string }) {
    const t = useTrans();
    const leg = (label: string, icon: string, value: number, width: string | undefined, cls: string) => (
        <div className="bk-pv-leg">
            <div className="bk-pv-leg-top">
                <span className="bk-pv-leg-lbl"><i className={`las ${icon}`} /> {label}</span>
                <span className="bk-pv-leg-val">{value.toFixed(2)}</span>
            </div>
            {width !== undefined && <div className="bk-pv-bar-track"><div className={`bk-pv-bar-fill ${cls}`} style={{ width }} /></div>}
        </div>
    );
    return (
        <div className="bk-pv-row">
            <div className="bk-pv-label"><i className="las la-project-diagram" /> {title}</div>
            <div className="bk-pv-bars">
                {leg(t('Left PV'), 'la-arrow-alt-circle-left', left, leftBar, 'bk-pv-left')}
                {leg(t('Right PV'), 'la-arrow-alt-circle-right', right, rightBar, 'bk-pv-right')}
            </div>
        </div>
    );
}

/** The connector lines between levels (same markup as the Blade page). */
function Connector({ level }: { level: number }) {
    const lines = (n: number, cls: string) => Array.from({ length: n }, (_, i) => <div className={cls} key={i} />);
    if (level === 1) {
        return (
            <div className="tree-connector tree-connector-1-2">
                <div className="tree-v-line" />
                <div className="tree-h-spread tree-h-spread-2">{lines(2, 'tree-h-line')}</div>
                <div className="tree-v-pair">{lines(2, 'tree-v-line-short')}</div>
            </div>
        );
    }
    if (level === 2) {
        return (
            <div className="tree-connector tree-connector-2-3">
                <div className="tree-v-pair">{lines(2, 'tree-v-line')}</div>
                <div className="tree-h-spread tree-h-spread-4">{lines(4, 'tree-h-line')}</div>
                <div className="tree-v-quad">{lines(4, 'tree-v-line-short')}</div>
            </div>
        );
    }
    return (
        <div className="tree-connector tree-connector-3-4">
            <div className="tree-v-oct-pre">{lines(4, 'tree-v-line')}</div>
            <div className="tree-h-spread tree-h-spread-8">{lines(8, 'tree-h-line')}</div>
            <div className="tree-v-oct">{lines(8, 'tree-v-line-short')}</div>
        </div>
    );
}
