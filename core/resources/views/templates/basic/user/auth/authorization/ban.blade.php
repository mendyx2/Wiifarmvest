@extends($activeTemplate . 'layouts.app')

@section('panel')
<div class="ban-screen">
    <div class="ban-card">

        <div class="ban-card__visual">
            <div class="ban-card__icon-ring">
                <svg class="ban-card__icon-svg" viewBox="0 0 64 64" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                    <circle cx="32" cy="32" r="27" stroke="currentColor" stroke-width="2.5"/>
                    <path d="M12.5 12.5L51.5 51.5" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"/>
                </svg>
            </div>
            <span class="ban-card__visual-label">@lang('Suspended')</span>
        </div>

        <div class="ban-card__content">

            <div class="ban-card__badge">
                <svg width="10" height="10" viewBox="0 0 10 10" fill="currentColor" aria-hidden="true">
                    <circle cx="5" cy="5" r="5"/>
                </svg>
                @lang('Administrator Action')
            </div>

            <h2 class="ban-card__title">@lang('Your account access has been restricted.')</h2>

            <p class="ban-card__desc">
                @lang('An administrator has suspended your account. Review the stated reason below. If you believe this is in error, open a support ticket and our team will investigate.')
            </p>

            @if($user->ban_reason)
            <div class="ban-card__reason">
                <span class="ban-card__reason-tag">@lang('Reason for suspension')</span>
                <p class="ban-card__reason-body">{{ $user->ban_reason }}</p>
            </div>
            @endif

            <div class="ban-card__actions">
                <a href="{{ route('ticket.open') }}" class="ban-btn ban-btn--primary">
                    @lang('Open a Support Ticket')
                </a>
                <a href="{{ route('home') }}" class="ban-btn ban-btn--ghost">
                    @lang('Back to Home')
                </a>
            </div>

            <p class="ban-card__meta">
                @lang('Account') <strong>#{{ $user->id }}</strong>
                &nbsp;&middot;&nbsp;
                {{ now()->format('d M Y') }}
            </p>

        </div>
    </div>
</div>
@endsection

@push('style')
<style>
    .ban-screen {
        min-height: 78vh;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        padding: 48px 16px;
        background: #f2f4f1;
    }

    .ban-card {
        display: flex;
        width: 100%;
        max-width: 860px;
        background: #fff;
        border-radius: 18px;
        overflow: hidden;
        box-shadow: 0 2px 4px rgba(0,0,0,0.04), 0 12px 48px rgba(0,0,0,0.09);
    }

    /* ── Left panel ── */
    .ban-card__visual {
        background: #101e14;
        width: 230px;
        flex-shrink: 0;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 22px;
        padding: 56px 28px;
        position: relative;
        overflow: hidden;
    }

    /* Subtle diagonal texture — not a gradient, just line work */
    .ban-card__visual::before {
        content: '';
        position: absolute;
        inset: 0;
        background-image: repeating-linear-gradient(
            -52deg,
            transparent,
            transparent 20px,
            rgba(255,255,255,0.025) 20px,
            rgba(255,255,255,0.025) 21px
        );
        pointer-events: none;
    }

    .ban-card__icon-ring {
        width: 90px;
        height: 90px;
        border-radius: 50%;
        border: 1.5px solid rgba(222, 68, 80, 0.35);
        background: rgba(222, 68, 80, 0.10);
        display: flex;
        align-items: center;
        justify-content: center;
        position: relative;
        z-index: 1;
    }

    .ban-card__icon-svg {
        width: 42px;
        height: 42px;
        color: #e05465;
    }

    .ban-card__visual-label {
        font-size: 10px;
        font-weight: 700;
        letter-spacing: 0.18em;
        text-transform: uppercase;
        color: rgba(255,255,255,0.38);
        position: relative;
        z-index: 1;
    }

    /* ── Right panel ── */
    .ban-card__content {
        flex: 1;
        padding: 52px 48px;
    }

    .ban-card__badge {
        display: inline-flex;
        align-items: center;
        gap: 7px;
        background: #fff5f5;
        color: #b91c1c;
        font-size: 10.5px;
        font-weight: 700;
        letter-spacing: 0.09em;
        text-transform: uppercase;
        padding: 5px 13px 5px 10px;
        border-radius: 100px;
        border: 1px solid #fecaca;
        margin-bottom: 22px;
        line-height: 1;
    }

    .ban-card__title {
        font-size: 21px;
        font-weight: 700;
        color: #111827;
        line-height: 1.38;
        margin: 0 0 14px;
        max-width: 480px;
    }

    .ban-card__desc {
        font-size: 14px;
        color: #6b7280;
        line-height: 1.75;
        margin: 0 0 30px;
        max-width: 480px;
    }

    .ban-card__reason {
        background: #f9fafb;
        border-left: 3px solid #e05465;
        border-radius: 0 8px 8px 0;
        padding: 15px 20px;
        margin-bottom: 34px;
        max-width: 480px;
    }

    .ban-card__reason-tag {
        display: block;
        font-size: 10px;
        font-weight: 700;
        letter-spacing: 0.12em;
        text-transform: uppercase;
        color: #9ca3af;
        margin-bottom: 7px;
    }

    .ban-card__reason-body {
        font-size: 13.5px;
        color: #374151;
        line-height: 1.65;
        margin: 0;
    }

    /* ── Buttons ── */
    .ban-card__actions {
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
        margin-bottom: 34px;
    }

    .ban-btn {
        display: inline-block;
        padding: 10px 22px;
        border-radius: 8px;
        font-size: 13px;
        font-weight: 600;
        letter-spacing: 0.01em;
        text-decoration: none;
        border: 1.5px solid transparent;
        transition: background 0.17s, border-color 0.17s, box-shadow 0.17s, transform 0.14s;
    }

    .ban-btn--primary {
        background: #135D26;
        color: #fff;
        border-color: #135D26;
    }

    .ban-btn--primary:hover {
        background: #0e4a1e;
        border-color: #0e4a1e;
        color: #fff;
        transform: translateY(-1px);
        box-shadow: 0 4px 14px rgba(19, 93, 38, 0.28);
    }

    .ban-btn--ghost {
        background: transparent;
        color: #374151;
        border-color: #d1d5db;
    }

    .ban-btn--ghost:hover {
        background: #f3f4f6;
        color: #111827;
        border-color: #9ca3af;
    }

    /* ── Footer meta ── */
    .ban-card__meta {
        font-size: 12px;
        color: #9ca3af;
        margin: 0;
    }

    /* ── Mobile ── */
    @media (max-width: 620px) {
        .ban-card {
            flex-direction: column;
            border-radius: 14px;
        }

        .ban-card__visual {
            width: 100%;
            flex-direction: row;
            justify-content: flex-start;
            padding: 28px 28px;
            gap: 18px;
        }

        .ban-card__icon-ring {
            width: 56px;
            height: 56px;
            flex-shrink: 0;
        }

        .ban-card__icon-svg {
            width: 28px;
            height: 28px;
        }

        .ban-card__visual-label {
            font-size: 9px;
            letter-spacing: 0.14em;
        }

        .ban-card__content {
            padding: 32px 24px;
        }

        .ban-card__title {
            font-size: 18px;
        }

        .ban-card__actions {
            flex-direction: column;
        }

        .ban-btn {
            text-align: center;
        }
    }
</style>
@endpush
