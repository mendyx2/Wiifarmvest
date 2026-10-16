@extends($activeTemplate . 'layouts.master')
@section('content')
<div class="nc-wrap" id="ncWrap">
    <br>
    <div class="rwp-page">
        {{-- ── Stats strip ─────────────────────────────────── --}}
        <div class="rwp-stats">
            <div class="rwp-stat">
                <div class="rwp-stat-icon green"><i class="las la-coins"></i></div>
                <div>
                    <p class="rwp-stat-label">Total Redeemed</p>
                    <p class="rwp-stat-value">{{ showAmount($totalRedeemed) }}</p>
                </div>
            </div>
            <div class="rwp-stat">
                <div class="rwp-stat-icon amber"><i class="las la-list-alt"></i></div>
                <div>
                    <p class="rwp-stat-label">Packages Processed</p>
                    <p class="rwp-stat-value">{{ $history->total() }}</p>
                </div>
            </div>
            <div class="rwp-stat">
                <div class="rwp-stat-icon slate"><i class="las la-gift"></i></div>
                <div>
                    <p class="rwp-stat-label">Scanner Status</p>
                    <p class="rwp-stat-value" style="font-size:1rem;color:#059669;">
                        <span style="display:inline-block;width:8px;height:8px;border-radius:50%;background:#059669;margin-right:5px;animation:rwpBlink 1.5s ease-in-out infinite;"></span>
                        Ready
                    </p>
                </div>
            </div>
        </div>

        {{-- ── Scanner hero card ───────────────────────────── --}}
        <div class="rwp-scanner-card">
            {{-- Background decorations --}}
            <div class="rwp-scanner-bg-circle rwp-bg-c1"></div>
            <div class="rwp-scanner-bg-circle rwp-bg-c2"></div>
            <div class="rwp-scanner-bg-circle rwp-bg-c3"></div>
            <div class="rwp-scanner-grid"></div>

            {{-- Header --}}
            <div class="rwp-scanner-header">
                <div style="display:flex;align-items:flex-start;gap:18px;flex:1;min-width:0;">
                    <div class="rwp-scanner-icon-wrap">
                        <div class="rwp-scanner-icon-bg"><i class="las la-gift"></i></div>
                        <div class="rwp-icon-ring"></div>
                        <div class="rwp-icon-ring-2"></div>
                    </div>
                    <div class="rwp-scanner-title-block">
                        <p class="rwp-scanner-label"><i class="las la-shield-alt"></i> &nbsp;Authorized Redemption Terminal</p>
                        <h3 class="rwp-scanner-title">Gift Code Scanner</h3>
                        <p class="rwp-scanner-sub">Enter the customer's 10-character welcome package code below to verify and redeem their gift in person.</p>
                    </div>
                </div>
                <div class="rwp-scanner-total-badge">
                    <p class="rwp-total-label">Total Earned</p>
                    <p class="rwp-total-value">{{ showAmount($totalRedeemed) }}</p>
                </div>
            </div>

            {{-- Code entry --}}
            <div class="rwp-code-entry" id="codeEntryWrap">
                <p class="rwp-code-label"><i class="las la-keyboard" style="font-size:1rem;margin-right:3px;"></i> Enter Redemption Code</p>

                {{-- OTP boxes: 5 + separator + 5 (desktop) --}}
                <div class="rwp-otp-wrap" id="otpWrap">
                    <input class="rwp-otp-box" id="otp0" maxlength="1" autocomplete="off" inputmode="text" type="text" aria-label="Code character 1">
                    <input class="rwp-otp-box" id="otp1" maxlength="1" autocomplete="off" inputmode="text" type="text" aria-label="Code character 2">
                    <input class="rwp-otp-box" id="otp2" maxlength="1" autocomplete="off" inputmode="text" type="text" aria-label="Code character 3">
                    <input class="rwp-otp-box" id="otp3" maxlength="1" autocomplete="off" inputmode="text" type="text" aria-label="Code character 4">
                    <input class="rwp-otp-box" id="otp4" maxlength="1" autocomplete="off" inputmode="text" type="text" aria-label="Code character 5">
                    <div class="rwp-otp-sep">—</div>
                    <input class="rwp-otp-box" id="otp5" maxlength="1" autocomplete="off" inputmode="text" type="text" aria-label="Code character 6">
                    <input class="rwp-otp-box" id="otp6" maxlength="1" autocomplete="off" inputmode="text" type="text" aria-label="Code character 7">
                    <input class="rwp-otp-box" id="otp7" maxlength="1" autocomplete="off" inputmode="text" type="text" aria-label="Code character 8">
                    <input class="rwp-otp-box" id="otp8" maxlength="1" autocomplete="off" inputmode="text" type="text" aria-label="Code character 9">
                    <input class="rwp-otp-box" id="otp9" maxlength="1" autocomplete="off" inputmode="text" type="text" aria-label="Code character 10">
                </div>

                {{-- Mobile single input (≤ 575px) — swaps in instead of the 10 boxes --}}
                <div class="rwp-mobile-card" id="mobileInputWrap">
                    <div class="rwp-mobile-card-inner">
                        <div class="rwp-mobile-card-icon"><i class="las la-gift"></i></div>
                        <div class="rwp-mobile-card-body">
                            <p class="rwp-mobile-card-label">Enter Redemption Code</p>
                            <input type="text" id="codeSingleInput" class="rwp-mobile-input"
                                   maxlength="10" placeholder="XXXXXXXXXX"
                                   autocomplete="off" inputmode="text" spellcheck="false"
                                   aria-label="10-character redemption code">
                            <p class="rwp-mobile-card-hint">10 characters &bull; letters &amp; numbers</p>
                        </div>
                    </div>
                </div>

                <button type="button" class="rwp-verify-btn" id="verifyBtn" disabled>
                    <span class="rwp-verify-btn-icon"><i class="las la-search-plus"></i></span>
                    <span id="verifyBtnText">Verify Code</span>
                </button>

                <div class="rwp-tips-row">
                    <span class="rwp-tip"><i class="las la-keyboard"></i> Type or paste the code</span>
                    <span class="rwp-tip"><i class="las la-undo"></i> Backspace to correct</span>
                    <span class="rwp-tip"><i class="las la-lock"></i> Secure & encrypted</span>
                </div>
            </div>
        </div>

        {{-- ── Verified package reveal ─────────────────────── --}}
        <div id="packageReveal" class="d-none">
            <div class="rwp-reveal-outer">
                <div class="rwp-reveal-card">

                    {{-- Header --}}
                    <div class="rwp-reveal-header">
                        <div class="rwp-reveal-check-badge"><i class="las la-check"></i></div>
                        <div class="rwp-reveal-header-text">
                            <h6>Code Verified</h6>
                            <p>Customer identified — confirm to complete redemption</p>
                        </div>
                        <div class="rwp-reveal-header-code" id="revealCodeDisplay">——</div>
                    </div>

                    {{-- Body --}}
                    <div class="rwp-reveal-body">

                        {{-- Customer row --}}
                        <div class="rwp-customer-row">
                            <div style="position:relative;">
                                <div class="rwp-customer-avatar" id="revealAvatar">?</div>
                                <span class="rwp-avatar-tick"><i class="las la-check"></i></span>
                            </div>
                            <div class="rwp-customer-info">
                                <p class="rwp-customer-name" id="revealName">—</p>
                                <p class="rwp-customer-username" id="revealUsername">@—</p>
                                <span class="rwp-source-badge" id="revealSource">—</span>
                            </div>
                        </div>

                        {{-- Package value --}}
                        <div class="rwp-value-box">
                            <p class="rwp-value-label"><i class="las la-gift"></i> &nbsp;Welcome Package Value</p>
                            <p class="rwp-value-amount" id="revealAmount">—</p>
                            <p class="rwp-value-sub">Will be credited to your stockist wallet upon confirmation</p>
                        </div>

                        {{-- Action buttons --}}
                        <div class="rwp-reveal-actions">
                            <button type="button" class="rwp-cancel-btn" id="cancelRevealBtn">
                                <i class="las la-times"></i> Check Another
                            </button>
                            <button type="button" class="rwp-confirm-btn" id="confirmRedeemBtn">
                                <i class="las la-check-circle"></i> Confirm & Redeem Package
                            </button>
                        </div>

                    </div>
                </div>
            </div>
        </div>

        {{-- ── Redemption History ───────────────────────────── --}}
        <div class="rwp-history-card">
            <div class="rwp-history-head">
                <h6 class="rwp-history-title"><i class="las la-history"></i> Your Redemption History</h6>
                @if(!$history->isEmpty())
                <span class="rwp-count-pill">{{ $history->total() }} total</span>
                @endif
            </div>

            @if($history->isEmpty())
                <div class="sl-empty-state" style="padding:52px 24px;">
                    <div class="sl-empty-icon" style="font-size:3rem;color:#D1D5DB;margin-bottom:14px;">
                        <i class="las la-gift"></i>
                    </div>
                    <p class="sl-empty-title" style="font-weight:700;color:#374151;font-size:.95rem;margin-bottom:6px;">No Redemptions Yet</p>
                    <p class="sl-empty-sub" style="font-size:.82rem;color:#9CA3AF;max-width:280px;margin:0 auto;">Welcome packages you redeem for customers will appear here.</p>
                </div>
            @else
                <div class="rwp-history-grid">
                    @foreach($history as $pkg)
                    <div class="rwp-hcard">
                        <div class="rwp-hcard-avatar">{{ strtoupper(substr($pkg->user->fullname ?? '?', 0, 1)) }}</div>
                        <div class="rwp-hcard-info">
                            <p class="rwp-hcard-name">{{ $pkg->user->fullname ?? '—' }}</p>
                            <p class="rwp-hcard-meta">
                                <i class="las la-calendar-alt"></i>
                                {{ $pkg->redeemed_at?->format('M d, Y') }}
                                &bull;
                                {{ $pkg->redeemed_at?->format('H:i') }}
                            </p>
                        </div>
                        <div class="rwp-hcard-right">
                            <span class="rwp-hcard-amount">{{ showAmount($pkg->amount) }}</span>
                            <span class="rwp-hcard-source {{ $pkg->source }}">{{ ucfirst($pkg->source) }}</span>
                        </div>
                    </div>
                    @endforeach
                </div>

                @if($history->hasPages())
                <div style="padding:14px 22px;border-top:1px solid #F3F4F6;">
                    {{ paginateLinks($history) }}
                </div>
                @endif
            @endif
        </div>

    </div>
