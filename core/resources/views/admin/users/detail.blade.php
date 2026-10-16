@extends('admin.layouts.app')

@section('panel')
    {{-- ── Ambassador + Activate row ─────────────────────────────────────── --}}
    <div class="row mb-3">
        <div class="col-12">
            <div class="d-flex mt-4 flex-wrap align-items-center gap-3">

                {{-- Ambassador status badge + toggle --}}
                <form action="{{ route('admin.users.ambassador.toggle', $user->id) }}" method="POST" class="d-inline">
                    @csrf
                    @if($user->ambassador)
                        <button type="submit" class="btn btn-sm btn--warning btn--shadow"
                            onclick="return confirm('Remove Ambassador status from {{ addslashes($user->fullname) }}?')"
                            style="font-size:.8rem;padding:6px 16px;">
                            <i class="las la-star"></i> Ambassador
                            <span class="badge bg-white text-warning ms-1" style="font-size:.65rem;">Active · Click to remove</span>
                        </button>
                    @else
                    
                        <button type="submit" class="btn btn-sm btn--outline--warning btn--shadow"
                            onclick="return confirm('Tag {{ addslashes($user->fullname) }} as Ambassador?')"
                            style="font-size:.8rem;padding:6px 16px;">
                            <i class="las la-star"></i> Tag as Ambassador
                        </button>
                    @endif
                </form>
            </div>
        </div>
    </div>
    
    <div class="row"> 
        <div class="col-12">
            <div class="row gy-4">
                <div class="col-6 col-xxl-3 col-lg-4">
                    <x-widget type="2" value="{{ showAmount($user->balance) }}" title="Balance" style="7" link="#" icon="las la-money-bill-wave-alt" bg="indigo" />
                     
                </div>
                <div class="col-6 col-xxl-3 col-lg-4">
                    <x-widget type="2" value="{{ showAmount($user->visa) }}" title="VISA wallet" style="7" link="#" icon="la la-money" bg="8" />
                </div>
                @if($user->stockist)
                <div class="col-6 col-xxl-3 col-lg-4">
                    <x-widget type="2" value="{{ showAmount($user->stockist->wallet) }}" title="Stockist Balance" style="7" link="{{ route('admin.stockist.detail', $user->stockist->id) }}" icon="las la-money-bill-wave-alt" bg="14" />
                    
                </div>
                @endif
                <div class="col-6 col-xxl-3 col-lg-4">
                    <x-widget type="2" value="{{ showAmount($total_ref) }}" title="Referral Commission" style="7" link="{{ route('admin.report.referral.commission', $user->id) }}" icon="la la-user" bg="2" />
                </div>
                {{-- 
                <div class="col-6 col-xxl-3 col-lg-4">
                    <x-widget type="2" value="{{ showAmount($total_stage_out) }}" title="Total Stage-Out Commission" style="7" link="{{ route('admin.report.stageOut.commission', $user->id) }}" icon="la la-tree" bg="3" />
                </div>

                --}}

                <div class="col-6 col-xxl-3 col-lg-4">
                    <x-widget type="2" value="{{ showAmount($totalDeposit) }}" title="Deposits" style="7" link="{{ route('admin.deposit.list', $user->id) }}" icon="las la-wallet" bg="8" />
                </div>
                <div class="col-6 col-xxl-3 col-lg-4">
                    <x-widget type="2" value="{{ showAmount($totalWithdrawals) }}" title="Withdrawals" style="7" link="{{ route('admin.withdraw.data.all', $user->id) }}" icon="la la-bank" bg="6" />
                </div>
                <div class="col-6 col-xxl-3 col-lg-4">
                    <x-widget type="2" value="{{ $totalTransaction }}" title="Transactions" style="7" link="{{ route('admin.report.transaction', $user->id) }}" icon="las la-exchange-alt" bg="17" />
                </div>
                <div class="col-6 col-xxl-3 col-lg-4">
                    <x-widget type="2" value="{{ showAmount($user->total_invest) }}" title="Total Invest" style="7" link="{{ route('admin.report.invest', $user->id) }}" icon="la la-money" bg="17" />
                </div>
                
                <div class="col-6 col-xxl-3 col-lg-4">
                    <x-widget type="2" value="{{ showAmount($user->total_binary_com) }}" title="Total Invest Commission" style="7" link="{{ route('admin.report.binary.commission', $user->id) }}" icon="la la-tree" bg="3" />
                </div>

                {{-- ── MLM / Project Bonuses ── --}}
                <div class="col-12 mt-2 mb-0">
                    <p class="text-muted fw-semibold" style="font-size:.72rem;text-transform:uppercase;letter-spacing:.07em;margin-bottom:8px;">
                        <i class="las la-sitemap me-1"></i> MLM &amp; Re-purchase wallets
                        @if($user->project)
                            &nbsp;·&nbsp;
                            <span class="badge" style="background:{{ $user->project->color }};font-size:.65rem;">
                                <i class="{{ $user->project->icon }}"></i> {{ $user->project->title }}
                            </span>
                        @endif
                    </p>
                </div>
                <div class="col-6 col-xxl-3 col-lg-4">
                    <x-widget type="2" value="{{ showAmount($user->product_wallet ?? 0) }}" title="Re-purchase wallet" style="7" link="#" icon="las la-shopping-bag" bg="9" />
                </div>
                <div class="col-6 col-xxl-3 col-lg-4">
                    <x-widget type="2" value="{{ showAmount($user->direct_bonus ?? 0) }}" title="Direct Bonus (Lifetime)" style="7" link="#" icon="las la-hand-holding-usd" bg="2" />
                </div>
                <div class="col-6 col-xxl-3 col-lg-4">
                    <x-widget type="2" value="{{ showAmount($user->indirect_bonus ?? 0) }}" title="Indirect Bonus (Lifetime)" style="7" link="#" icon="las la-network-wired" bg="8" />
                </div>
                <div class="col-6 col-xxl-3 col-lg-4">
                    <x-widget type="2" value="{{ showAmount($user->upgrade_bonus ?? 0) }}" title="Upgrade Bonus (Lifetime)" style="7" link="#" icon="las la-arrow-up" bg="14" />
                </div>
                <div class="col-6 col-xxl-3 col-lg-4">
                    <x-widget type="2" value="{{ showAmount($user->unilevel_bonus ?? 0) }}" title="Unilevel Bonus (Lifetime)" style="7" link="#" icon="las la-sitemap" bg="17" />
                </div>
                <div class="col-6 col-xxl-3 col-lg-4">
                    <x-widget type="2" value="{{ showAmount($user->matching_bonus ?? 0) }}" title="Matching Bonus (Lifetime)" style="7" link="#" icon="las la-balance-scale" bg="6" />
                </div>
                <div class="col-6 col-xxl-3 col-lg-4">
                    <x-widget type="2" value="{{ showAmount($user->repurchase_award ?? 0) }}" title="Repurchase Award Wallet" style="7" link="#" icon="las la-medal" bg="14" />
                </div>
                <div class="col-6 col-xxl-3 col-lg-4">
                    <x-widget type="2" value="{{ showAmount($user->key_in_bonus ?? 0) }}" title="Key-In Bonus (Lifetime)" style="7" link="#" icon="las la-keyboard" bg="17" />
                </div>
                <div class="col-6 col-xxl-3 col-lg-4">
                    <x-widget type="2" value="{{ showAmount($user->acb ?? 0) }}" title="ACB Bonus (Achievers Celebrated)" style="7" link="#" icon="las la-star" bg="2" />
                </div>

                {{-- ── Binary Tree PV ── --}}
                @if($userMatrix)
                <div class="col-12 mt-2 mb-0">
                    <p class="text-muted fw-semibold" style="font-size:.72rem;text-transform:uppercase;letter-spacing:.07em;margin-bottom:8px;">
                        <i class="las la-project-diagram me-1"></i> Binary Tree PV
                        @if($userMatrix->position)
                            &nbsp;·&nbsp;
                            <span class="badge bg-{{ $userMatrix->position === 'left' ? 'primary' : ($userMatrix->position === 'right' ? 'danger' : 'success') }}">
                                {{ ucfirst($userMatrix->position) }} leg
                            </span>
                        @endif
                    </p>
                </div>
                <div class="col-6 col-xxl-3 col-lg-4">
                    <x-widget type="2" value="{{ number_format($userMatrix->pv_left ?? 0, 2) }}" title="PV Left Ranking" style="7" link="#" icon="las la-arrow-alt-circle-left" bg="1" />
                </div>
                <div class="col-6 col-xxl-3 col-lg-4">
                    <x-widget type="2" value="{{ number_format($userMatrix->pv_right ?? 0, 2) }}" title="PV Right Ranking" style="7" link="#" icon="las la-arrow-alt-circle-right" bg="13" />
                </div>
                <div class="col-6 col-xxl-3 col-lg-4">
                    <x-widget type="2" value="{{ number_format($userMatrix->pv_left_pairing ?? 0, 2) }}" title="PV Left (Matched)" style="7" link="#" icon="las la-check-double" bg="4" />
                </div>
                <div class="col-6 col-xxl-3 col-lg-4">
                    <x-widget type="2" value="{{ number_format($userMatrix->pv_right_pairing ?? 0, 2) }}" title="PV Right (Matched)" style="7" link="#" icon="las la-check-double" bg="16" />
                </div>
                @endif

                {{--
                <div class="col-6 col-xxl-3 col-lg-4">
                    <x-widget type="2" value="{{ getAmount($totalBvCut) }}" title="Total Cut BV" style="7" link="{{ route('admin.report.bvLog', $user->id) }}?type=cutBV" icon="la la-cut" bg="4" />
                </div>
                <div class="col-6 col-xxl-3 col-lg-4">
                    <x-widget type="2" value="{{ getAmount($user->userExtra->bv_left) }}" title="Left BV" style="7" link="{{ route('admin.report.bvLog', $user->id) }}?type=leftBV" icon="las la-arrow-alt-circle-left" bg="1" />
                </div>
                <div class="col-6 col-xxl-3 col-lg-4">
                    <x-widget type="2" value="{{ getAmount($user->userExtra->bv_right) }}" title="Right BV" style="7" link="{{ route('admin.report.bvLog', $user->id) }}?type=rightBV" icon="las la-arrow-alt-circle-right" bg="13" />
                </div>
                <div class="col-6 col-xxl-3 col-lg-4">
                    <x-widget type="2" value="{{ getAmount($user->userExtra->bv_left + $user->userExtra->bv_right) }}" title="Total BV" style="7" link="{{ route('admin.report.bvLog', $user->id) }}" icon="las la-arrow-alt-circle-right" bg="14" />
                </div>
                <div class="col-6 col-xxl-3 col-lg-4">
                    <x-widget type="2" value="{{ $totalOrder }}" title="Total Orders" style="7"
                        link="{{ route('admin.order.index', $user->id) }}" icon="las la-question-circle"
                        bg="16" />
                </div>
                --}}
               

            </div>

            <div class="udb-toolbar mt-4">
                @if(auth('admin')->user()->hasRole('Owner|super-admin'))
                <div class="udb-group">
                    <p class="udb-group-label"><i class="las la-wallet"></i>@lang('Wallet Adjustments')</p>
                    <div class="udb-group-row">
                        <button class="btn btn--success btn--shadow udb-btn bal-btn" data-bs-toggle="modal" data-bs-target="#addSubModal" data-act="add">
                            <i class="las la-plus-circle"></i> @lang('Money Box')
                        </button>
                        <button class="btn btn--danger btn--shadow udb-btn bal-btn" data-bs-toggle="modal" data-bs-target="#addSubModal" data-act="sub">
                            <i class="las la-minus-circle"></i> @lang('Money Box')
                        </button>
                        <button class="btn btn--success btn--shadow udb-btn bal-btn1" data-bs-toggle="modal" data-bs-target="#addSubModalVisa" data-act="add">
                            <i class="las la-plus-circle"></i> @lang('VISA Wallet')
                        </button>
                        <button class="btn btn--danger btn--shadow udb-btn bal-btn1" data-bs-toggle="modal" data-bs-target="#addSubModalVisa" data-act="sub">
                            <i class="las la-minus-circle"></i> @lang('VISA Wallet')
                        </button>
                        <button class="btn btn--success btn--shadow udb-btn bal-btn-pw" data-bs-toggle="modal" data-bs-target="#addSubModalProductWallet" data-act="add">
                            <i class="las la-plus-circle"></i> @lang('Re-purchase Wallet')
                        </button>
                        <button class="btn btn--danger btn--shadow udb-btn bal-btn-pw" data-bs-toggle="modal" data-bs-target="#addSubModalProductWallet" data-act="sub">
                            <i class="las la-minus-circle"></i> @lang('Re-purchase Wallet')
                        </button>
                    </div>
                </div>
                @endif

                <div class="udb-group">
                    <p class="udb-group-label"><i class="las la-link"></i>@lang('Quick Links')</p>
                    <div class="udb-group-row">
                        <a class="btn btn--primary btn--shadow udb-btn" href="{{ route('admin.report.login.history') }}?search={{ $user->username }}">
                            <i class="las la-list-alt"></i>@lang('Logins')
                        </a>
                        <a class="btn btn--secondary btn--shadow udb-btn" href="{{ route('admin.users.notification.log', $user->id) }}">
                            <i class="las la-bell"></i>@lang('Notifications')
                        </a>
                        <a class="btn btn--secondary btn--shadow udb-btn" href="{{ route('admin.welcome-pack.index', ['user' => $user->username]) }}">
                            <i class="las la-gift"></i>@lang('Welcome Packages')
                        </a>
                        @if ($user->kyc_data)
                            <a class="btn btn--dark btn--shadow udb-btn" href="{{ route('admin.users.kyc.details', $user->id) }}" target="_blank">
                                <i class="las la-user-check"></i>@lang('KYC Data')
                            </a>
                        @endif
                        <a class="btn btn--primary btn--shadow udb-btn" href="{{ route('admin.users.other.tree', $user->username) }}">
                            <i class="las la-project-diagram"></i>@lang('User Tree')
                        </a>
                        <a class="btn btn--info btn--shadow udb-btn" href="{{ route('admin.users.referral', $user->id) }}">
                            <i class="las la-users"></i>@lang('User Referrals')
                        </a>
                    </div>
                </div>

                <div class="udb-group">
                    <p class="udb-group-label"><i class="las la-shield-alt"></i>@lang('Account Controls')</p>
                    <div class="udb-group-row">
                        @if ($user->status == Status::ACTIVE)
                            <button class="btn btn--warning btn--shadow udb-btn userStatus" data-bs-toggle="modal" data-bs-target="#userStatusModal" type="button">
                                <i class="las la-ban"></i>@lang('Ban User')
                            </button>
                        @else
                            <button class="btn btn--success btn--shadow udb-btn userStatus" data-bs-toggle="modal" data-bs-target="#userStatusModal" type="button">
                                <i class="las la-undo"></i>@lang('Unban User')
                            </button>
                        @endif

                        @if(!$user->withdrawal_blocked)
                            <button class="btn btn--danger btn--shadow udb-btn" data-bs-toggle="modal" data-bs-target="#withdrawalBlockModal" type="button">
                                <i class="las la-lock"></i> @lang('Block Withdrawal')
                            </button>
                        @else
                            <button class="btn btn--success btn--shadow udb-btn" data-bs-toggle="modal" data-bs-target="#withdrawalBlockModal" type="button">
                                <i class="las la-lock-open"></i> @lang('Unblock Withdrawal')
                            </button>
                        @endif

                        @if(auth('admin')->user()->hasRole('super-admin'))
                        <button class="btn btn--primary btn--shadow udb-btn udb-btn-cta"
                                data-bs-toggle="modal"
                                data-bs-target="#buySharesModal"
                                data-balance="{{ $user->balance }}"
                                id="openBuySharesBtn">
                            <i class="las la-chart-pie"></i>@lang('Buy Shares')
                        </button>
                        @endif
                    </div>
                </div>
            </div>


            <div class="row">
                <div class="col-12 col-md-5 col-xxl-4">
                    <div class="card mt-30">
                        <div class="card-body p-0">
                            <div class="bg--white p-3">
                                <div class="profile-info-top">
                                    <div class="profile-image">
                                        <img id="output"
                                            src="{{ getImage(getFilePath('userProfile') . '/' . $user->image, null, true) }}"
                                            alt="image">
                                    </div>
                                    {{--
                                    <div class="plan-info">
                                        <p class="plan-info-name">
                                            <span>@lang('Current plan')</span>
                                            @if($user->plan->name ?? false)
                                            <strong class="text--success">{{__($user->plan->name)}}</strong>
                                            @else
                                            <strong class="text-danger">@lang('N/A')</strong>
                                            @endif
                                        </p>

                                    </div>
                                    --}}
                                </div>
                                <ul class="list-group mt-3">
                                    <li class="list-group-item d-flex justify-content-between">
                                        <span>@lang('Project')</span> {{ __($user->project?->title) }}
                                    </li>
                                    <li class="list-group-item d-flex justify-content-between">
                                        <span>@lang('Name')</span> {{ __($user->fullname) }}
                                    </li>
                                    <li class="list-group-item rounded-0 d-flex justify-content-between">
                                        <span>@lang('Username')</span> {{ $user->username }}
                                    </li>
                                    <li class="list-group-item rounded-0 d-flex justify-content-between">
                                        <span>@lang('TRX')</span> {{ $user->trx }}
                                    </li>
                                    <li class="list-group-item rounded-0 d-flex justify-content-between">
                                        <span>@lang('Email')</span> {{ $user->email }}
                                    </li>
                                    <li class="list-group-item rounded-0 d-flex justify-content-between">
                                        <span>@lang('Mobile Number')</span> {{ $user->dial_code }}{{ $user->mobile }}
                                    </li>
                                    <li class="list-group-item rounded-0 d-flex justify-content-between">
                                        <span>@lang('Ref By')</span> <a href="{{ route('admin.users.detail', @$user->refBy->id) }}">{{'@'.@$user->refBy->username ?? 'N/A' }}</a>
                                    </li>
                                    <li class="list-group-item rounded-0 d-flex justify-content-between">
                                        <span>@lang('State ')</span> {{ $user->state }}
                                    </li>
                                    <li class="list-group-item rounded-0 d-flex justify-content-between">
                                        <span>@lang('Address ')</span>{{ $user->address }}
                                    </li>
                                    

                                    <li class="list-group-item d-flex justify-content-between">
                                        <span>@lang('Joined at')</span> {{ showDateTime($user->created_at) }}
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                     <!-- Sponsor section -->
                    <div class="card mt-30">
                        <div class="card-header">
                            <h5 class="card-title mb-0">@lang('Current Sponsor ') {{'@'.@$user->refBy->username ?? 'N/A' }}</h5>
                        </div> 
                        <div class="card-body">
                            <form action="{{ route('admin.users.update_again', [$user->id]) }}" method="POST"
                                enctype="multipart/form-data">
                                @csrf

                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="form-group">
                                            <label>@lang('Current Sponsor')</label>
                                            <input class="form-control" name="csponsor" type="text" value="{{@$user->refBy->username}}" 
                                                readonly>
                                        </div>
                                    </div>

                                    <div class="col-md-12">
                                        <div class="form-group">
                                            <label class="form-control-label">@lang('New sponsor')</label>
                                            <input class="form-control" name="nsponsor" type="text"
                                                required>
                                        </div>
                                    </div>

                                   
                                    
                                    <div class="col-md-12">
                                        <button class="btn btn--primary w-100 h-45" type="submit">@lang('Submit')
                                        </button>
                                    </div>

                                    @if ($sponsorChangeCount)
                                        <div class="col-md-12 mt-3 text-center">
                                            <a class="btn btn-sm btn-outline--dark" href="{{ route('admin.users.sponsor.history', $user->id) }}">
                                                <i class="las la-history"></i> @lang('View Sponsor History')
                                                <span class="badge badge--dark ms-1">{{ $sponsorChangeCount }}</span>
                                            </a>
                                        </div>
                                    @endif
                                </div>
                            </form>
                        </div>
                    </div>
                    <!-- Change username -->
                    <div class="card mt-30">
                        <div class="card-header">
                            <h5 class="card-title mb-0">@lang('Change Username here')</h5>
                        </div>  
                        <div class="card-body">
                            <form action="{{ route('admin.users.update_again1', [$user->id]) }}" method="POST"
                                enctype="multipart/form-data"> 
                                @csrf

                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="form-group">
                                            <label>@lang('Current Usernae')</label>
                                            <input class="form-control" name="username" type="text" value="{{$user->username}}" 
                                                readonly>
                                        </div>
                                    </div>

                                    <div class="col-md-12">
                                        <div class="form-group">
                                            <label class="form-control-label">@lang('New username')</label>
                                            <input class="form-control" name="nusername" type="text"
                                                required>
                                        </div>
                                    </div>

                                   
                                    
                                    <div class="col-md-12">
                                        <button class="btn btn--primary w-100 h-45" type="submit">@lang('Submit')
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- End the sponsors -->
                </div>
                <div class="col-12 col-md-7 col-xxl-8">
                    <div class="card mt-30">
                        <div class="card-header">
                            <h5 class="card-title mb-0">@lang('Information of') {{ $user->fullname }}</h5>
                        </div>
                        <div class="card-body">
                            <form action="{{ route('admin.users.update', [$user->id]) }}" method="POST"
                                enctype="multipart/form-data">
                                @csrf

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>@lang('First Name')</label>
                                            <input class="form-control" name="firstname" type="text"
                                                value="{{ $user->firstname }}" required>
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="form-control-label">@lang('Last Name')</label>
                                            <input class="form-control" name="lastname" type="text"
                                                value="{{ $user->lastname }}" required>
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>@lang('Email') </label>
                                            <input class="form-control" name="email" type="email"
                                                value="{{ $user->email }}" required>
                                        </div>
                                    </div>
                                    
                                    
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>@lang('First Name')</label>
                                            <input class="form-control" name="firstname" type="text"
                                                value="{{ $user->firstname }}" required>
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="form-control-label">@lang('Last Name')</label>
                                            <input class="form-control" name="lastname" type="text"
                                                value="{{ $user->lastname }}" required>
                                        </div>
                                    </div>

                                    
                                    
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>@lang('Bank Name')</label>
                                            <input class="form-control" name="bname" type="text"
                                                value="{{ $user->bname }}" required>
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="form-control-label">@lang('Account name')</label>
                                            <input class="form-control" name="aname" type="text"
                                                value="{{ $user->aname }}" required>
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>@lang('Account number') </label>
                                            <input class="form-control" name="ano" type="number"
                                                value="{{ $user->ano }}" required>
                                        </div>
                                    </div>
                                    
                                   

                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>@lang('Mobile Number') </label>
                                            <div class="input-group">
                                                <span class="input-group-text mobile-code">+{{ $user->dial_code }}</span>
                                                <input class="form-control checkUser" id="mobile" name="mobile"
                                                    type="number" value="{{ $user->mobile }}" required>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-md-12">
                                        <div class="form-group">
                                            <label>@lang('Address')</label>
                                            <input class="form-control" name="address" type="text"
                                                value="{{ @$user->address }}">
                                        </div>
                                    </div>

                                    <div class="col-sm-6 col-xl-6 col-xxl-3">
                                        <div class="form-group">
                                            <label>@lang('City')</label>
                                            <input class="form-control" name="city" type="text"
                                                value="{{ @$user->city }}">
                                        </div>
                                    </div>

                                    <div class="col-sm-6 col-xl-6 col-xxl-3">
                                        <div class="form-group">
                                            <label>@lang('State')</label>
                                            <input class="form-control" name="state" type="text"
                                                value="{{ @$user->state }}">
                                        </div>
                                    </div>

                                    <div class="col-sm-6 col-xl-6 col-xxl-3">
                                        <div class="form-group">
                                            <label>@lang('Zip/Postal')</label>
                                            <input class="form-control" name="zip" type="text"
                                                value="{{ @$user->zip }}">
                                        </div>
                                    </div>

                                    <div class="col-sm-6 col-xl-6 col-xxl-3">
                                        <div class="form-group">
                                            <label>@lang('Country') <span class="text--danger">*</span></label>
                                            <select class="form-control select2" name="country">
                                                @foreach ($countries as $key => $country)
                                                    <option data-mobile_code="{{ $country->dial_code }}"
                                                        value="{{ $key }}" @selected($user->country_code == $key)>
                                                        {{ __($country->country) }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>

                                    <div class="col-sm-6 col-xxl-3">
                                        <div class="form-group">
                                            <label>@lang('Email Verification')</label>
                                            <input name="ev" data-width="100%" data-onstyle="-success"
                                                data-offstyle="-danger" data-bs-toggle="toggle"
                                                data-on="@lang('Verified')" data-off="@lang('Unverified')"
                                                type="checkbox" @if ($user->ev) checked @endif>
                                        </div>
                                    </div>

                                    <div class="col-sm-6 col-xxl-3">
                                        <div class="form-group">
                                            <label>@lang('Mobile Verification')</label>
                                            <input name="sv" data-width="100%" data-onstyle="-success"
                                                data-offstyle="-danger" data-bs-toggle="toggle"
                                                data-on="@lang('Verified')" data-off="@lang('Unverified')"
                                                type="checkbox" @if ($user->sv) checked @endif>
                                        </div>
                                    </div>
                                    <div class="col-sm-6 col-xxl-3">
                                        <div class="form-group">
                                            <label>@lang('2FA Verification') </label>
                                            <input name="ts" data-width="100%" data-height="50"
                                                data-onstyle="-success" data-offstyle="-danger" data-bs-toggle="toggle"
                                                data-on="@lang('Enable')" data-off="@lang('Disable')"
                                                type="checkbox" @if ($user->ts) checked @endif>
                                        </div>
                                    </div>
                                    <div class="col-sm-6 col-xxl-3">
                                        <div class="form-group">
                                            <label>@lang('KYC') </label>
                                            <input name="kv" data-width="100%" data-height="50"
                                                data-onstyle="-success" data-offstyle="-danger" data-bs-toggle="toggle"
                                                data-on="@lang('Verified')" data-off="@lang('Unverified')"
                                                type="checkbox" @if ($user->kv == Status::KYC_VERIFIED) checked @endif>
                                        </div>
                                    </div>
                                    <div class="col-md-12">
                                        <button class="btn btn--primary w-100 h-45" type="submit">@lang('Submit')
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>

                    <div class="card mt-30">
                        <div class="card-body">
                            <h5 class="card-title mb-4 border-bottom pb-2">@lang('Change Password')</h5>

                            <form action="{{ route('admin.users.update_password', [$user->id]) }}" method="POST" enctype="multipart/form-data">
                                @csrf
                                 <div class="form-group">
                                    <label>@lang('New Password')</label>
                                    <input class="form-control" type="password" name="password" required>
                                </div>

                                <div class="form-group">
                                    <label>@lang('Confirm Password')</label>
                                    <input class="form-control" type="password" name="password_confirmation" required>
                                </div>
                                <button type="submit" class="btn btn--primary w-100 btn-lg h-45">@lang('Submit')</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            {{-- <div class="card mt-30">
                <div class="card-header">
                    <h5 class="card-title mb-0">@lang('Information of') {{ $user->fullname }}</h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('admin.users.update', [$user->id]) }}" method="POST" enctype="multipart/form-data">
                        @csrf

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>@lang('First Name')</label>
                                    <input class="form-control" name="firstname" type="text" value="{{ $user->firstname }}" required>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-control-label">@lang('Last Name')</label>
                                    <input class="form-control" name="lastname" type="text" value="{{ $user->lastname }}" required>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>@lang('Email') </label>
                                    <input class="form-control" name="email" type="email" value="{{ $user->email }}" required>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>@lang('Mobile Number') </label>
                                    <div class="input-group">
                                        <span class="input-group-text mobile-code">+{{ $user->dial_code }}</span>
                                        <input class="form-control checkUser" id="mobile" name="mobile" type="number" value="{{ $user->mobile }}" required>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-12">
                                <div class="form-group">
                                    <label>@lang('Address')</label>
                                    <input class="form-control" name="address" type="text" value="{{ @$user->address }}">
                                </div>
                            </div>

                            <div class="col-xl-3 col-md-6">
                                <div class="form-group">
                                    <label>@lang('City')</label>
                                    <input class="form-control" name="city" type="text" value="{{ @$user->city }}">
                                </div>
                            </div>

                            <div class="col-xl-3 col-md-6">
                                <div class="form-group">
                                    <label>@lang('State')</label>
                                    <input class="form-control" name="state" type="text" value="{{ @$user->state }}">
                                </div>
                            </div>

                            <div class="col-xl-3 col-md-6">
                                <div class="form-group">
                                    <label>@lang('Zip/Postal')</label>
                                    <input class="form-control" name="zip" type="text" value="{{ @$user->zip }}">
                                </div>
                            </div>

                            <div class="col-xl-3 col-md-6">
                                <div class="form-group">
                                    <label>@lang('Country') <span class="text--danger">*</span></label>
                                    <select class="form-control select2" name="country">
                                        @foreach ($countries as $key => $country)
                                            <option data-mobile_code="{{ $country->dial_code }}" value="{{ $key }}" @selected($user->country_code == $key)>{{ __($country->country) }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <div class="col-xl-3 col-md-6 col-12">
                                <div class="form-group">
                                    <label>@lang('Email Verification')</label>
                                    <input name="ev" data-width="100%" data-onstyle="-success" data-offstyle="-danger" data-bs-toggle="toggle" data-on="@lang('Verified')" data-off="@lang('Unverified')" type="checkbox" @if ($user->ev) checked @endif>
                                </div>
                            </div>

                            <div class="col-xl-3 col-md-6 col-12">
                                <div class="form-group">
                                    <label>@lang('Mobile Verification')</label>
                                    <input name="sv" data-width="100%" data-onstyle="-success" data-offstyle="-danger" data-bs-toggle="toggle" data-on="@lang('Verified')" data-off="@lang('Unverified')" type="checkbox" @if ($user->sv) checked @endif>
                                </div>
                            </div>
                            <div class="col-xl-3 col-12">
                                <div class="form-group">
                                    <label>@lang('2FA Verification') </label>
                                    <input name="ts" data-width="100%" data-height="50" data-onstyle="-success" data-offstyle="-danger" data-bs-toggle="toggle" data-on="@lang('Enable')" data-off="@lang('Disable')" type="checkbox" @if ($user->ts) checked @endif>
                                </div>
                            </div>
                            <div class="col-xl-3 col-12">
                                <div class="form-group">
                                    <label>@lang('KYC') </label>
                                    <input name="kv" data-width="100%" data-height="50" data-onstyle="-success" data-offstyle="-danger" data-bs-toggle="toggle" data-on="@lang('Verified')" data-off="@lang('Unverified')" type="checkbox" @if ($user->kv == Status::KYC_VERIFIED) checked @endif>
                                </div>
                            </div>
                            <div class="col-md-12">
                                <button class="btn btn--primary w-100 h-45" type="submit">@lang('Submit')
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div> --}}


        </div>
    </div>
    
    
    {{-- Add Sub Balance MODAL --}}
    <div class="modal fade" id="addSubModal" role="dialog" tabindex="-1">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><span class="type"></span> <span>@lang('Balance')</span></h5>
                    <button class="close" data-bs-dismiss="modal" type="button" aria-label="Close">
                        <i class="las la-times"></i>
                    </button>
                </div>
                <form class="balanceAddSub disableSubmission" action="{{ route('admin.users.add.sub.balance', $user->id) }}" method="POST">
                    @csrf
                    <input name="act" type="hidden">
                    <div class="modal-body">
                        <div class="form-group">
                            <label>@lang('Amount')</label>
                            <div class="input-group">
                                <input class="form-control" name="amount" type="number" step="any" placeholder="@lang('Please provide positive amount')" required>
                                <div class="input-group-text">{{ __(gs('cur_text')) }}</div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>@lang('Remark')</label>
                            <textarea class="form-control" name="remark" placeholder="@lang('Remark')" rows="4" required></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button class="btn btn--primary h-45 w-100" type="submit">@lang('Submit')</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    {{-- Add Sub VISA MODAL --}}
    <div class="modal fade" id="addSubModalVisa" role="dialog" tabindex="-1">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><span class="type"></span> <span>@lang('VISA wallet')</span></h5>
                    <button class="close" data-bs-dismiss="modal" type="button" aria-label="Close">
                        <i class="las la-times"></i>
                    </button>
                </div>
                <form class="visaAddSub disableSubmission" action="{{ route('admin.users.add.sub.balance.visa', $user->id) }}" method="POST">
                    @csrf
                    <input name="act" type="hidden">
                    <div class="modal-body">
                        <div class="form-group">
                            <label>@lang('Amount')</label>
                            <div class="input-group">
                                <input class="form-control" name="amount" type="number" step="any" placeholder="@lang('Please provide positive amount')" required>
                                <div class="input-group-text">{{ __(gs('cur_text')) }}</div>
                            </div>
                        </div>
                        {{-- 
                        <div class="form-group">
                            <label>@lang('Leave zero if the person is doing 7,000 registration and 1 if its 9,000 registration')</label>
                            <div class="input-group">
                                <input class="form-control" name="stats" type="number" step="any" placeholder="@lang('Please provide positive 1 or 0')" required>
                               
                            </div>
                        </div>

                        --}}
                        
                        <div class="form-group">
                            <label>@lang('Remark')</label>
                            <textarea class="form-control" name="remark" placeholder="@lang('Remark')" rows="4" required></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button class="btn btn--primary h-45 w-100" type="submit">@lang('Submit')</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    

    {{-- Add / Sub Re-purchase wallet MODAL --}}
    <div class="modal fade" id="addSubModalProductWallet" role="dialog" tabindex="-1">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <span class="pw-type"></span>
                        <span>@lang('Re-purchase wallet')</span>
                    </h5>
                    <button class="close" data-bs-dismiss="modal" type="button" aria-label="Close">
                        <i class="las la-times"></i>
                    </button>
                </div>
                <form class="productWalletAddSub disableSubmission"
                      action="{{ route('admin.users.add.sub.balance.product.wallet', $user->id) }}"
                      method="POST">
                    @csrf
                    <input name="act" type="hidden">
                    <div class="modal-body">
                        <div class="alert alert-info d-flex align-items-center gap-2 py-2 px-3 mb-3" role="alert" style="font-size:.82rem;">
                            <i class="las la-info-circle fs-5"></i>
                            <span>
                                @lang('Current balance:')
                                <strong>{{ showAmount($user->product_wallet ?? 0) }}</strong>
                                &mdash; @lang('Used exclusively for product purchases.')
                            </span>
                        </div>
                        <div class="form-group">
                            <label>@lang('Amount')</label>
                            <div class="input-group">
                                <input class="form-control" name="amount" type="number"
                                       step="any" min="0.01"
                                       placeholder="@lang('Enter a positive amount')" required>
                                <div class="input-group-text">{{ __(gs('cur_text')) }}</div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>@lang('Remark')</label>
                            <textarea class="form-control" name="remark"
                                      placeholder="@lang('Reason for this adjustment')"
                                      rows="4" required></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button class="btn btn--primary h-45 w-100" type="submit">
                            @lang('Submit')
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Withdrawal Block / Unblock MODAL --}}
    <div class="modal fade" id="withdrawalBlockModal" role="dialog" tabindex="-1">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        @if(!$user->withdrawal_blocked)
                            <i class="las la-lock text-danger me-1"></i> @lang('Block Withdrawal Access')
                        @else
                            <i class="las la-lock-open text-success me-1"></i> @lang('Unblock Withdrawal Access')
                        @endif
                    </h5>
                    <button class="close" data-bs-dismiss="modal" type="button" aria-label="Close">
                        <i class="las la-times"></i>
                    </button>
                </div>
                <form action="{{ route('admin.users.withdrawal.block', $user->id) }}" method="POST">
                    @csrf
                    <div class="modal-body">
                        @if(!$user->withdrawal_blocked)
                            <p class="mb-2">@lang('This will prevent the user from submitting any withdrawal request until you unblock them.')</p>
                            <div class="form-group">
                                <label>@lang('Reason') <span class="text-danger">*</span></label>
                                <textarea class="form-control" name="reason" rows="4" placeholder="@lang('Provide a reason for blocking withdrawals...')" required></textarea>
                            </div>
                        @else
                            <div class="alert alert-warning">
                                <strong>@lang('Current block reason:')</strong><br>
                                {{ $user->withdrawal_block_reason }}
                            </div>
                            <h5 class="text-center mt-3">@lang('Restore this user\'s withdrawal access?')</h5>
                        @endif
                    </div>
                    <div class="modal-footer">
                        @if(!$user->withdrawal_blocked)
                            <button class="btn btn--danger h-45 w-100" type="submit">@lang('Block Withdrawal')</button>
                        @else
                            <button class="btn btn--dark" data-bs-dismiss="modal" type="button">@lang('Cancel')</button>
                            <button class="btn btn--success" type="submit">@lang('Yes, Unblock')</button>
                        @endif
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="userStatusModal" role="dialog" tabindex="-1">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        @if ($user->status == Status::USER_ACTIVE)
                            @lang('Ban User')
                        @else
                            @lang('Unban User')
                        @endif
                    </h5>
                    <button class="close" data-bs-dismiss="modal" type="button" aria-label="Close">
                        <i class="las la-times"></i>
                    </button>
                </div>
                <form action="{{ route('admin.users.status', $user->id) }}" method="POST">
                    @csrf
                    <div class="modal-body">
                        @if ($user->status == Status::USER_ACTIVE)
                            <h6 class="mb-2">@lang('If you ban this user he/she won\'t able to access his/her dashboard.')</h6>
                            <div class="form-group">
                                <label>@lang('Reason')</label>
                                <textarea class="form-control" name="reason" rows="4" required></textarea>
                            </div>
                        @else
                            <p><span>@lang('Ban reason was'):</span></p>
                            <p>{{ $user->ban_reason }}</p>
                            <h4 class="mt-3 text-center">@lang('Are you sure to unban this user?')</h4>
                        @endif
                    </div>
                    <div class="modal-footer">
                        @if ($user->status == Status::USER_ACTIVE)
                            <button class="btn btn--primary h-45 w-100" type="submit">@lang('Submit')</button>
                        @else
                            <button class="btn btn--dark" data-bs-dismiss="modal" type="button">@lang('No')</button>
                            <button class="btn btn--primary" type="submit">@lang('Yes')</button>
                        @endif
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('breadcrumb-plugins')
    @if(auth('admin')->user()->hasRole('super-admin'))
        <a class="btn btn-sm btn-outline--primary" href="{{ route('admin.users.login', $user->id) }}" target="_blank"><i class="las la-sign-in-alt"></i>@lang('Login as User')</a>
    @endif
