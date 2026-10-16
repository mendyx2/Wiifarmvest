@extends($activeTemplate . 'layouts.frontend')
@section('content')
<div class="bg-light">
    <div class="container py-5">
        <div class="row">
            <!-- Table of Contents -->
            <div class="col-lg-3 mb-4">
                <div class="card sticky-top" style="top: 2rem;">
                    <div class="card-body">
                        <h5 class="card-title mb-3">Contents</h5>
                        <nav class="nav flex-column">
                            <a class="toc-link py-2" href="#introduction">1. Introduction</a>
                            <a class="toc-link py-2" href="#overview">2.  
                            Eligibility</a>
                            <a class="toc-link py-2" href="#purpose-of">3. 
                            Purpose of the Platforme</a>
                            <a class="toc-link py-2" href="#use">4.
                            Risk Disclosure Statement</a>
                            
                            <a class="toc-link py-2" href="#member-Only">5. Member-Only Investment Policy</a>
                            <a class="toc-link py-2" href="#indemnity">6. Indemnity</a>
                            
                            <a class="toc-link py-2" href="#dispute-resolution">7. Dispute resolution</a>
                            <a class="toc-link py-2" href="#acceptance">8. Dispute resolution</a>

                       

                        </nav>
                    </div>
                </div>
            </div>

            <!-- Main Content -->
            <div class="col-lg-9">
                <div class="card">
                    <div class="card-body mx-3 " id="introduction">
                        <h4 >
                            1. Introduction
                            </h4>
                        <p class="text-dark px-3 py-2">
                        Welcome to the official investment platform of WiiFarm Cooperative Society Ltd (“WiiFarm” or “the Cooperative”). This digital platform is designed exclusively for registered members of the Cooperative and provides access to farmland ownership opportunities and cooperative-driven agribusiness initiatives.
                        <br/>
                        By using this platform, you agree to the following terms, conditions, and policies governing participation.
                        </p>
                        <hr/>
                        <div id="overview" class="policy-section py-4">
                            <h4>2. Eligibility</h4>
                            

                                <ul class="sq text-dark pt-1 px-5">
                                    <li>
                                        - Access to the investment platform is strictly limited to <strong>registered members of WiiFarm Cooperative Society Ltd.</strong>
                                    </li>
                                    <li>
                                        - Non-members are not permitted to participate in any offers or promotional campaigns made available through this platform.
                                    </li>
                                    <li>
                                        - Registration and acceptance as a member is a mandatory precondition to making any investment, reservation, or acquisition of farmland rights.

                                    </li>
                                </ul>
                            
                        </div>

                        <div id="purpose-of" class="policy-section py-4">
                            <h4>3. Purpose of the Platform</h4>
                            
                            <ul  class="text-dark pt-1 px-5">
                                <li>- This platform facilitates the reservation, subscription, and documentation of <strong>Fractional Farmland Ownership Interests</strong> and related cooperative benefits.</li>


                                <li>- It is not a public offer, public investment solicitation, or crowdfunding platform.</li>
                                <li>- Offers made through this platform are <strong>member-to-cooperative engagements</strong> governed by cooperative bylaws and internal governance structures</li>
                            </ul>

                            
                        </div>

                        <div id="use" class="policy-section py-4">
                            <h4>4. Risk Disclosure Statement</h4>
                            <p class="text-dark py-2 px-3">By using this platform and subscribing to any farmland interest or project, you acknowledge and accept the following risks:</p>

                            <p class="h5 text-dark px-3"><strong>Agricultural Risks</strong></p>
                            <ul  class="text-dark pt-1 px-5 a">
                                <li>- Exposure to unpredictable weather, pest outbreaks, crop failure, or input shortages.</li>
                                    

                                <li>- Fluctuations in commodity market prices and operational margins.</li>
                                <li>- Delays due to regulatory changes, force majeure, or farm-level disruptions.</li>
                            </ul>

                            <p class="h5 text-dark px-3"><strong>Financial Risks</strong></p>
                            <ul  class="text-dark pt-1 px-5 a">
                                <li>- No guaranteed profits or minimum returns.</li>


                                <li>- Potential loss of capital due to operational shortfalls.</li>
                                <li>- Limited liquidity – resale of units is subject to cooperative approval and internal policy.</li>
                            </ul>


                            <p class="h5 text-dark px-3"><strong>Management and Operational Risks</strong></p>
                            <ul  class="text-dark pt-1 px-5 a">
                                <li>- Variability in project performance due to staffing, equipment, or third-party involvement.</li>


                                <li>- Delays in project execution due to infrastructure, technical, or financial bottlenecks.</li>
                                
                            </ul>
      

                        </div>
                        <hr>
                        <div id="member-Only" class="policy-section py-4">
                            <h4>5. Member-Only Investment Policy</h4>
                            

                            
                            <ul  class="text-dark pt-1 px-5 a">
                                <li>- Offers made on this platform constitute <strong>member-based cooperative activities</strong>.</li>


                                <li>- Participants shall be entitled to dividends, land use rights, and cooperative services as outlined in individual agreements.</li>
                                <li>- Investments are made in reliance on collective management structures and are <strong>not securities</strong> regulated under public investment laws.</li>
                            </ul>

                        </div>

                        <hr>
                        <div id="indemnity" class="policy-section py-4">
                            <h4>6. Indemnity</h4>
                            
                            <p class="text-dark pt-2 px-2">You agree to indemnify and hold harmless the Cooperative, its board, officers, affiliates, and staff from any claims, losses, liabilities, or disputes arising from your use of this platform, except in cases of proven fraud or gross misconduct.</p>

                        </div>

                        <hr>
                        <div id="dispute-resolution" class="policy-section py-4">
                            <h4>7. Dispute Resolution</h4>
                            
                            <p class="text-dark pt-2 px-2">Any disputes shall be subject to internal dispute mechanisms. Where unresolved, arbitration shall apply in accordance with the Arbitration and Conciliation Act, Laws of the Federation of Nigeria.</p>

                        </div>

                        <hr>
                        <div id="acceptance" class="policy-section py-4">
                            <h4>8. Acceptance</h4>
                            
                            <p class="text-dark pt-2 px-2">By accessing or using this platform, you confirm that:</p>
                            <ul  class="text-dark pt-1 px-5 a">
                                <li>- You are a registered and accepted member of <strong>WiiFarm Cooperative</strong>.</li>


                                <li>- You understand the risks and limitations of investing in agriculture.</li>
                                <li>- You agree to be bound by these Terms of Use and Cooperative Investment Policies.</li>
                            </ul>

                        </div>

                      

                        <div id="contact" class="policy-section py-4">
                            
                            <div class="card bg-light mt-3">
                                <div class="card-body px-2">
                                    <p class="mb-1 text-dark" style="font-size:18px;"><strong>WiiFarm Cooperative Society Ltd</strong> reserves the right to update these terms periodically in line with cooperative regulations and applicable laws.</p>
                                    
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
   
@endsection
@push('css-styles')

<style type="text/css">
    ul.sq {
        list-style-type: square !important;
    }

    .policy-section:not(:last-child) {
        border-bottom: 1px solid #dee2e6;
    }
    .toc-link {
        color: inherit;
        text-decoration: none;
        transition: color 0.2s;
    }
    .toc-link:hover {
        color: #0d6efd;
    }

</style>
@endpush