@extends('admin.layouts.app')
@section('panel')

{{-- ── Lightbox ── --}}
<div id="kycLightbox" onclick="kycLbClose()" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.88);z-index:99999;align-items:center;justify-content:center;cursor:zoom-out;padding:20px;">
    <img id="kycLightboxImg" src="" alt="KYC Attachment"
         style="max-width:92vw;max-height:88vh;border-radius:10px;box-shadow:0 12px 48px rgba(0,0,0,.6);object-fit:contain;cursor:default;"
         onclick="event.stopPropagation()">
    <button onclick="kycLbClose()" style="position:fixed;top:18px;right:22px;background:rgba(255,255,255,.15);border:none;color:#fff;font-size:1.6rem;line-height:1;width:40px;height:40px;border-radius:50%;cursor:pointer;display:flex;align-items:center;justify-content:center;">&times;</button>
</div>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-body">

                {{-- ── Dynamic KYC form fields ── --}}
                @if($user->kyc_data)
                    <ul class="list-group">
                        @foreach($user->kyc_data as $val)
                        @continue(!$val->value)
                        <li class="list-group-item d-flex justify-content-between align-items-center flex-wrap gap-2">
                            <span>{{ __($val->name) }}</span>
                            <span>
                                @if($val->type == 'checkbox')
                                    {{ implode(', ', (array)$val->value) }}
                                @elseif($val->type == 'file')
                                    @php
                                        $fHash = encrypt(getFilePath('verify') . '/' . $val->value);
                                        $ext   = strtolower(pathinfo($val->value, PATHINFO_EXTENSION));
                                        $isImg = in_array($ext, ['jpg','jpeg','png','gif','webp']);
                                    @endphp
                                    <div class="d-flex gap-2 align-items-center">
                                        @if($isImg)
                                        <a href="#" onclick="kycLbOpen('{{ route('admin.view.attachment', $fHash) }}');return false;"
                                           class="btn btn-sm btn-outline-primary py-1 px-2">
                                            <i class="fas fa-eye"></i> @lang('View')
                                        </a>
                                        @endif
                                        <a href="{{ route('admin.download.attachment', $fHash) }}"
                                           class="btn btn-sm btn-outline-secondary py-1 px-2">
                                            <i class="fas fa-download"></i> @lang('Download')
                                        </a>
                                    </div>
                                @else
                                    {{ __($val->value) }}
                                @endif
                            </span>
                        </li>
                        @endforeach
                    </ul>
                @else
                    <h5 class="text-center">@lang('KYC data not found')</h5>
                @endif

                {{-- ── Identity Verification ── --}}
                @if($user->nin || $user->id_card_type || $user->id_card_image)
                <h6 class="mt-4 mb-2 text-muted">@lang('Identity Verification')</h6>
                <ul class="list-group mb-3">
                    @if($user->nin)
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        @lang('NIN')
                        <span class="fw-bold">{{ $user->nin }}</span>
                    </li>
                    @endif
                    @if($user->id_card_type)
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        @lang('ID Card Type')
                        <span>{{ ucwords(str_replace('_', ' ', $user->id_card_type)) }}</span>
                    </li>
                    @endif
                    @if($user->id_card_image)
                    @php $idHash = encrypt(getFilePath('verify') . '/id_cards/' . $user->id_card_image); @endphp
                    <li class="list-group-item d-flex justify-content-between align-items-center flex-wrap gap-2">
                        @lang('ID Card Image')
                        <div class="d-flex gap-2 align-items-center">
                            {{-- thumbnail --}}
                            <img src="{{ route('admin.view.attachment', $idHash) }}"
                                 alt="ID Card"
                                 onclick="kycLbOpen('{{ route('admin.view.attachment', $idHash) }}')"
                                 style="height:48px;width:72px;object-fit:cover;border-radius:6px;cursor:zoom-in;border:1px solid #dee2e6;">
                            <a href="#" onclick="kycLbOpen('{{ route('admin.view.attachment', $idHash) }}');return false;"
                               class="btn btn-sm btn-outline-primary py-1 px-2">
                                <i class="fas fa-eye"></i> @lang('View')
                            </a>
                            <a href="{{ route('admin.download.attachment', $idHash) }}"
                               class="btn btn-sm btn-outline-secondary py-1 px-2">
                                <i class="fas fa-download"></i> @lang('Download')
                            </a>
                        </div>
                    </li>
                    @endif
                </ul>
                @endif

                {{-- ── Guarantor ── --}}
                @php
                    $gReq = \App\Models\GuarantorRequest::where('user_id', $user->id)->with('guarantor')->latest()->first();
                @endphp
                @if($gReq)
                <h6 class="mt-3 mb-2 text-muted">@lang('Guarantor')</h6>
                <ul class="list-group mb-3">
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        @lang('Nominated Guarantor')
                        @if($gReq->guarantor)
                            <span>{{ $gReq->guarantor->firstname }} {{ $gReq->guarantor->lastname }}
                                <span class="text-muted">({{ $gReq->guarantor->username }})</span>
                            </span>
                        @else
                            <span class="text-muted">@lang('Deleted user')</span>
                        @endif
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        @lang('Guarantor Status')
                        @php $gBadge = ['pending' => 'warning', 'accepted' => 'success', 'declined' => 'danger']; @endphp
                        <span class="badge bg-{{ $gBadge[$gReq->status] ?? 'secondary' }}">
                            {{ ucfirst($gReq->status) }}
                        </span>
                    </li>
                    @if($gReq->responded_at)
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        @lang('Responded At')
                        <span>{{ $gReq->responded_at->format('d M Y, g:i A') }}</span>
                    </li>
                    @endif
                </ul>
                @endif

                {{-- ── Rejection reason (unverified) ── --}}
                @if($user->kv == Status::KYC_UNVERIFIED && $user->kyc_rejection_reason)
                <div class="my-3">
                    <h6>@lang('Rejection Reason')</h6>
                    <p>{{ $user->kyc_rejection_reason }}</p>
                </div>
                @endif

                {{-- ── Approve / Reject actions ── --}}
                @if($user->kv == Status::KYC_PENDING)
                <div class="d-flex flex-wrap justify-content-end mt-3">
                    <button class="btn btn-outline--danger me-3"
                            data-bs-toggle="modal" data-bs-target="#kycRejectionModal">
                        <i class="las la-ban"></i> @lang('Reject')
                    </button>
                    <button class="btn btn-outline--success confirmationBtn"
                            data-question="@lang('Are you sure to approve this documents?')"
                            data-action="{{ route('admin.users.kyc.approve', $user->id) }}">
                        <i class="las la-check"></i> @lang('Approve')
                    </button>
                </div>
                @endif

            </div>
        </div>
    </div>
</div>

{{-- ── Rejection modal ── --}}
<div id="kycRejectionModal" class="modal fade" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">@lang('Reject KYC Documents')</h5>
                <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close">
                    <i class="las la-times"></i>
                </button>
            </div>
            <form action="{{ route('admin.users.kyc.reject', $user->id) }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="alert alert-primary p-3">
                        @lang('If you reject these documents, the user will be able to re-submit new documents and these documents will be replaced by new documents.')
                    </div>
                    <div class="form-group">
                        <label>@lang('Rejection Reason')</label>
                        <textarea class="form-control" name="reason" rows="4" required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn--primary h-45 w-100">@lang('Submit')</button>
                </div>
            </form>
        </div>
    </div>
</div>

<x-confirmation-modal />

@push('script')
<script>
function kycLbOpen(url) {
    var lb  = document.getElementById('kycLightbox');
    var img = document.getElementById('kycLightboxImg');
    img.src = url;
    lb.style.display = 'flex';
    document.body.style.overflow = 'hidden';
}
function kycLbClose() {
    document.getElementById('kycLightbox').style.display = 'none';
    document.getElementById('kycLightboxImg').src = '';
    document.body.style.overflow = '';
}
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') kycLbClose();
});
</script>
@endpush

@endsection