@endpush

@push('script')
    <script>
        (function($) {
            "use strict"


            $('.bal-btn').on('click', function() {

                $('.balanceAddSub')[0].reset();

                var act = $(this).data('act');
                $('#addSubModal').find('input[name=act]').val(act);
                if (act == 'add') {
                    $('.type').text('Add');
                } else {
                    $('.type').text('Subtract');
                }
            });

            $('.bal-btn1').on('click', function() {
                $('.visaAddSub')[0].reset();
                var act = $(this).data('act');
                $('#addSubModalVisa').find('input[name=act]').val(act);
                if (act == 'add') {
                    $('.type').text('Add');
                } else {
                    $('.type').text('Subtract');
                }
            });

            $('.bal-btn-pw').on('click', function() {
                $('.productWalletAddSub')[0].reset();
                var act = $(this).data('act');
                $('#addSubModalProductWallet').find('input[name=act]').val(act);
                var label = act === 'add' ? 'Credit' : 'Debit';
                $('#addSubModalProductWallet .pw-type').text(label);
            });

            let mobileElement = $('.mobile-code');
            $('select[name=country]').on('change', function() {
                mobileElement.text(`+${$('select[name=country] :selected').data('mobile_code')}`);
            });

        })(jQuery);
    </script>
@endpush


