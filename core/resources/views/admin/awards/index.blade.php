@extends('admin.layouts.app')

@section('panel')

@push('style')
<style>
/* ═══════════════════════════════════════════════════════
   AWARDS INDEX — Premium Dashboard Design
   ═══════════════════════════════════════════════════════ */

.aw-hero {
    background: linear-gradient(135deg, #0D5C2E 0%, #16A34A 60%, #22c55e 100%);
    border-radius: 20px;
    padding: 32px 36px;
    margin-bottom: 28px;
    position: relative;
    overflow: hidden;
    color: #fff;
}
.aw-hero::before {
    content: '';
    position: absolute;
    top: -50px; right: -50px;
    width: 220px; height: 220px;
    border-radius: 50%;
    background: rgba(255,255,255,.06);
    pointer-events: none;
}
.aw-hero::after {
    content: '';
    position: absolute;
    bottom: -70px; right: 80px;
    width: 300px; height: 300px;
    border-radius: 50%;
    background: rgba(255,255,255,.04);
    pointer-events: none;
}
.aw-hero-bg-icon {
    position: absolute;
    right: 36px;
    top: 50%;
    transform: translateY(-50%);
    font-size: 5rem;
    opacity: .08;
    pointer-events: none;
}
.aw-hero-title {
    font-size: 1.65rem;
    font-weight: 800;
    letter-spacing: -.02em;
    margin-bottom: 4px;
}
.aw-hero-sub { font-size: .9rem; opacity: .8; margin-bottom: 24px; }
.aw-hero-stats {
    display: flex;
    gap: 12px;
    flex-wrap: wrap;
}
.aw-stat-pill {
    background: rgba(255,255,255,.13);
    border: 1px solid rgba(255,255,255,.22);
    border-radius: 50px;
    padding: 7px 18px;
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: .82rem;
    backdrop-filter: blur(4px);
}
.aw-stat-pill b { font-size: .98rem; font-weight: 800; }

/* Controls */
.aw-controls {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 22px;
    gap: 10px;
    flex-wrap: wrap;
}
.aw-total-label {
    font-size: .8rem;
    color: #9ca3af;
    font-weight: 500;
}

/* Award Card */
.award-card {
    background: #fff;
    border-radius: 20px;
    overflow: hidden;
    border: 1px solid #f0f0f0;
    box-shadow: 0 2px 12px rgba(0,0,0,.06), 0 1px 3px rgba(0,0,0,.04);
    transition: transform .3s cubic-bezier(.34,1.56,.64,1), box-shadow .3s ease;
    position: relative;
    height: 100%;
}
.award-card:hover {
    transform: translateY(-7px) scale(1.01);
    box-shadow: 0 18px 44px rgba(13,92,46,.16), 0 4px 12px rgba(0,0,0,.08);
}

/* Card head */
.award-card-head {
    background: linear-gradient(160deg, #f0fdf4 0%, #dcfce7 100%);
    padding: 24px 20px 18px;
    text-align: center;
    position: relative;
    overflow: hidden;
}
.award-card-head::before {
    content: '';
    position: absolute;
    top: -35px; left: 50%;
    transform: translateX(-50%);
    width: 130px; height: 130px;
    background: radial-gradient(circle, rgba(22,163,74,.18) 0%, transparent 70%);
    pointer-events: none;
}

/* Status pulse dot */
.aw-status-dot {
    position: absolute;
    top: 13px; right: 13px;
    width: 10px; height: 10px;
    border-radius: 50%;
    background: #d1d5db;
}
.aw-status-dot.active {
    background: #22c55e;
    animation: aw-pulse-dot 2s ease-out infinite;
}
@keyframes aw-pulse-dot {
    0%   { box-shadow: 0 0 0 0 rgba(34,197,94,.7); }
    70%  { box-shadow: 0 0 0 9px rgba(34,197,94,0); }
    100% { box-shadow: 0 0 0 0 rgba(34,197,94,0); }
}

.aw-order-badge {
    position: absolute;
    top: 11px; left: 11px;
    background: rgba(13,92,46,.12);
    color: #0D5C2E;
    font-size: .64rem;
    font-weight: 800;
    padding: 2px 9px;
    border-radius: 20px;
    letter-spacing: .07em;
    text-transform: uppercase;
}

/* Avatar ring */
.award-avatar-ring {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 88px; height: 88px;
    border-radius: 50%;
    background: linear-gradient(135deg, #16a34a, #0D5C2E);
    padding: 3px;
    margin-bottom: 12px;
    box-shadow: 0 4px 18px rgba(13,92,46,.28);
}
.award-avatar-ring img,
.aw-avatar-inner {
    width: 82px; height: 82px;
    border-radius: 50%;
    object-fit: cover;
    background: linear-gradient(135deg, #f0fdf4, #dcfce7);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 2.2rem;
    color: #16a34a;
}
.award-card-name {
    font-size: 1.02rem;
    font-weight: 800;
    color: #0D5C2E;
    letter-spacing: -.01em;
    margin-bottom: 4px;
    line-height: 1.2;
}
.award-card-desc {
    font-size: .75rem;
    color: #6b7280;
    line-height: 1.5;
    min-height: 34px;
}

/* PV block */
.award-pv-block {
    padding: 14px 16px;
    border-top: 1px solid #f0fdf4;
    background: #fafffe;
}
.award-pv-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 8px;
}
.award-pv-row:last-child { margin-bottom: 0; }
.pv-label {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: .71rem;
    font-weight: 700;
    color: #6b7280;
    text-transform: uppercase;
    letter-spacing: .06em;
}
.pv-dot {
    width: 8px; height: 8px;
    border-radius: 50%;
    flex-shrink: 0;
}
.dot-total { background: #6366f1; }
.dot-left  { background: #16a34a; }
.dot-right { background: #e11d48; }
.pv-value {
    font-size: .84rem;
    font-weight: 800;
    color: #1a1f2e;
}

/* Meta strip */
.award-meta-strip {
    padding: 10px 14px;
    display: flex;
    align-items: center;
    gap: 6px;
    flex-wrap: wrap;
    border-top: 1px solid #f3f4f6;
    min-height: 44px;
}
.aw-chip {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 4px 11px;
    border-radius: 20px;
    font-size: .71rem;
    font-weight: 700;
}
.chip-reward  { background: linear-gradient(135deg,#fef3c7,#fde68a); color: #92400e; box-shadow: 0 1px 4px rgba(251,191,36,.2); }
.chip-prereq  { background: #f3e8ff; color: #7c3aed; }
.chip-earned  { background: #e0f2fe; color: #0369a1; margin-left: auto; }

/* Card footer */
.award-card-footer {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 12px 14px;
    border-top: 1px solid #f3f4f6;
    background: #fcfcfd;
    gap: 8px;
}

/* Toggle */
.aw-toggle {
    position: relative;
    display: inline-block;
    width: 44px; height: 24px;
    flex-shrink: 0;
}
.aw-toggle input { opacity: 0; width: 0; height: 0; }
.aw-toggle-track {
    position: absolute;
    top: 0; left: 0; right: 0; bottom: 0;
    background: #d1d5db;
    border-radius: 24px;
    cursor: pointer;
    transition: background .25s;
}
.aw-toggle-track::before {
    content: '';
    position: absolute;
    width: 18px; height: 18px;
    left: 3px; top: 3px;
    background: #fff;
    border-radius: 50%;
    transition: transform .25s cubic-bezier(.34,1.56,.64,1);
    box-shadow: 0 1px 4px rgba(0,0,0,.15);
}
.aw-toggle input:checked + .aw-toggle-track { background: #16a34a; }
.aw-toggle input:checked + .aw-toggle-track::before { transform: translateX(20px); }

/* Action buttons */
.aw-btn-edit {
    display: inline-flex; align-items: center; gap: 5px;
    padding: 5px 14px; font-size: .75rem; font-weight: 700;
    background: #eff6ff; color: #2563eb;
    border-radius: 10px; border: 1px solid #dbeafe;
    text-decoration: none; transition: all .2s;
}
.aw-btn-edit:hover { background: #2563eb; color: #fff; border-color: #2563eb; }
.aw-btn-del {
    display: inline-flex; align-items: center; gap: 5px;
    padding: 5px 10px; font-size: .75rem; font-weight: 700;
    background: #fff1f2; color: #e11d48;
    border-radius: 10px; border: 1px solid #fecdd3;
    cursor: pointer; transition: all .2s;
}
.aw-btn-del:hover { background: #e11d48; color: #fff; border-color: #e11d48; }

/* Empty state */
.aw-empty {
    text-align: center;
    padding: 80px 24px;
    background: #fff;
    border-radius: 20px;
    border: 2px dashed #e5e7eb;
}
.aw-empty-icon {
    width: 84px; height: 84px;
    border-radius: 50%;
    background: linear-gradient(135deg, #dcfce7, #bbf7d0);
    display: flex; align-items: center; justify-content: center;
    margin: 0 auto 18px;
    font-size: 2.4rem; color: #16a34a;
}
.aw-empty h5 { font-size: 1.1rem; font-weight: 800; color: #374151; margin-bottom: 8px; }
.aw-empty p { font-size: .86rem; color: #9ca3af; max-width: 260px; margin: 0 auto 22px; }

/* Modal */
.aw-del-modal-head {
    background: linear-gradient(135deg, #fff1f2, #ffe4e6);
    padding: 22px 24px 18px;
    border-bottom: 1px solid #fecdd3;
}
.aw-del-modal-icon {
    width: 50px; height: 50px;
    border-radius: 50%;
    background: #fee2e2;
    display: flex; align-items: center; justify-content: center;
    font-size: 1.5rem; color: #e11d48;
    margin-bottom: 12px;
}
</style>
@endpush

<div class="aw-hero">
    <i class="las la-trophy aw-hero-bg-icon"></i>
    <div class="aw-hero-title">Award Management</div>
    <div class="aw-hero-sub">Define achievements and automatically reward your top-performing members</div>
    <div class="aw-hero-stats">
        <div class="aw-stat-pill">
            <i class="las la-layer-group"></i>
            <b>{{ $awards->count() }}</b>
            <span>Total Awards</span>
        </div>
        <div class="aw-stat-pill">
            <i class="las la-check-circle"></i>
            <b>{{ $awards->where('status', 1)->count() }}</b>
            <span>Active</span>
        </div>
        <div class="aw-stat-pill">
            <i class="las la-user-check"></i>
            <b>{{ $awards->sum('user_awards_count') }}</b>
            <span>Total Earned</span>
        </div>
    </div>
</div>

<div class="aw-controls">
    <span class="aw-total-label">{{ $awards->count() }} award(s) — ordered by rank</span>
    <a href="{{ route('admin.awards.create') }}" class="btn btn--primary">
        <i class="las la-plus me-1"></i> Add Award
    </a>
</div>

@if($awards->isEmpty())
<div class="aw-empty">
    <div class="aw-empty-icon"><i class="las la-trophy"></i></div>
    <h5>No Awards Configured</h5>
    <p>Create your first award to start recognising your top performers automatically.</p>
    <a href="{{ route('admin.awards.create') }}" class="btn btn--primary">
        <i class="las la-plus me-1"></i> Add First Award
    </a>
</div>
@else
<div class="row gy-4">
    @foreach($awards as $award)
    <div class="col-xl-3 col-lg-4 col-md-6">
        <div class="award-card">
            <div class="award-card-head">
                <span class="aw-order-badge"># {{ $award->sort_order }}</span>
                <span class="aw-status-dot {{ $award->status ? 'active' : '' }}"
                      title="{{ $award->status ? 'Active' : 'Inactive' }}"></span>

                <div class="award-avatar-ring">
                    @if($award->image_url)
                        <img src="{{ $award->image_url }}" alt="{{ $award->name }}">
                    @else
                        <div class="aw-avatar-inner"><i class="las la-trophy"></i></div>
                    @endif
                </div>

                <div class="award-card-name">{{ $award->name }}</div>
                <div class="award-card-desc">{{ Str::limit($award->description, 72) ?: 'No description provided.' }}</div>
            </div>

            <div class="award-pv-block">
                <div class="award-pv-row">
                    <span class="pv-label"><span class="pv-dot dot-total"></span> Total PV</span>
                    <span class="pv-value">{{ number_format($award->required_total_pv, 0) }}</span>
                </div>
                <div class="award-pv-row">
                    <span class="pv-label"><span class="pv-dot dot-left"></span> Left Leg</span>
                    <span class="pv-value">{{ number_format($award->required_left_pv, 0) }}</span>
                </div>
                <div class="award-pv-row">
                    <span class="pv-label"><span class="pv-dot dot-right"></span> Right Leg</span>
                    <span class="pv-value">{{ number_format($award->required_right_pv, 0) }}</span>
                </div>
            </div>

            <div class="award-meta-strip">
                <span class="aw-chip chip-reward">
                    <i class="las la-coins"></i> {{ showAmount($award->payment_amount) }}
                </span>
                @if($award->prerequisite)
                    <span class="aw-chip chip-prereq" title="Requires: {{ $award->prerequisite->name }}">
                        <i class="las la-link"></i> {{ Str::limit($award->prerequisite->name, 12) }}
                    </span>
                @endif
                <span class="aw-chip chip-earned">
                    <i class="las la-user-check"></i> {{ $award->user_awards_count }} earned
                </span>
            </div>

            <div class="award-card-footer">
                <label class="aw-toggle" title="{{ $award->status ? 'Deactivate' : 'Activate' }}">
                    <input type="checkbox"
                           class="award-toggle"
                           data-url="{{ route('admin.awards.toggle', $award->id) }}"
                           {{ $award->status ? 'checked' : '' }}>
                    <span class="aw-toggle-track"></span>
                </label>
                <div class="d-flex gap-2">
                    <a href="{{ route('admin.awards.edit', $award->id) }}" class="aw-btn-edit">
                        <i class="las la-edit"></i> Edit
                    </a>
                    <button type="button"
                            class="aw-btn-del"
                            data-bs-toggle="modal"
                            data-bs-target="#deleteModal"
                            data-name="{{ $award->name }}"
                            data-url="{{ route('admin.awards.destroy', $award->id) }}">
                        <i class="las la-trash"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endforeach
</div>
@endif

{{-- Delete Modal --}}
<div class="modal fade" id="deleteModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width:420px;">
        <div class="modal-content" style="border-radius:18px;border:none;overflow:hidden;">
            <div class="aw-del-modal-head">
                <div class="aw-del-modal-icon"><i class="las la-exclamation-triangle"></i></div>
                <h5 class="mb-1 fw-bold" style="color:#1a1f2e;">Delete Award</h5>
                <p class="mb-0" style="font-size:.8rem;color:#6b7280;">This action is permanent and cannot be undone.</p>
            </div>
            <div class="modal-body px-4 py-4">
                <p class="mb-0" style="font-size:.9rem;color:#374151;">
                    You are about to permanently delete
                    <strong id="deleteAwardName" style="color:#e11d48;"></strong>.
                    All earned records for this award will also be removed.
                </p>
            </div>
            <div style="padding:0 24px 20px;display:flex;justify-content:flex-end;gap:8px;">
                <button type="button" class="btn btn--secondary" data-bs-dismiss="modal">Cancel</button>
                <form id="deleteForm" method="POST" class="d-inline">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn--danger">
                        <i class="las la-trash me-1"></i> Delete Award
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

@endsection

@push('script')
<script>
document.addEventListener('DOMContentLoaded', function () {
    document.getElementById('deleteModal').addEventListener('show.bs.modal', function (e) {
        var btn = e.relatedTarget;
        document.getElementById('deleteAwardName').textContent = btn.getAttribute('data-name');
        document.getElementById('deleteForm').action = btn.getAttribute('data-url');
    });

    document.querySelectorAll('.award-toggle').forEach(function (el) {
        el.addEventListener('change', function () {
            var cb  = this;
            var dot = cb.closest('.award-card').querySelector('.aw-status-dot');
            fetch(cb.getAttribute('data-url'), {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json',
                    'Content-Type': 'application/json'
                }
            }).then(function (r) { return r.json(); })
              .then(function (d) {
                  if (d.success) {
                      if (dot) dot.className = 'aw-status-dot' + (cb.checked ? ' active' : '');
                  } else {
                      cb.checked = !cb.checked;
                  }
              })
              .catch(function () { cb.checked = !cb.checked; });
        });
    });
});
</script>
@endpush
