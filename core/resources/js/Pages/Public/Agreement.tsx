import { Head } from '@inertiajs/react';
import type { ReactNode } from 'react';
import { withPublicLayout } from '@/Layouts/PublicLayout';
import './Agreement.css';

type Section = { id: string; title: string; body: ReactNode };

const Bullets = ({ items }: { items: ReactNode[] }) => (
    <ul className="terms-list">
        {items.map((item, i) => (
            <li key={i}>{item}</li>
        ))}
    </ul>
);

/**
 * The terms text from agreement.blade.php (and its copy cookie.blade.php),
 * unchanged apart from two fixes: the contents list called section 8
 * "Dispute resolution" and section 3 "Purpose of the Platforme".
 */
const SECTIONS: Section[] = [
    {
        id: 'introduction',
        title: 'Introduction',
        body: (
            <>
                <p>
                    Welcome to the official investment platform of WiiFarm Cooperative Society Ltd (“WiiFarm” or “the Cooperative”). This digital platform is designed exclusively for
                    registered members of the Cooperative and provides access to farmland ownership opportunities and cooperative-driven agribusiness initiatives.
                </p>
                <p>By using this platform, you agree to the following terms, conditions, and policies governing participation.</p>
            </>
        ),
    },
    {
        id: 'overview',
        title: 'Eligibility',
        body: (
            <Bullets
                items={[
                    <>Access to the investment platform is strictly limited to <strong>registered members of WiiFarm Cooperative Society Ltd.</strong></>,
                    'Non-members are not permitted to participate in any offers or promotional campaigns made available through this platform.',
                    'Registration and acceptance as a member is a mandatory precondition to making any investment, reservation, or acquisition of farmland rights.',
                ]}
            />
        ),
    },
    {
        id: 'purpose-of',
        title: 'Purpose of the Platform',
        body: (
            <Bullets
                items={[
                    <>This platform facilitates the reservation, subscription, and documentation of <strong>Fractional Farmland Ownership Interests</strong> and related cooperative benefits.</>,
                    'It is not a public offer, public investment solicitation, or crowdfunding platform.',
                    <>Offers made through this platform are <strong>member-to-cooperative engagements</strong> governed by cooperative bylaws and internal governance structures</>,
                ]}
            />
        ),
    },
    {
        id: 'use',
        title: 'Risk Disclosure Statement',
        body: (
            <>
                <p>By using this platform and subscribing to any farmland interest or project, you acknowledge and accept the following risks:</p>
                <h5 className="terms-subheading">Agricultural Risks</h5>
                <Bullets
                    items={[
                        'Exposure to unpredictable weather, pest outbreaks, crop failure, or input shortages.',
                        'Fluctuations in commodity market prices and operational margins.',
                        'Delays due to regulatory changes, force majeure, or farm-level disruptions.',
                    ]}
                />
                <h5 className="terms-subheading">Financial Risks</h5>
                <Bullets
                    items={[
                        'No guaranteed profits or minimum returns.',
                        'Potential loss of capital due to operational shortfalls.',
                        'Limited liquidity – resale of units is subject to cooperative approval and internal policy.',
                    ]}
                />
                <h5 className="terms-subheading">Management and Operational Risks</h5>
                <Bullets
                    items={[
                        'Variability in project performance due to staffing, equipment, or third-party involvement.',
                        'Delays in project execution due to infrastructure, technical, or financial bottlenecks.',
                    ]}
                />
            </>
        ),
    },
    {
        id: 'member-Only',
        title: 'Member-Only Investment Policy',
        body: (
            <Bullets
                items={[
                    <>Offers made on this platform constitute <strong>member-based cooperative activities</strong>.</>,
                    'Participants shall be entitled to dividends, land use rights, and cooperative services as outlined in individual agreements.',
                    <>Investments are made in reliance on collective management structures and are <strong>not securities</strong> regulated under public investment laws.</>,
                ]}
            />
        ),
    },
    {
        id: 'indemnity',
        title: 'Indemnity',
        body: (
            <p>
                You agree to indemnify and hold harmless the Cooperative, its board, officers, affiliates, and staff from any claims, losses, liabilities, or disputes arising from your use
                of this platform, except in cases of proven fraud or gross misconduct.
            </p>
        ),
    },
    {
        id: 'dispute-resolution',
        title: 'Dispute Resolution',
        body: (
            <p>
                Any disputes shall be subject to internal dispute mechanisms. Where unresolved, arbitration shall apply in accordance with the Arbitration and Conciliation Act, Laws of the
                Federation of Nigeria.
            </p>
        ),
    },
    {
        id: 'acceptance',
        title: 'Acceptance',
        body: (
            <>
                <p>By accessing or using this platform, you confirm that:</p>
                <Bullets
                    items={[
                        <>You are a registered and accepted member of <strong>WiiFarm Cooperative</strong>.</>,
                        'You understand the risks and limitations of investing in agriculture.',
                        'You agree to be bound by these Terms of Use and Cooperative Investment Policies.',
                    ]}
                />
            </>
        ),
    },
];

/**
 * Terms of Use and Member Investment Policy (/agreement), linked from the
 * sign-up form and the shop footer. The legal text is English only, as before.
 */
export default function Agreement() {
    return (
        <div className="terms-page">
            <Head title="Terms of Use" />
            <div className="shop-container">
                <h1 className="terms-title">
                    WiiFarm Cooperative Society Ltd
                    <span>Terms of Use and Member Investment Policy</span>
                </h1>

                <div className="terms-grid">
                    <nav className="terms-toc" aria-label="Contents">
                        <h2>Contents</h2>
                        <ol>
                            {SECTIONS.map((section) => (
                                <li key={section.id}>
                                    <a href={`#${section.id}`}>{section.title}</a>
                                </li>
                            ))}
                        </ol>
                    </nav>

                    <article className="terms-body">
                        {SECTIONS.map((section, i) => (
                            <section key={section.id} id={section.id} className="terms-section">
                                <h2>
                                    {i + 1}. {section.title}
                                </h2>
                                {section.body}
                            </section>
                        ))}

                        <p className="terms-note">
                            <strong>WiiFarm Cooperative Society Ltd</strong> reserves the right to update these terms periodically in line with cooperative regulations and applicable laws.
                        </p>
                    </article>
                </div>
            </div>
        </div>
    );
}

Agreement.layout = withPublicLayout;