{{-- ═══════════════════════════════════════════
     BUY SHARES MODAL
════════════════════════════════════════════ --}}
<div class="modal fade" id="buySharesModal" tabindex="-1" aria-labelledby="buySharesModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width:520px">
        <div class="modal-content buy-shares-modal">

            {{-- Header --}}
            <div class="bsm-header">
                <div class="bsm-header-icon">
                    <i class="las la-chart-pie"></i>
                </div>
                <div>
                    <h5 class="bsm-title" id="buySharesModalLabel">Buy Shares</h5>
                    <p class="bsm-subtitle mb-0">
                        Allocate shares to <strong>{{ $user->username }}</strong>
                    </p>
                </div>
                <button type="button" class="bsm-close ms-auto" data-bs-dismiss="modal">
                    <i class="las la-times"></i>
                </button>
            </div>

            {{-- User Balance Banner --}}
            <div class="bsm-balance-bar">
                <i class="las la-wallet me-2"></i>
                <span>User Balance:</span>
                <strong id="bsmUserBalance" class="ms-1">{{ showAmount($user->balance) }}</strong>
            </div>

            {{-- Body --}}
            <div class="modal-body px-4 pb-2 pt-3">
                <form id="buySharesForm">
                    @csrf

                    {{-- Plan Selector --}}
                    <div class="mb-3">
                        <label class="bsm-label">Select Plan</label>
                        <select id="bsmPlanSelect" name="plan_id" class="form-select bsm-select" required>
                            <option value="">— Choose a plan —</option>
                            @foreach($plans as $plan)
                                <option value="{{ $plan->id }}"
                                        data-price="{{ $plan->price }}"
                                        data-name="{{ $plan->name }}"
                                        data-qty="{{ $plan->quantity }}">
                                    {{ $plan->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Quick Action --}}
                    <div class="mb-3">
                        <label class="bsm-label"><i class="las la-bolt me-1"></i>Quick Action</label>
                        <div class="bsm-quick-actions" id="bsmQuickActions">
                            <button type="button" class="bsm-quick-btn" data-qty="5">
                                <span class="bsm-quick-num">5</span>
                                <span class="bsm-quick-unit">units</span>
                                <i class="las la-check-circle bsm-quick-check"></i>
                            </button>
                            <button type="button" class="bsm-quick-btn" data-qty="10">
                                <span class="bsm-quick-num">10</span>
                                <span class="bsm-quick-unit">units</span>
                                <i class="las la-check-circle bsm-quick-check"></i>
                            </button>
                            <button type="button" class="bsm-quick-btn" data-qty="20">
                                <span class="bsm-quick-num">20</span>
                                <span class="bsm-quick-unit">units</span>
                                <i class="las la-check-circle bsm-quick-check"></i>
                            </button>
                            <button type="button" class="bsm-quick-btn" data-qty="50">
                                <span class="bsm-quick-num">50</span>
                                <span class="bsm-quick-unit">units</span>
                                <i class="las la-check-circle bsm-quick-check"></i>
                            </button>
                            <button type="button" class="bsm-quick-btn" data-qty="100">
                                <span class="bsm-quick-num">100</span>
                                <span class="bsm-quick-unit">units</span>
                                <i class="las la-check-circle bsm-quick-check"></i>
                            </button>
                        </div>
                    </div>

                    {{-- Quantity Input --}}
                    <div class="mb-3">
                        <label class="bsm-label">Number of Units</label>
                        <div class="bsm-qty-group">
                            <button type="button" class="bsm-qty-btn" id="bsmQtyMinus">
                                <i class="las la-minus"></i>
                            </button>
                            <input type="number" id="bsmQtyInput" name="quantity"
                                   class="form-control bsm-qty-input text-center"
                                   value="1" min="1" step="1" required>
                            <button type="button" class="bsm-qty-btn" id="bsmQtyPlus">
                                <i class="las la-plus"></i>
                            </button>
                        </div>
                        <p class="bsm-hint mt-1 mb-0">
                            <i class="las la-info-circle me-1"></i>Minimum purchase is 1 unit.
                        </p>
                    </div>

                    {{-- Live Total Calculator --}}
                    <div class="bsm-total-card" id="bsmTotalCard">
                        <div class="bsm-calc-row">
                            <span class="bsm-calc-label">Unit Price</span>
                            <span class="bsm-calc-value" id="bsmCalcUnit">—</span>
                        </div>
                        <div class="bsm-calc-row">
                            <span class="bsm-calc-label">×&nbsp;&nbsp;Quantity</span>
                            <span class="bsm-calc-value" id="bsmCalcQty">—</span>
                        </div>
                        <div class="bsm-calc-divider"></div>
                        <div class="bsm-calc-row bsm-calc-total">
                            <span class="bsm-calc-label">Total Cost</span>
                            <span class="bsm-calc-value text--primary fw-bold" id="bsmCalcTotal">—</span>
                        </div>

                        {{-- Balance sufficiency indicator --}}
                        <div id="bsmBalanceCheck" class="bsm-balance-check mt-2" style="display:none"></div>
                    </div>

                    {{-- Force allocate (shown only when balance is insufficient) --}}
                    <div id="bsmForceRow" class="bsm-force-row mt-3" style="display:none">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="bsmForce" name="force" value="1">
                            <label class="form-check-label" for="bsmForce">
                                <i class="las la-exclamation-triangle me-1 text-warning"></i>
                                <strong>Force allocate</strong> — skip balance deduction
                            </label>
                        </div>
                    </div>

                </form>
            </div>

            {{-- Footer --}}
            <div class="modal-footer px-4 pb-4 pt-2 border-0">
                <button type="button" class="btn btn-outline--dark" data-bs-dismiss="modal">
                    Cancel
                </button>
                <button type="button" class="btn btn--primary px-4" id="bsmSubmitBtn" disabled>
                    <span id="bsmSubmitText"><i class="las la-check me-1"></i>Allocate Shares</span>
                    <span id="bsmSubmitSpinner" style="display:none">
                        <span class="spinner-border spinner-border-sm me-1"></span>Processing…
                    </span>
                </button>
            </div>
        </div>
    </div>
