@extends('admin.layouts.app')

@section('panel')
<div class="state-page">

    <div class="state-layout">

        {{-- TABLE --}}
        <div class="state-table-col">
            <div class="state-card">
                <div class="state-card-head">
                    <span class="state-count">{{ $states->count() }} {{ Str::plural('state', $states->count()) }}</span>
                    <button class="state-add-btn" id="openAddDrawer">
                        <i class="las la-plus"></i> Add State
                    </button>
                </div>
                <div class="table-responsive">
                    <table class="table state-tbl">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>State Name</th>
                                <th>Code</th>
                                <th class="text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($states as $i => $state)
                            <tr>
                                <td class="state-row-num">{{ $i + 1 }}</td>
                                <td class="state-row-name">{{ $state->name }}</td>
                                <td><span class="state-code-chip">{{ $state->code }}</span></td>
                                <td class="text-right">
                                    <div class="state-row-actions">
                                        <button class="state-action-edit"
                                            data-id="{{ $state->id }}"
                                            data-name="{{ $state->name }}"
                                            data-code="{{ $state->code }}"
                                            onclick="openEdit(this)">
                                            <i class="las la-pen"></i>
                                        </button>
                                        <form action="{{ route('admin.state.destroy', $state->id) }}" method="POST"
                                            onsubmit="return confirm('Delete {{ addslashes($state->name) }}? This will also remove any associated product prices.')">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="state-action-del">
                                                <i class="las la-trash-alt"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="4">
                                    <div class="state-empty-row">
                                        <i class="las la-map"></i>
                                        <p>No states yet. Add your first state to get started.</p>
                                    </div>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>

</div>

{{-- DRAWER OVERLAY --}}
<div class="state-overlay" id="stateOverlay"></div>

{{-- DRAWER --}}
<div class="state-drawer" id="stateDrawer">
    <div class="state-drawer-head">
        <span id="drawerTitle">Add State</span>
        <button class="state-drawer-close" id="closeDrawer"><i class="las la-times"></i></button>
    </div>
    <div class="state-drawer-body">
        <form id="stateForm" method="POST">
            @csrf
            <div class="state-form-field">
                <label class="state-form-label">State Name <span class="pf-req">*</span></label>
                <input class="state-form-input" id="fieldName" name="name" type="text" placeholder="e.g. Lagos" required maxlength="100">
            </div>
            <div class="state-form-field">
                <label class="state-form-label">State Code <span class="pf-req">*</span></label>
                <input class="state-form-input state-form-input--upper" id="fieldCode" name="code" type="text"
                    placeholder="e.g. LG" maxlength="10" required
                    oninput="this.value=this.value.toUpperCase()">
                <p class="state-form-hint">Short uppercase abbreviation used for filtering and display</p>
            </div>
            <button type="submit" class="state-form-submit" id="drawerSubmit">Save State</button>
        </form>
    </div>
</div>

@endsection

@push('breadcrumb-plugins')
    <a class="btn btn-sm btn-outline--dark" href="{{ route('admin.product.index') }}">
        <i class="las la-store"></i> Products
    </a>
@endpush

@push('script')
<script>
"use strict";
(function($) {

    const addUrl   = "{{ route('admin.state.store') }}";
    const updateBase = "{{ url('admin/state') }}/";

    function openDrawer(title, action, name, code) {
        $('#drawerTitle').text(title);
        $('#stateForm').attr('action', action);
        $('#fieldName').val(name || '');
        $('#fieldCode').val(code || '');
        $('#stateOverlay, #stateDrawer').addClass('active');
        $('#fieldName').focus();
    }

    function closeDrawer() {
        $('#stateOverlay, #stateDrawer').removeClass('active');
        $('#stateForm')[0].reset();
    }

    $('#openAddDrawer').on('click', function() {
        openDrawer('Add State', addUrl, '', '');
    });

    window.openEdit = function(btn) {
        const id   = $(btn).data('id');
        const name = $(btn).data('name');
        const code = $(btn).data('code');
        openDrawer('Edit State', updateBase + id, name, code);
    };

    $('#closeDrawer, #stateOverlay').on('click', closeDrawer);

})(jQuery);
</script>
@endpush