</div>
@endsection

@push('modal')
{{-- Success modal --}}
<div class="modal fade" id="rwpSuccessModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width:420px;">
        <div class="rwp-modal-content modal-content">
            <div class="rwp-modal-header-success">
                <div class="rwp-modal-icon-circle"><i class="las la-check-double"></i></div>
                <div class="rwp-confetti">🎁 🎉 ✨ 🎊 🎁</div>
                <h5>Package Redeemed!</h5>
            </div>
            <div class="rwp-modal-body">
                <p id="rwpSuccessMessage">The package has been successfully redeemed.</p>
                <button type="button" class="rwp-modal-btn" data-bs-dismiss="modal">Continue Scanning</button>
            </div>
        </div>
    </div>
</div>

{{-- Error modal --}}
<div class="modal fade" id="rwpErrorModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width:420px;">
        <div class="rwp-modal-content modal-content">
            <div class="rwp-modal-header-error">
                <div class="rwp-modal-icon-circle"><i class="las la-exclamation-triangle"></i></div>
                <h5>Cannot Redeem</h5>
            </div>
            <div class="rwp-modal-body">
                <p id="rwpErrorMessage">Something went wrong.</p>
                <button type="button" class="rwp-modal-btn" data-bs-dismiss="modal" style="background:linear-gradient(135deg,#D97706,#F59E0B);">Understood</button>
            </div>
        </div>
    </div>
