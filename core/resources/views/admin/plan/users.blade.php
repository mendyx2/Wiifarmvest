@extends('admin.layouts.app')
@section('panel')
    <div class="row justify-content-center">
        <div class="col-md-12">
            <div class="card">
                <div class="card-body p-0">
                    <div class="table-responsive--lg table-responsive">
                        <table class="table--light style--two table">
                            <thead>
                                <tr>
                                    <th>@lang('username')</th>
                                    <th>@lang('Unit cost')</th>
                                    <th>@lang('Reserved Unit')</th>
                                    <th>@lang('Holding')</th>
                                    <th>@lang('Value')</th>
                                    <th>@lang('Status')</th>
                                    <th>@lang('Action')</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($rinvestments as $rinvestment)
                                    <tr>

                                        <td>
                                            <span class="fw-bold">{{ $rinvestment->user->fullname }}</span>
                                            <br>
                                            <span class="small">
                                                <a href="{{ route('admin.users.detail', $rinvestment->user_id) }}"><span>@</span>{{ $rinvestment->user->username }}</a>
                                            </span>
                                        </td>
                                        <td>{{ showAmount($rinvestment->unit_cost) }}</td>
                                        <td>{{ showAmount($rinvestment->units) }}</td>
                                        <td> {{ showAmount($rinvestment->five) }}</td>
                                        <td> {{ showAmount($rinvestment->unit_cost * $rinvestment->units) }}</td>
                                        <td>@php echo $rinvestment->statusBadge @endphp </td>
                                        <td>
                                            <div class="button--group">
                                               
                                                @if ($rinvestment->status == Status::ENABLE)
                                                    <button class="btn btn-outline--danger btn-sm confirmationBtn" data-question="@lang('Are you sure to disable this plan?')"
                                                        data-action="{{ route('admin.plan.statusUser', $rinvestment->id) }}">
                                                        <i class="las la-eye-slash"></i>@lang('Disable')
                                                    </button>
                                                @else
                                                    <button class="btn btn-outline--success confirmationBtn btn-sm" data-question="@lang('Are you sure to enable this plan?')"
                                                        data-action="{{ route('admin.plan.statusUser', $rinvestment->id) }}">
                                                        <i class="las la-eye"></i>@lang('Enable')
                                                    </button>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td class="text-muted text-center" colspan="100%">{{ __($emptyMessage) }}</td>
                                    </tr>
                                @endforelse

                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
  

    <x-confirmation-modal />
@endsection

