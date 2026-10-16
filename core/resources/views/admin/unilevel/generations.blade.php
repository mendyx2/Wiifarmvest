@extends('admin.layouts.app')

@section('panel')

<div class="row g-4">

    {{-- ── Left: Generation list ─────────────────────────────── --}}
    <div class="col-lg-8">
        <div class="card b-radius--10 overflow-hidden h-100">
            <div class="card-header d-flex align-items-center justify-content-between gap-2">
                <div>
                    <h5 class="card-title mb-0">Unilevel Generations</h5>
                    <small class="text-muted">Define each generation level and its PRB share percentage</small>
                </div>
                <button class="btn btn--primary btn-sm ul-open-create" type="button">
                    <i class="las la-plus me-1"></i> Add Generation
                </button>
            </div>

            {{-- Summary strip --}}
            <div class="ul-summary-strip">
                <div class="ul-summary-item">
                    <span class="ul-summary-val">{{ $generations->count() }}</span>
                    <span class="ul-summary-lbl">Configured</span>
                </div>
                <div class="ul-summary-item">
                    <span class="ul-summary-val text--success">{{ $generations->where('status', true)->count() }}</span>
                    <span class="ul-summary-lbl">Active</span>
                </div>
                <div class="ul-summary-item">
                    <span class="ul-summary-val">{{ count($available) }}</span>
                    <span class="ul-summary-lbl">Remaining slots</span>
                </div>
                <div class="ul-summary-item">
                    <span class="ul-summary-val">{{ number_format($generations->sum('percentage'), 2) }}%</span>
                    <span class="ul-summary-lbl">Total %</span>
                </div>
            </div>

            <div class="card-body p-0">
                @if($generations->isEmpty())
                    <div class="ul-empty-state">
                        <div class="ul-empty-icon"><i class="las la-layer-group"></i></div>
                        <p class="ul-empty-title">No Generations Yet</p>
                        <p class="ul-empty-sub">Add your first generation to start configuring unilevel bonus distribution.</p>
                        <button class="btn btn--primary btn-sm ul-open-create" type="button">
                            <i class="las la-plus me-1"></i> Add First Generation
                        </button>
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="table table--light style--two ul-table">
                            <thead>
                                <tr>
                                    <th style="width:60px">Gen</th>
                                    <th>Title</th>
                                    <th class="text-center" style="width:120px">Percentage</th>
                                    <th class="text-center" style="width:90px">Status</th>
                                    <th class="text-end" style="width:130px">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($generations as $gen)
                                <tr class="{{ !$gen->status ? 'ul-row-inactive' : '' }}">
                                    <td>
                                        <div class="ul-gen-badge" style="--gen-color:{{ ulGenColor($gen->number) }};">
                                            {{ $gen->number }}
                                        </div>
                                    </td>
                                    <td>
                                        <span class="ul-gen-title">{{ $gen->title }}</span>
                                        @if($gen->description)
                                            <br><small class="text-muted">{{ Str::limit($gen->description, 55) }}</small>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <span class="ul-pct-chip">{{ number_format((float)$gen->percentage, 4) }}%</span>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge {{ $gen->status ? 'badge--success' : 'badge--warning' }}">
                                            {{ $gen->status ? 'Active' : 'Inactive' }}
                                        </span>
                                    </td>
                                    <td class="text-end">
                                        <div class="ul-action-group">
                                            <button type="button" class="ul-btn ul-btn-edit"
                                                data-id="{{ $gen->id }}"
                                                data-number="{{ $gen->number }}"
                                                data-title="{{ $gen->title }}"
                                                data-percentage="{{ $gen->percentage }}"
                                                data-description="{{ $gen->description }}"
                                                data-status="{{ $gen->status ? 1 : 0 }}"
                                                title="Edit">
                                                <i class="las la-pen"></i>
                                            </button>

                                            <form action="{{ route('admin.unilevel.generations.toggle', $gen->id) }}" method="POST" class="d-inline">
                                                @csrf
                                                <button type="submit" class="ul-btn {{ $gen->status ? 'ul-btn-warn' : 'ul-btn-success' }}"
                                                    title="{{ $gen->status ? 'Disable' : 'Enable' }}">
                                                    <i class="las la-{{ $gen->status ? 'eye-slash' : 'eye' }}"></i>
                                                </button>
                                            </form>

                                            <form action="{{ route('admin.unilevel.generations.destroy', $gen->id) }}" method="POST" class="d-inline"
                                                onsubmit="return confirm('Delete Generation {{ $gen->number }}? It will be removed from all project allocations.')">
                                                @csrf @method('DELETE')
                                                <button type="submit" class="ul-btn ul-btn-danger" title="Delete">
                                                    <i class="las la-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- ── Right: Visual gen ladder ──────────────────────────── --}}
    <div class="col-lg-4">
        <div class="card b-radius--10 overflow-hidden">
            <div class="card-header">
                <h6 class="card-title mb-0">Distribution Preview</h6>
                <small class="text-muted">PRB share per generation tier</small>
            </div>
            <div class="card-body p-3">
                @if($generations->isEmpty())
                    <p class="text-muted text-center py-4" style="font-size:.82rem;">No generations configured yet.</p>
                @else
                    <div class="ul-ladder">
                        @foreach($generations as $gen)
                        <div class="ul-ladder-row {{ !$gen->status ? 'opacity-50' : '' }}">
                            <div class="ul-ladder-node" style="--gen-color:{{ ulGenColor($gen->number) }};">
                                <span>{{ $gen->number }}</span>
                            </div>
                            <div class="ul-ladder-bar-wrap">
                                <div class="ul-ladder-label">{{ $gen->title }}</div>
                                <div class="ul-ladder-track">
                                    @php $pct = min(100, (float)$gen->percentage); @endphp
                                    <div class="ul-ladder-fill" style="width:{{ max(2, $pct) }}%;background:{{ ulGenColor($gen->number) }};"></div>
                                </div>
                            </div>
                            <div class="ul-ladder-pct" style="color:{{ ulGenColor($gen->number) }};">
                                {{ number_format((float)$gen->percentage, 2) }}%
                            </div>
                        </div>
                        @endforeach
                    </div>
                @endif

                @if(count($available) > 0)
                <div class="ul-slots-remaining">
                    <i class="las la-info-circle"></i>
                    {{ count($available) }} slot{{ count($available) !== 1 ? 's' : '' }} remaining
                    ({{ implode(', ', array_slice($available, 0, 5)) }}{{ count($available) > 5 ? '…' : '' }})
                </div>
                @endif
            </div>
        </div>
    </div>