</div>
@endpush

@push('script')
<script>
$(document).ready(function () {
    const OTP_LEN  = 10;
    const boxes    = Array.from({ length: OTP_LEN }, (_, i) => document.getElementById('otp' + i));
    let verifiedCode = null;

    /* ── OTP box behaviours ── */
    boxes.forEach(function (box, idx) {
        box.addEventListener('input', function () {
            let v = box.value.replace(/[^A-Za-z0-9]/g, '').toUpperCase().slice(-1);
            box.value = v;
            box.classList.toggle('rwp-otp-filled', v !== '');
            if (v && idx < OTP_LEN - 1) boxes[idx + 1].focus();
            updateVerifyState();
        });

        box.addEventListener('keydown', function (e) {
            if (e.key === 'Backspace') {
                if (box.value === '' && idx > 0) {
                    boxes[idx - 1].value = '';
                    boxes[idx - 1].classList.remove('rwp-otp-filled');
                    boxes[idx - 1].focus();
                } else {
                    box.value = '';
                    box.classList.remove('rwp-otp-filled');
                }
                updateVerifyState();
                e.preventDefault();
            }
            if (e.key === 'ArrowLeft'  && idx > 0)             { boxes[idx - 1].focus(); e.preventDefault(); }
            if (e.key === 'ArrowRight' && idx < OTP_LEN - 1)   { boxes[idx + 1].focus(); e.preventDefault(); }
        });

        /* Handle paste — distribute chars across boxes */
        box.addEventListener('paste', function (e) {
            e.preventDefault();
            const text = (e.clipboardData || window.clipboardData)
                .getData('text')
                .replace(/[^A-Za-z0-9]/g, '')
                .toUpperCase()
                .slice(0, OTP_LEN);
            text.split('').forEach(function (ch, ci) {
                const target = idx + ci;
                if (target < OTP_LEN) {
                    boxes[target].value = ch;
                    boxes[target].classList.add('rwp-otp-filled');
                }
            });
            const next = Math.min(idx + text.length, OTP_LEN - 1);
            boxes[next].focus();
            updateVerifyState();
        });
    });

    function getCode() {
        return boxes.map(b => b.value).join('');
    }

    function updateVerifyState() {
        const code = getCode();
        const full = code.length === OTP_LEN;
        $('#verifyBtn').prop('disabled', !full);
    }

    function isMobile() {
        return window.matchMedia('(max-width: 575px)').matches;
    }

    function resetBoxes() {
        boxes.forEach(function (b) {
            b.value = '';
            b.classList.remove('rwp-otp-filled');
        });
        $('#codeSingleInput').val('');
        updateVerifyState();
        if (isMobile()) {
            document.getElementById('codeSingleInput').focus();
        } else {
            boxes[0].focus();
        }
    }

    /* ── Mobile single input → sync chars into OTP boxes ── */
    $('#codeSingleInput').on('input', function () {
        let v = this.value.replace(/[^A-Za-z0-9]/g, '').toUpperCase().slice(0, OTP_LEN);
        this.value = v;
        boxes.forEach(function (b, i) {
            b.value = v[i] || '';
            b.classList.toggle('rwp-otp-filled', !!v[i]);
        });
        updateVerifyState();
    });

    /* ── Verify ── */
    $('#verifyBtn').on('click', function () {
        const code = getCode();
        if (code.length !== OTP_LEN) return;

        const $btn = $(this);
        $btn.prop('disabled', true);
        $('#verifyBtnText').html('<i class="las la-spinner la-spin me-1"></i> Verifying...');
        $('#otpWrap').addClass('rwp-otp-scanning');
        $('#codeSingleInput').prop('disabled', true).addClass('rwp-mobile-input--scanning');

        $.ajax({
            //url: "{{ route('user.stockist.welcome-pack.verify') }}",
            method: 'POST',
            data: { code: code, _token: "{{ csrf_token() }}" },
            success: function (response) {
                $('#otpWrap').removeClass('rwp-otp-scanning');
                $('#codeSingleInput').prop('disabled', false).removeClass('rwp-mobile-input--scanning');
                $('#verifyBtnText').html('Verify Code');

                if (response.success) {
                    verifiedCode = code;
                    const pkg    = response.package;
                    const name   = (pkg.fullname || '?').trim();

                    $('#revealAvatar').text(name.charAt(0).toUpperCase());
                    $('#revealName').text(name);
                    $('#revealUsername').text('@ ' + pkg.username);
                    $('#revealAmount').text(pkg.amount);
                    $('#revealCodeDisplay').text(code.slice(0,5) + '-' + code.slice(5));

                    const src = pkg.source.toLowerCase();
                    $('#revealSource')
                        .text(src.charAt(0).toUpperCase() + src.slice(1))
                        .attr('class', 'rwp-source-badge ' + src);

                    $('#packageReveal').removeClass('d-none');
                    $('html, body').animate({ scrollTop: $('#packageReveal').offset().top - 100 }, 350);
                } else {
                    $btn.prop('disabled', false);
                    $('#rwpErrorMessage').text(response.message);
                    $('#rwpErrorModal').modal('show');
                }
            },
            error: function () {
                $('#otpWrap').removeClass('rwp-otp-scanning');
                $('#codeSingleInput').prop('disabled', false).removeClass('rwp-mobile-input--scanning');
                $('#verifyBtnText').html('Verify Code');
                $btn.prop('disabled', false);
                $('#rwpErrorMessage').text('Something went wrong verifying this code. Please try again.');
                $('#rwpErrorModal').modal('show');
            }
        });
    });

    /* ── Cancel ── */
    $('#cancelRevealBtn').on('click', function () {
        verifiedCode = null;
        $('#packageReveal').addClass('d-none');
        resetBoxes();
    });

    /* ── Confirm redeem ── */
    $('#confirmRedeemBtn').on('click', function () {
        if (!verifiedCode) return;

        const $btn = $(this);
        $btn.prop('disabled', true).html('<i class="las la-spinner la-spin me-1"></i> Processing...');

        $.ajax({
            url: "{{ route('user.stockist.welcome-pack.redeem') }}",
            method: 'POST',
            data: { code: verifiedCode, _token: "{{ csrf_token() }}" },
            success: function (response) {
                $btn.prop('disabled', false).html('<i class="las la-check-circle"></i> Confirm & Redeem Package');

                if (response.success) {
                    $('#rwpSuccessMessage').text(response.message);
                    $('#rwpSuccessModal').modal('show');
                    $('#packageReveal').addClass('d-none');
                    verifiedCode = null;
                    resetBoxes();
                    setTimeout(function () { location.reload(); }, 2000);
                } else {
                    $('#rwpErrorMessage').text(response.message);
                    $('#rwpErrorModal').modal('show');
                }
            },
            error: function () {
                $btn.prop('disabled', false).html('<i class="las la-check-circle"></i> Confirm & Redeem Package');
                $('#rwpErrorMessage').text('Something went wrong redeeming this package. Please try again.');
                $('#rwpErrorModal').modal('show');
            }
        });
    });

    /* Focus appropriate input on load */
    if (isMobile()) {
        document.getElementById('codeSingleInput').focus();
    } else {
        boxes[0].focus();
    }
});
</script>
@endpush
