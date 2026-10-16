@extends('admin.layouts.app')

@push('style')
<style>
/* ── Project cards ─────────────────────────────────────── */
.proj-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
    gap: 22px;
    padding: 24px;
}

.proj-card {
    background: #fff;
    border-radius: 16px;
    border: 1px solid #E5E9EF;
    overflow: hidden;
    box-shadow: 0 2px 12px rgba(0,0,0,.06);
    transition: transform .2s, box-shadow .2s;
    display: flex;
    flex-direction: column;
}
.proj-card:hover { transform: translateY(-3px); box-shadow: 0 8px 28px rgba(0,0,0,.10); }

/* coloured top band */
.proj-card-band {
    height: 6px;
}

.proj-card-head {
    display: flex;
    align-items: flex-start;
    gap: 14px;
    padding: 20px 20px 14px;
}
.proj-card-icon {
    width: 48px; height: 48px; flex-shrink: 0;
    border-radius: 12px;
    display: flex; align-items: center; justify-content: center;
    font-size: 1.35rem; color: #fff;
}
.proj-card-title { font-size: .97rem; font-weight: 800; color: #111827; margin: 0 0 3px; }
.proj-card-slug  { font-size: .72rem; color: #9CA3AF; margin: 0; font-family: monospace; }

/* status / default badges */
.proj-badge-row { display: flex; gap: 6px; flex-wrap: wrap; margin-top: 6px; }
.proj-badge {
    font-size: .65rem; font-weight: 700; text-transform: uppercase; letter-spacing: .5px;
    padding: 2px 8px; border-radius: 20px;
}
.proj-badge-active   { background: #D1FAE5; color: #065F46; }
.proj-badge-inactive { background: #FEE2E2; color: #991B1B; }
.proj-badge-default  { background: #DBEAFE; color: #1E40AF; }

/* stats grid */
.proj-stats {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    border-top: 1px solid #F3F4F6;
    border-bottom: 1px solid #F3F4F6;
}
.proj-stat {
    padding: 12px 8px;
    text-align: center;
    border-right: 1px solid #F3F4F6;
}
.proj-stat:last-child { border-right: none; }
.proj-stat-val  { font-size: 1rem; font-weight: 800; color: #111827; margin: 0; }
.proj-stat-lbl  { font-size: .67rem; color: #9CA3AF; text-transform: uppercase; letter-spacing: .04em; margin: 2px 0 0; }

/* commission/detail rows */
.proj-details { padding: 14px 20px; display: flex; flex-direction: column; gap: 7px; }
.proj-detail-row {
    display: flex; align-items: center; justify-content: space-between; gap: 8px;
    font-size: .8rem;
}
.proj-detail-lbl { color: #6B7280; display: flex; align-items: center; gap: 5px; }
.proj-detail-lbl i { font-size: .85rem; width: 16px; text-align: center; }
.proj-detail-val { font-weight: 700; color: #111827; }
.proj-detail-val.highlight { color: #059669; }

/* actions footer */
.proj-actions {
    display: flex; gap: 8px; padding: 14px 20px;
    border-top: 1px solid #F3F4F6;
    margin-top: auto;
}
.proj-btn {
    flex: 1; padding: 8px 10px; border-radius: 8px; font-size: .78rem; font-weight: 700;
    border: none; cursor: pointer; display: flex; align-items: center; justify-content: center;
    gap: 5px; transition: opacity .15s, transform .1s; text-decoration: none;
}
.proj-btn:active { transform: scale(.97); }
.proj-btn-edit   { background: #EFF6FF; color: #2563EB; border: 1px solid #DBEAFE; }
.proj-btn-edit:hover { background: #DBEAFE; color: #1D4ED8; }
.proj-btn-toggle-on  { background: #FEF2F2; color: #DC2626; border: 1px solid #FEE2E2; }
.proj-btn-toggle-on:hover  { background: #FEE2E2; color: #B91C1C; }
.proj-btn-toggle-off { background: #F0FDF4; color: #16A34A; border: 1px solid #BBF7D0; }
.proj-btn-toggle-off:hover { background: #DCFCE7; color: #15803D; }
.proj-btn-delete { background: #FFF7ED; color: #EA580C; border: 1px solid #FED7AA; }
.proj-btn-delete:hover { background: #FFEDD5; color: #C2410C; }
.proj-btn-delete:disabled { opacity: .4; cursor: not-allowed; }

/* subscriber count chip */
.proj-sub-chip {
    display: inline-flex; align-items: center; gap: 4px;
    background: #F0FDF4; color: #15803D; border: 1px solid #BBF7D0;
    border-radius: 20px; padding: 2px 10px; font-size: .72rem; font-weight: 700;
}

/* empty state */
.proj-empty {
    text-align: center; padding: 72px 24px;
}
.proj-empty-icon { font-size: 56px; color: #D1D5DB; margin-bottom: 16px; }
.proj-empty-title { font-size: 1rem; font-weight: 700; color: #374151; margin: 0 0 6px; }
.proj-empty-sub   { font-size: .85rem; color: #9CA3AF; margin: 0 0 22px; }

/* modal form */
.proj-form-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 14px;
}
.proj-form-grid .full { grid-column: 1 / -1; }
.proj-form-section-head {
    grid-column: 1 / -1;
    font-size: .7rem; font-weight: 700; letter-spacing: .08em;
    text-transform: uppercase; color: #6B7280;
    border-bottom: 1px solid #F3F4F6; padding-bottom: 6px; margin-top: 4px;
}

@media (max-width: 600px) {
    .proj-grid { grid-template-columns: 1fr; padding: 14px; }
    .proj-form-grid { grid-template-columns: 1fr; }
    .proj-form-grid .full { grid-column: 1; }
}

/* ── Scrollable modal: the <form> sits between .modal-content
      and .modal-body which breaks Bootstrap's flex chain.
      These rules restore it. ── */
#projectModal .modal-dialog {
    max-height: calc(100vh - 3.5rem);
    height: calc(100vh - 3.5rem);
}
#projectModal .modal-content {
    max-height: 100%;
    height: 100%;
    display: flex;
    flex-direction: column;
    overflow: hidden;
}
#projectModal .modal-header,
#projectModal .modal-footer {
    flex-shrink: 0;
}
#projectModal #projectForm {
    display: flex;
    flex-direction: column;
    flex: 1 1 auto;
    overflow: hidden;
    min-height: 0;
}
#projectModal .modal-body {
    flex: 1 1 auto;
    overflow-y: auto;
    min-height: 0;
}
</style>
@endpush

@section('panel')

<div class="card b-radius--10 overflow-hidden">
    {{-- ── Header ── --}}
    <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-2">
        <div>
            <h5 class="card-title mb-0">Projects</h5>
            <small class="text-muted">Define the plans users subscribe to on registration</small>
        </div>
        <button class="btn btn--primary btn-sm" data-bs-toggle="modal" data-bs-target="#projectModal" id="createProjectBtn">
            <i class="las la-plus me-1"></i> New Project
        </button>
    </div>

    {{-- ── Stats strip ── --}}
    <div class="card-body border-bottom py-3">
        <div class="row g-3 text-center">
            <div class="col-6 col-md-3">
                <p class="mb-0 fw-bold fs-5">{{ $projects->count() }}</p>
                <small class="text-muted">Total Projects</small>
            </div>
            <div class="col-6 col-md-3">
                <p class="mb-0 fw-bold fs-5 text-success">{{ $projects->where('status', true)->count() }}</p>
                <small class="text-muted">Active</small>
            </div>
            <div class="col-6 col-md-3">
                <p class="mb-0 fw-bold fs-5">{{ $projects->sum('users_count') }}</p>
                <small class="text-muted">Total Subscribers</small>
            </div>
            <div class="col-6 col-md-3">
                <p class="mb-0 fw-bold fs-5 text-primary">{{ showAmount($projects->sum('amount')) }}</p>
                <small class="text-muted">Avg Plan Value</small>
            </div>
        </div>
    </div>

    {{-- ── Cards ── --}}
    @if($projects->isEmpty())
    <div class="proj-empty">
        <div class="proj-empty-icon"><i class="las la-briefcase"></i></div>
        <p class="proj-empty-title">No Projects Yet</p>
        <p class="proj-empty-sub">Create your first project to allow users to subscribe on registration.</p>
        <button class="btn btn--primary" data-bs-toggle="modal" data-bs-target="#projectModal">
            <i class="las la-plus me-1"></i> Create Project
        </button>
    </div>
    @else
    <div class="proj-grid">
        @foreach($projects as $proj)
        @php $canDelete = $proj->users_count === 0; @endphp
        <div class="proj-card">
            <div class="proj-card-band" style="background:{{ $proj->color }};"></div>

            {{-- Head --}}
            <div class="proj-card-head">
                <div class="proj-card-icon" style="background:{{ $proj->color }};">
                    <i class="{{ $proj->icon }}"></i>
                </div>
                <div style="flex:1;min-width:0;">
                    <p class="proj-card-title">{{ $proj->title }}</p>
                    <p class="proj-card-slug">{{ $proj->slug }}</p>
                    <div class="proj-badge-row">
                        <span class="proj-badge {{ $proj->status ? 'proj-badge-active' : 'proj-badge-inactive' }}">
                            {{ $proj->status ? 'Active' : 'Inactive' }}
                        </span>
                        @if($proj->is_default)
                            <span class="proj-badge proj-badge-default">Default</span>
                        @endif
                        <span class="proj-sub-chip">
                            <i class="las la-users"></i> {{ number_format($proj->users_count) }} subscribers
                        </span>
                    </div>
                </div>
            </div>

            {{-- Key stats --}}
            <div class="proj-stats">
                <div class="proj-stat">
                    <p class="proj-stat-val">{{ showAmount($proj->amount) }}</p>
                    <p class="proj-stat-lbl">Price</p>
                </div>
                <div class="proj-stat">
                    <p class="proj-stat-val">{{ $proj->pv }}</p>
                    <p class="proj-stat-lbl">PV</p>
                </div>
                <div class="proj-stat">
                    <p class="proj-stat-val">{{ showAmount($proj->cash_back) }}</p>
                    <p class="proj-stat-lbl">Cashback</p>
                </div>
            </div>

            {{-- Commission details --}}
            <div class="proj-details">
                <div class="proj-detail-row">
                    <span class="proj-detail-lbl"><i class="las la-hand-holding-usd"></i> Direct Commission</span>
                    <span class="proj-detail-val highlight">{{ showAmount($proj->direct_commission) }}</span>
                </div>
                <div class="proj-detail-row">
                    <span class="proj-detail-lbl"><i class="las la-network-wired"></i> Indirect Commission</span>
                    <span class="proj-detail-val highlight">{{ showAmount($proj->indirect_commission) }}</span>
                </div>
                <div class="proj-detail-row">
                    <span class="proj-detail-lbl"><i class="las la-balance-scale"></i> Matching Cap/Day</span>
                    <span class="proj-detail-val">{{ $proj->pairing_per_day > 0 ? showAmount($proj->pairing_per_day) : 'Unlimited' }}</span>
                </div>
                <div class="proj-detail-row">
                    <span class="proj-detail-lbl"><i class="las la-arrow-up"></i> Upgrade Bonus</span>
                    <span class="proj-detail-val">{{ $proj->upgrade_bonus }}%</span>
                </div>
                <div class="proj-detail-row">
                    <span class="proj-detail-lbl"><i class="las la-sitemap"></i> Unilevel Bonus</span>
                    <span class="proj-detail-val">{{ showAmount($proj->unilevel_bonus) }}</span>
                </div>
                @if($proj->monthly_maintenance > 0)
                <div class="proj-detail-row">
                    <span class="proj-detail-lbl"><i class="las la-calendar-check"></i> Monthly Fee</span>
                    <span class="proj-detail-val text-danger">{{ showAmount($proj->monthly_maintenance) }}</span>
                </div>
                @endif
                @if($proj->description)
                <p style="font-size:.78rem;color:#6B7280;margin:4px 0 0;border-top:1px solid #F9FAFB;padding-top:8px;">
                    {{ $proj->description }}
                </p>
                @endif
            </div>

            {{-- Actions --}}
            <div class="proj-actions">
                <button class="proj-btn proj-btn-edit editProjectBtn"
                    data-id="{{ $proj->id }}"
                    data-title="{{ $proj->title }}"
                    data-description="{{ $proj->description }}"
                    data-icon="{{ $proj->icon }}"
                    data-color="{{ $proj->color }}"
                    data-amount="{{ $proj->amount }}"
                    data-direct_commission="{{ $proj->direct_commission }}"
                    data-indirect_commission="{{ $proj->indirect_commission }}"
                    data-pv="{{ $proj->pv }}"
                    data-pairing_per_day="{{ $proj->pairing_per_day }}"
                    data-cash_back="{{ $proj->cash_back }}"
                    data-monthly_maintenance="{{ $proj->monthly_maintenance }}"
                    data-upgrade_bonus="{{ $proj->upgrade_bonus }}"
                    data-unilevel_bonus="{{ $proj->unilevel_bonus }}"
                    data-max_pairing_slots="{{ $proj->max_pairing_slots }}"
                    data-sort_order="{{ $proj->sort_order }}"
                    data-upgrade_allowed="{{ $proj->upgrade_allowed ? 1 : 0 }}"
                    data-is_default="{{ $proj->is_default ? 1 : 0 }}"
                    data-status="{{ $proj->status ? 1 : 0 }}">
                    <i class="las la-pen"></i> Edit
                </button>

                <form action="{{ route('admin.projects.toggle', $proj->id) }}" method="POST" class="flex-1 d-flex">
                    @csrf
                    <button type="submit" class="proj-btn {{ $proj->status ? 'proj-btn-toggle-on' : 'proj-btn-toggle-off' }}">
                        <i class="las la-{{ $proj->status ? 'eye-slash' : 'eye' }}"></i>
                        {{ $proj->status ? 'Disable' : 'Enable' }}
                    </button>
                </form>

                <form action="{{ route('admin.projects.destroy', $proj->id) }}" method="POST" class="d-flex"
                      onsubmit="return {{ $canDelete ? 'confirm(\'Delete this project?\')' : 'false' }}">
                    @csrf @method('DELETE')
                    <button type="submit" class="proj-btn proj-btn-delete"
                            {{ !$canDelete ? 'disabled title="Cannot delete — has subscribers"' : '' }}>
                        <i class="las la-trash"></i> Delete
                    </button>
                </form>
            </div>
        </div>
        @endforeach
    </div>
    @endif
</div>

{{-- ════════════════════════════════════════════════════════
     CREATE / EDIT MODAL
     ════════════════════════════════════════════════════════ --}}
<div class="modal fade" id="projectModal" tabindex="-1" data-create-url="{{ route('admin.projects.store') }}">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="projectModalTitle">New Project</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="projectForm" method="POST" action="{{ route('admin.projects.store') }}">
                @csrf
                <input type="hidden" name="_method" id="formMethod" value="POST">
                <div class="modal-body">
                    <div class="proj-form-grid">

                        {{-- ── Identity ── --}}
                        <div class="proj-form-section-head">Identity</div>

                        <div class="full">
                            <label class="form-label fw-semibold">Title <span class="text-danger">*</span></label>
                            <input type="text" name="title" id="f_title" class="form-control" required maxlength="150" placeholder="e.g. Starter Pack">
                        </div>

                        <div class="full">
                            <label class="form-label fw-semibold">Description</label>
                            <textarea name="description" id="f_description" class="form-control" rows="2" maxlength="1000"
                                      placeholder="Short description visible to users during registration"></textarea>
                        </div>

                        <div>
                            <label class="form-label fw-semibold">Icon Class</label>
                            <input type="text" name="icon" id="f_icon" class="form-control" value="las la-briefcase"
                                   placeholder="las la-briefcase">
                            <small class="text-muted">Any LineAwesome or FontAwesome class</small>
                        </div>

                        <div>
                            <label class="form-label fw-semibold">Theme Colour</label>
                            <div class="input-group">
                                <span class="input-group-text p-1">
                                    <input type="color" id="colorPicker" value="#059669"
                                           style="width:32px;height:32px;border:none;cursor:pointer;background:none;">
                                </span>
                                <input type="text" name="color" id="f_color" class="form-control" value="#059669"
                                       pattern="^#([0-9A-Fa-f]{3}|[0-9A-Fa-f]{6})$">
                            </div>
                        </div>

                        {{-- ── Pricing ── --}}
                        <div class="proj-form-section-head">Pricing &amp; Value</div>

                        <div>
                            <label class="form-label fw-semibold">Activation Amount <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text">{{ gs('cur_sym') }}</span>
                                <input type="number" name="amount" id="f_amount" class="form-control" min="0" step="0.01" required>
                            </div>
                        </div>

                        <div>
                            <label class="form-label fw-semibold">Point Value (PV) <span class="text-danger">*</span></label>
                            <input type="number" name="pv" id="f_pv" class="form-control" min="0" step="0.01" required>
                        </div>

                        <div>
                            <label class="form-label fw-semibold">Cash Back Amount <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text">{{ gs('cur_sym') }}</span>
                                <input type="number" name="cash_back" id="f_cash_back" class="form-control" min="0" step="0.01" required>
                            </div>
                        </div>

                        <div>
                            <label class="form-label fw-semibold">Monthly Maintenance <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text">{{ gs('cur_sym') }}</span>
                                <input type="number" name="monthly_maintenance" id="f_monthly_maintenance" class="form-control" min="0" step="0.01" required value="0">
                            </div>
                            <small class="text-muted">0 = none</small>
                        </div>

                        {{-- ── Commissions ── --}}
                        <div class="proj-form-section-head">Binary Commissions</div>

                        <div>
                            <label class="form-label fw-semibold">Direct Commission Amount <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text">{{ gs('cur_sym') }}</span>
                                <input type="number" name="direct_commission" id="f_direct_commission" class="form-control" min="0" step="0.01" required>
                            </div>
                        </div>

                        <div>
                            <label class="form-label fw-semibold">Indirect Commission Amount <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text">{{ gs('cur_sym') }}</span>
                                <input type="number" name="indirect_commission" id="f_indirect_commission" class="form-control" min="0" step="0.01" required>
                            </div>
                        </div>

                        <div>
                            <label class="form-label fw-semibold">Upgrade Bonus % <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="number" name="upgrade_bonus" id="f_upgrade_bonus" class="form-control" min="0" max="100" step="0.01" required value="0">
                                <span class="input-group-text">%</span>
                            </div>
                        </div>

                        <div>
                            <label class="form-label fw-semibold">Unilevel Bonus Amount <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text">{{ gs('cur_sym') }}</span>
                                <input type="number" name="unilevel_bonus" id="f_unilevel_bonus" class="form-control" min="0" step="0.01" required value="0">
                            </div>
                        </div>

                        {{-- ── Pairing ── --}}
                        <div class="proj-form-section-head">Pairing (Binary Tree)</div>

                        <div>
                            <label class="form-label fw-semibold">Max Matching Bonus/Day <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text">{{ gs('cur_sym') }}</span>
                                <input type="number" name="pairing_per_day" id="f_pairing_per_day" class="form-control" min="0" step="0.01" required value="0">
                            </div>
                            <small class="text-muted">0 = unlimited</small>
                        </div>

                        <div>
                            <label class="form-label fw-semibold">Max Pairing Slots</label>
                            <input type="number" name="max_pairing_slots" id="f_max_pairing_slots" class="form-control" min="0" required value="0">
                            <small class="text-muted">0 = unlimited</small>
                        </div>

                        {{-- ── Settings ── --}}
                        <div class="proj-form-section-head">Settings</div>

                        <div>
                            <label class="form-label fw-semibold">Sort Order</label>
                            <input type="number" name="sort_order" id="f_sort_order" class="form-control" min="0" required value="0">
                        </div>

                        <div class="d-flex flex-column gap-2 pt-2">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="status" id="f_status" value="1" checked>
                                <label class="form-check-label" for="f_status">Active</label>
                            </div>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="upgrade_allowed" id="f_upgrade_allowed" value="1" checked>
                                <label class="form-check-label" for="f_upgrade_allowed">Allow upgrades to this project</label>
                            </div>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="is_default" id="f_is_default" value="1">
                                <label class="form-check-label" for="f_is_default">Default (pre-selected on registration)</label>
                            </div>
                        </div>

                    </div>{{-- /proj-form-grid --}}
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn--secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn--primary" id="projectSubmitBtn">
                        <i class="las la-save me-1"></i> <span id="projectSubmitLabel">Create Project</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('script')
<script>
(function () {
    'use strict';

    // colour picker sync
    var colorPicker = document.getElementById('colorPicker');
    var colorInput  = document.getElementById('f_color');
    colorPicker.addEventListener('input', function () { colorInput.value = this.value; });
    colorInput.addEventListener('input', function () {
        if (/^#([0-9A-Fa-f]{3}|[0-9A-Fa-f]{6})$/.test(this.value)) colorPicker.value = this.value;
    });

    var modal     = document.getElementById('projectModal');
    var form      = document.getElementById('projectForm');
    var title     = document.getElementById('projectModalTitle');
    var label     = document.getElementById('projectSubmitLabel');
    var createUrl = modal.getAttribute('data-create-url');

    // ── Open modal in CREATE mode ──────────────────────────────
    document.getElementById('createProjectBtn').addEventListener('click', function () {
        form.action = createUrl;
        document.getElementById('formMethod').value = 'POST';
        title.textContent = 'New Project';
        label.textContent = 'Create Project';
        form.reset();
        // reset defaults
        document.getElementById('f_icon').value   = 'las la-briefcase';
        document.getElementById('f_color').value  = '#059669';
        colorPicker.value = '#059669';
        document.getElementById('f_status').checked           = true;
        document.getElementById('f_upgrade_allowed').checked  = true;
        document.getElementById('f_is_default').checked       = false;
    });

    // ── Open modal in EDIT mode ────────────────────────────────
    document.querySelectorAll('.editProjectBtn').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var d = this.dataset;
            form.action = createUrl + '/' + d.id;   // POST /projects/{id}
            document.getElementById('formMethod').value = 'POST';
            title.textContent = 'Edit Project';
            label.textContent = 'Save Changes';

            // populate fields
            [
                'title','description','icon','color','amount','pv','cash_back',
                'monthly_maintenance','direct_commission','indirect_commission',
                'upgrade_bonus','unilevel_bonus','pairing_per_day','max_pairing_slots','sort_order'
            ].forEach(function (key) {
                var el = document.getElementById('f_' + key);
                if (el) el.value = d[key] ?? '';
            });

            colorPicker.value = d.color || '#059669';

            document.getElementById('f_status').checked           = d.status === '1';
            document.getElementById('f_upgrade_allowed').checked  = d.upgrade_allowed === '1';
            document.getElementById('f_is_default').checked       = d.is_default === '1';

            var bsModal = bootstrap.Modal.getOrCreateInstance(modal);
            bsModal.show();
        });
    });
})();
</script>
@endpush

@endsection