</div>

{{-- ════════════════════════════════════════════════════════════
     CREATE / EDIT DRAWER
     ════════════════════════════════════════════════════════════ --}}
<div class="ul-drawer-overlay" id="ulDrawerOverlay">
    <div class="ul-drawer" id="ulDrawer" role="dialog" aria-modal="true" aria-labelledby="ulDrawerTitle">
        <div class="ul-drawer-head">
            <div>
                <h6 class="ul-drawer-title" id="ulDrawerTitle">Add Generation</h6>
                <p class="ul-drawer-sub" id="ulDrawerSub">Configure a new unilevel generation tier</p>
            </div>
            <button type="button" class="ul-drawer-close" id="ulDrawerClose" aria-label="Close">
                <i class="las la-times"></i>
            </button>
        </div>

        <form id="ulGenForm" method="POST" action="{{ route('admin.unilevel.generations.store') }}"
              class="ul-drawer-body">
            @csrf
            <input type="hidden" name="_method" id="ulFormMethod" value="POST">

            <div class="ul-form-field">
                <label class="ul-form-label">Generation Number <span class="text-danger">*</span></label>
                <select name="number" id="ulFNumber" class="form-control" required>
                    <option value="">Select a number…</option>
                    @foreach($available as $n)
                        <option value="{{ $n }}">Generation {{ $n }}</option>
                    @endforeach
                    @foreach($generations as $gen)
                        <option value="{{ $gen->number }}" data-existing="1">Generation {{ $gen->number }} (edit only)</option>
                    @endforeach
                </select>
                <small class="ul-form-hint">Numbers 1–15; each number can only exist once</small>
            </div>

            <div class="ul-form-field">
                <label class="ul-form-label">Title <span class="text-danger">*</span></label>
                <input type="text" name="title" id="ulFTitle" class="form-control"
                       placeholder="e.g. Direct Upline" maxlength="100" required>
            </div>

            <div class="ul-form-field">
                <label class="ul-form-label">Percentage of PRB <span class="text-danger">*</span></label>
                <div class="input-group">
                    <input type="number" name="percentage" id="ulFPercentage" class="form-control"
                           min="0" max="100" step="0.0001" placeholder="0.0000" required>
                    <span class="input-group-text">%</span>
                </div>
                <small class="ul-form-hint">Portion of the product's PRB value credited to this generation</small>
            </div>

            <div class="ul-form-field">
                <label class="ul-form-label">Description</label>
                <textarea name="description" id="ulFDescription" class="form-control" rows="3"
                          maxlength="500" placeholder="Optional note about this generation tier…"></textarea>
            </div>

            <div class="ul-form-field">
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" name="status" id="ulFStatus" value="1" checked>
                    <label class="form-check-label" for="ulFStatus">Active</label>
                </div>
                <small class="ul-form-hint">Inactive generations are skipped during bonus distribution</small>
            </div>

            <div class="ul-drawer-footer">
                <button type="button" class="btn btn--secondary" id="ulDrawerCancel">Cancel</button>
                <button type="submit" class="btn btn--primary" id="ulSubmitBtn">
                    <i class="las la-save me-1"></i>
                    <span id="ulSubmitLabel">Create Generation</span>
                </button>
            </div>
        </form>
    </div>