</div>

@push('style')
    <style>
        /* ── Buy Shares Modal ── */
        .buy-shares-modal {
            border: none;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 20px 60px rgba(0,0,0,.18);
        }

        /* Header */
        .bsm-header {
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 22px 24px 16px;
            background: linear-gradient(135deg, var(--primary) 0%, #3a56d4 100%);
            color: #fff;
        }
        .bsm-header-icon {
            width: 46px;
            height: 46px;
            min-width: 46px;
            border-radius: 12px;
            background: rgba(255,255,255,.18);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
        }
        .bsm-title  { color:#fff; font-size:1.1rem; font-weight:700; margin-bottom:2px; }
        .bsm-subtitle { color:rgba(255,255,255,.8); font-size:.82rem; }
        .bsm-close {
            background: rgba(255,255,255,.15);
            border: none;
            color: #fff;
            width: 32px;
            height: 32px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            font-size: 1rem;
            transition: background .15s;
        }
        .bsm-close:hover { background: rgba(255,255,255,.28); }

        /* Balance bar */
        .bsm-balance-bar {
            display: flex;
            align-items: center;
            background: #f0f4ff;
            border-bottom: 1px solid #e0e7ff;
            padding: 10px 24px;
            font-size: .84rem;
            color: #444;
        }
        .bsm-balance-bar strong { color: #2563eb; font-size: .9rem; }

        /* Label */
        .bsm-label {
            font-size: .8rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: .06em;
            color: #666;
            margin-bottom: 6px;
            display: block;
        }

        /* Plan select */
        .bsm-select {
            border-radius: 10px;
            border: 1.5px solid #dde3f0;
            padding: 10px 14px;
            font-size: .9rem;
            transition: border-color .2s;
        }
        .bsm-select:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(37,99,235,.1);
        }

        /* Quantity group */
        .bsm-qty-group {
            display: flex;
            align-items: center;
            gap: 0;
            border: 1.5px solid #dde3f0;
            border-radius: 10px;
            overflow: hidden;
        }
        .bsm-qty-btn {
            background: #f0f4ff;
            border: none;
            width: 44px;
            height: 44px;
            font-size: 1rem;
            color: #3a56d4;
            cursor: pointer;
            transition: background .15s;
            flex-shrink: 0;
        }
        .bsm-qty-btn:hover { background: #dde6ff; }
        .bsm-qty-input {
            border: none;
            border-left: 1.5px solid #dde3f0;
            border-right: 1.5px solid #dde3f0;
            border-radius: 0;
            font-size: 1.1rem;
            font-weight: 700;
            height: 44px;
        }
        .bsm-qty-input:focus { box-shadow: none; border-color: #dde3f0; }

        /* Total calculator card */
        .bsm-total-card {
            background: #f8f9fc;
            border: 1.5px solid #e0e7ff;
            border-radius: 12px;
            padding: 16px 18px;
        }
        .bsm-calc-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 4px 0;
        }
        .bsm-calc-label { font-size: .84rem; color: #666; }
        .bsm-calc-value { font-size: .9rem; font-weight: 600; color: #333; }
        .bsm-calc-divider {
            border-top: 1.5px dashed #d0d8ee;
            margin: 8px 0;
        }
        .bsm-calc-total .bsm-calc-label { font-weight: 700; color: #333; font-size: .9rem; }
        .bsm-calc-total .bsm-calc-value { font-size: 1.1rem; }

        /* Balance check */
        .bsm-balance-check {
            font-size: .82rem;
            padding: 7px 12px;
            border-radius: 7px;
        }
        .bsm-balance-ok   { background: #d1f5e0; color: #0f5132; border: 1px solid #a3e6c0; }
        .bsm-balance-warn { background: #fff3cd; color: #664d03; border: 1px solid #ffe69c; }

        /* Force-allocate row */
        .bsm-force-row {
            background: #fff8ec;
            border: 1px dashed #f59e0b;
            border-radius: 7px;
            padding: 10px 14px;
            font-size: .83rem;
        }
        .bsm-force-row .form-check-label { cursor: pointer; color: #78350f; }

        /* Hint text */
        .bsm-hint { font-size: .78rem; color: #888; }

        /* Responsive */
        @media (max-width:480px) {
            .bsm-header { padding: 16px; }
        }
    </style>

    <style>
        .profile-info-top {
            display: flex;
            align-items: center;
            justify-content: flex-start;
            gap: 16px;
        }

        .profile-image {
            flex-shrink: 0;
        }

        .plan-info {
            flex: 1;
        }

        .plan-info-name {
            display: flex;
            flex-direction: column;
            gap: 6px;
            line-height: 1.3;
            font-size: 20px;
        }

        .plan-info-name span {
            font-size: 24px;
        }

        .profile-image img {
            height: 100px;
            width: 100px;
            border-radius: 50%;
            border: 6px solid #00000010;
        }
        .plan-info{
            background: #fafafa;
            padding: 10px;
            border-radius: 5px;
        }
    </style>
@endpush

@push('script')
<script>
(function ($) {
    'use strict';

    /* ── Helpers ── */
    var userBalance    = parseFloat('{{ $user->balance }}') || 0;
    var buySharesRoute = '{{ route("admin.users.buy.shares", $user->id) }}';
    var csrfToken      = '{{ csrf_token() }}';

    /* Format a number as currency (replicates showAmount display) */
    function fmtMoney(n) {
        return parseFloat(n).toLocaleString('en-NG', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2,
        });
    }

    /* ── State ── */
    var selectedPlan = null;   // { price, name, qty }

    /* ── DOM refs ── */
    var $planSelect    = $('#bsmPlanSelect');
    var $qtyInput      = $('#bsmQtyInput');
    var $quickBtns     = $('#bsmQuickActions .bsm-quick-btn');
    var $calcUnit      = $('#bsmCalcUnit');
    var $calcQty       = $('#bsmCalcQty');
    var $calcTotal     = $('#bsmCalcTotal');
    var $balanceCheck  = $('#bsmBalanceCheck');
    var $submitBtn     = $('#bsmSubmitBtn');
    var $submitText    = $('#bsmSubmitText');
    var $submitSpinner = $('#bsmSubmitSpinner');
    var $forceRow      = $('#bsmForceRow');

    /* ── Recalculate totals whenever plan or qty changes ── */
    function recalculate() {
        if (!selectedPlan) {
            $submitBtn.prop('disabled', true);
            return;
        }

        var qty   = Math.max(1, parseInt($qtyInput.val()) || 1);
        var total = selectedPlan.price * qty;

        $calcUnit.text(fmtMoney(selectedPlan.price));
        $calcQty.text(qty);
        $calcTotal.text(fmtMoney(total));

        /* Balance sufficiency */
        var sufficient = userBalance >= total;
        $balanceCheck.show();

        if (sufficient) {
            $balanceCheck
                .removeClass('bsm-balance-warn')
                .addClass('bsm-balance-ok')
                .html('<i class="las la-check-circle me-1"></i>User has sufficient balance (' + fmtMoney(userBalance) + ')');
            $forceRow.hide();
            $('#bsmForce').prop('checked', false);
        } else {
            $balanceCheck
                .removeClass('bsm-balance-ok')
                .addClass('bsm-balance-warn')
                .html('<i class="las la-exclamation-triangle me-1"></i>Insufficient balance — shortfall: ' + fmtMoney(total - userBalance) + '. Use "Force allocate" to proceed without deducting.');
            $forceRow.show();
        }

        $submitBtn.prop('disabled', false);

        /* Highlight the quick-action button matching the current quantity */
        $quickBtns.removeClass('active').filter('[data-qty="' + qty + '"]').addClass('active');
    }

    /* ── Plan change ── */
    $planSelect.on('change', function () {
        var $opt = $(this).find(':selected');
        var price = parseFloat($opt.data('price')) || 0;
        var qty   = parseInt($opt.data('qty'))   || 0;
        var name  = $opt.data('name') || '';

        if (!$opt.val()) {
            selectedPlan = null;
            $balanceCheck.hide();
            $submitBtn.prop('disabled', true);
            return;
        }

        selectedPlan = { price: price, name: name, qty: qty };
        recalculate();
    });

    /* ── Quantity ± buttons ── */
    $('#bsmQtyMinus').on('click', function () {
        var v = parseInt($qtyInput.val()) || 1;
        if (v > 1) { $qtyInput.val(v - 1).trigger('input'); }
    });
    $('#bsmQtyPlus').on('click', function () {
        var v = parseInt($qtyInput.val()) || 1;
        $qtyInput.val(v + 1).trigger('input');
    });
    $qtyInput.on('input', recalculate);

    /* ── Quick action buttons ── */
    $quickBtns.on('click', function () {
        $qtyInput.val($(this).data('qty')).trigger('input');
    });

    /* ── Auto-select the first plan whenever the modal is opened ── */
    $('#buySharesModal').on('show.bs.modal', function () {
        var $firstPlan = $planSelect.find('option[value!=""]').first();
        if ($firstPlan.length) {
            $planSelect.val($firstPlan.val()).trigger('change');
        }
    });

    /* ── Reset modal when closed ── */
    $('#buySharesModal').on('hidden.bs.modal', function () {
        $planSelect.val('');
        $qtyInput.val(1);
        selectedPlan = null;
        $balanceCheck.hide().removeClass('bsm-balance-ok bsm-balance-warn');
        $forceRow.hide();
        $('#bsmForce').prop('checked', false);
        $quickBtns.removeClass('active');
        $submitBtn.prop('disabled', true);
        $submitText.show();
        $submitSpinner.hide();
        /* clear any leftover alerts */
        $('#bsmFormAlert').remove();
    });

    /* ── Submit via AJAX ── */
    $submitBtn.on('click', function () {
        if (!selectedPlan) return;

        var qty   = parseInt($qtyInput.val()) || 0;
        

        if (qty < 1) {
            showAlert('danger', 'Please enter a valid quantity.');
            return; 
        }

        /* Loading state */
        $submitText.hide();
        $submitSpinner.show();
        $submitBtn.prop('disabled', true);

        $.ajax({
            url:    buySharesRoute,
            method: 'POST',
            data: {
                _token:   csrfToken,
                plan_id:  $planSelect.val(),
                quantity: qty,
                force:    $('#bsmForce').is(':checked') ? 1 : 0,
            },
            success: function (res) {
                $submitText.show();
                $submitSpinner.hide();
                showAlert('success', '<i class="las la-check-circle me-1"></i>' + res.message);
                /* Disable submit so admin can't double-click */
                $submitBtn.prop('disabled', true);
                /* Auto-close after 2 s */
                setTimeout(function () { $('#buySharesModal').modal('hide'); }, 2000);
            },
            error: function (xhr) {
                $submitText.show();
                $submitSpinner.hide();
                $submitBtn.prop('disabled', false);

                var json = xhr.responseJSON || {};
                if (json.status === 'insufficient') {
                    showAlert('warning', '<i class="las la-exclamation-triangle me-1"></i>' + json.message);
                   
                } else {
                    showAlert('danger', '<i class="las la-times-circle me-1"></i>' + (json.message || 'Something went wrong. Please try again.'));
                }
            },
        });
    });

    /* ── Helper: inject alert inside modal body ── */
    function showAlert(type, html) {
        $('#bsmFormAlert').remove();
        var cls = type === 'success' ? 'bsm-balance-ok'
                : type === 'warning' ? 'bsm-balance-warn'
                : 'alert alert-danger';
        var $alert = $('<div id="bsmFormAlert" class="bsm-balance-check mt-3 ' + cls + '">' + html + '</div>');
        $('#buySharesForm').append($alert);
    }

})(jQuery);
</script>
@endpush
