@extends($activeTemplate . 'layouts.master')

@section('content')
<div class="gr-page">
<div class="container">

    <div class="gr-hero">
        <div class="gr-hero-icon"><i class="fas fa-handshake"></i></div>
        <h2>@lang('Guarantor Requests')</h2>
        <p>@lang('Users who have nominated you as their KYC guarantor. Accept to support their verification.')</p>
    </div>
    <div class="row g-3">
    @forelse($incoming as $req)
    @php
        $requester = $req->user;
        $rName  = $requester ? trim($requester->firstname . ' ' . $requester->lastname) : 'Unknown User';
        $rParts = explode(' ', $rName);
        $rInit  = strtoupper(substr($rParts[0] ?? '', 0, 1) . substr($rParts[1] ?? '', 0, 1));
    @endphp
    
    <div class="gr-card col-sm-6 col-md-6 col-lg-6 col-xl-4 col-12">

        {{-- Header --}}
        <div class="gr-card-header">
            <div class="gr-avatar">{{ $rInit }}</div>
            <div class="gr-user-info-block">
                <p class="gr-user-name">{{ $rName }}</p>
                @if($requester)
                <p class="gr-user-handle">{{ '@'.$requester->username }}</p>
                @endif
            </div>
            <span class="gr-badge gr-badge-{{ $req->status }}">
                @if($req->status === 'pending')   <i class="fas fa-hourglass-half"></i>
                @elseif($req->status === 'accepted') <i class="fas fa-check-circle"></i>
                @else                              <i class="fas fa-times-circle"></i>
                @endif
                @lang(ucfirst($req->status))
            </span>
        </div>

        {{-- Body --}}
        <div class="gr-card-body ">

            <div class="gr-meta">
                <div class="gr-meta-item">
                    <i class="fas fa-calendar-alt gr-meta-icon"></i>
                    <span class="gr-meta-text">@lang('Requested') {{ $req->created_at->diffForHumans() }}</span>
                </div>
                @if($req->responded_at)
                <div class="gr-meta-item">
                    <i class="fas fa-reply gr-meta-icon"></i>
                    <span class="gr-meta-text">@lang('Responded') {{ $req->responded_at->diffForHumans() }}</span>
                </div>
                @endif
            </div>

            @if($req->status === 'pending')
            <div class="gr-actions">
                <form action="{{ route('user.guarantor.accept', $req->id) }}" method="post">
                    @csrf
                    <button type="submit" class="gr-btn gr-btn-accept">
                        <i class="fas fa-check-circle"></i> @lang('Accept')
                    </button>
                </form>
                <form action="{{ route('user.guarantor.decline', $req->id) }}" method="post">
                    @csrf
                    <button type="submit" class="gr-btn gr-btn-decline">
                        <i class="fas fa-times-circle"></i> @lang('Decline')
                    </button>
                </form>
            </div>

            @elseif($req->status === 'accepted')
            <div class="gr-responded gr-responded-accepted">
                <i class="fas fa-check-circle"></i>
                <span>@lang('You accepted this request.')</span>
                <span class="gr-responded-date">{{ $req->responded_at?->format('d M Y, g:i A') }}</span>
            </div>

            @else
            <div class="gr-responded gr-responded-declined">
                <i class="fas fa-times-circle"></i>
                <span>@lang('You declined this request.')</span>
                <span class="gr-responded-date">{{ $req->responded_at?->format('d M Y, g:i A') }}</span>
            </div>
            @endif

        </div>
    </div>
    

    @empty
    <div class="gr-empty">
        <div class="gr-empty-icon"><i class="fas fa-handshake-slash"></i></div>
        <p class="gr-empty-title">@lang('No Guarantor Requests')</p>
        <p class="gr-empty-sub">@lang('You have not been nominated as a guarantor by anyone yet.')</p>
    </div>
    @endforelse

    {{ $incoming->links() }}
    </div>
</div>
</div>
@endsection