</div>

@endsection

@push('script')
<script>
(function () {
    'use strict';

    var overlay    = document.getElementById('ulDrawerOverlay');
    var drawer     = document.getElementById('ulDrawer');
    var form       = document.getElementById('ulGenForm');
    var titleEl    = document.getElementById('ulDrawerTitle');
    var subEl      = document.getElementById('ulDrawerSub');
    var submitLbl  = document.getElementById('ulSubmitLabel');
    var methodInp  = document.getElementById('ulFormMethod');
    var createUrl  = '{{ route("admin.unilevel.generations.store") }}';

    function openDrawer() {
        overlay.classList.add('active');
        document.body.style.overflow = 'hidden';
    }

    function closeDrawer() {
        overlay.classList.remove('active');
        document.body.style.overflow = '';
    }

    // Open in CREATE mode
    document.querySelectorAll('.ul-open-create').forEach(function (btn) {
        btn.addEventListener('click', function () {
            titleEl.textContent   = 'Add Generation';
            subEl.textContent     = 'Configure a new unilevel generation tier';
            submitLbl.textContent = 'Create Generation';
            form.action           = createUrl;
            methodInp.value       = 'POST';
            form.reset();
            document.getElementById('ulFStatus').checked = true;

            // Disable "edit only" options
            document.querySelectorAll('#ulFNumber option[data-existing]').forEach(function (o) {
                o.disabled = true;
            });
            document.querySelectorAll('#ulFNumber option:not([data-existing])').forEach(function (o) {
                o.disabled = false;
            });

            openDrawer();
        });
    });

    // Open in EDIT mode
    document.querySelectorAll('.ul-btn-edit').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var d = this.dataset;

            titleEl.textContent   = 'Edit Generation ' + d.number;
            subEl.textContent     = 'Update the details for this generation tier';
            submitLbl.textContent = 'Save Changes';

            var updateUrl = createUrl + '/' + d.id;
            form.action   = updateUrl;
            methodInp.value = 'POST';

            document.getElementById('ulFNumber').value       = d.number;
            document.getElementById('ulFTitle').value        = d.title;
            document.getElementById('ulFPercentage').value   = d.percentage;
            document.getElementById('ulFDescription').value  = d.description || '';
            document.getElementById('ulFStatus').checked     = d.status === '1';

            // In edit mode enable only the current number option
            document.querySelectorAll('#ulFNumber option').forEach(function (o) {
                o.disabled = (o.value !== d.number);
            });

            openDrawer();
        });
    });

    document.getElementById('ulDrawerClose').addEventListener('click', closeDrawer);
    document.getElementById('ulDrawerCancel').addEventListener('click', closeDrawer);

    overlay.addEventListener('click', function (e) {
        if (e.target === overlay) closeDrawer();
    });

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') closeDrawer();
    });
})();
</script>
@endpush
