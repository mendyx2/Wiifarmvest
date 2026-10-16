@extends('admin.layouts.app')

@section('panel')

@push('style')
<style>
/* ═══════════════════════════════════════════════════════
   AWARD FORM — Two-panel Premium Layout
   ═══════════════════════════════════════════════════════ */

.aw-form-header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    margin-bottom: 28px;
    gap: 12px;
    flex-wrap: wrap;
}
.aw-form-eyebrow {
    font-size: .7rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .1em;
    color: #16a34a;
    margin-bottom: 4px;
}
.aw-form-title {
    font-size: 1.5rem;
    font-weight: 800;
    color: #1a1f2e;
    letter-spacing: -.025em;
    margin-bottom: 3px;
}
.aw-form-sub { font-size: .82rem; color: #9ca3af; }

/* Section card */
.aw-section {
    background: #fff;
    border-radius: 16px;
    border: 1px solid #f0f0f0;
    box-shadow: 0 2px 8px rgba(0,0,0,.05);
    margin-bottom: 20px;
    overflow: hidden;
}
.aw-section-head {
    padding: 15px 20px;
    border-bottom: 1px solid #f3f4f6;
    display: flex;
    align-items: center;
    gap: 12px;
}
.aw-section-icon {
    width: 36px; height: 36px;
    border-radius: 10px;
    display: flex; align-items: center; justify-content: center;
    font-size: 1rem;
    flex-shrink: 0;
}
.icon-green  { background: #dcfce7; color: #16a34a; }
.icon-blue   { background: #dbeafe; color: #2563eb; }
.icon-purple { background: #ede9fe; color: #7c3aed; }
.icon-amber  { background: #fef3c7; color: #d97706; }
.icon-slate  { background: #f1f5f9; color: #475569; }
.aw-section-title { font-size: .88rem; font-weight: 700; color: #1a1f2e; }
.aw-section-hint  { font-size: .73rem; color: #d1d5db; margin-left: auto; }
.aw-section-body  { padding: 20px; }

/* PV metric inputs */
.pv-input-group {
    background: #f9fafb;
    border: 1.5px solid #f0f0f0;
    border-radius: 12px;
    padding: 14px 16px;
    transition: border-color .2s, box-shadow .2s, background .2s;
    cursor: text;
}
.pv-input-group:focus-within {
    border-color: #16a34a;
    box-shadow: 0 0 0 3px rgba(22,163,74,.08);
    background: #fff;
}
.pv-input-label {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: .7rem;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: .07em;
    margin-bottom: 8px;
}
.pv-dot { width: 8px; height: 8px; border-radius: 50%; flex-shrink: 0; }
.pv-dot-total { background: #6366f1; }
.pv-dot-left  { background: #16a34a; }
.pv-dot-right { background: #e11d48; }
.pv-dot-gold  { background: #d97706; }
.pv-color-total { color: #6366f1; }
.pv-color-left  { color: #16a34a; }
.pv-color-right { color: #e11d48; }
.pv-color-gold  { color: #d97706; }
.pv-input-group input {
    border: none; background: transparent;
    font-size: 1.4rem; font-weight: 800;
    color: #1a1f2e; width: 100%; outline: none; padding: 0;
}
.pv-input-group input::placeholder { color: #e5e7eb; font-weight: 400; font-size: 1.1rem; }
.pv-input-helper { font-size: .71rem; color: #9ca3af; margin-top: 5px; }

/* Image upload zone */
.aw-image-zone {
    border: 2px dashed #e5e7eb;
    border-radius: 14px;
    padding: 28px 20px;
    text-align: center;
    cursor: pointer;
    transition: all .25s;
    background: #fafafa;
}
.aw-image-zone:hover, .aw-image-zone.drag-over {
    border-color: #16a34a;
    background: #f0fdf4;
}
.aw-image-zone img { max-height: 160px; width: 100%; object-fit: contain; border-radius: 8px; }
.aw-image-zone-hint i { font-size: 2.6rem; display: block; margin-bottom: 10px; color: #d1d5db; }
.aw-image-zone-hint strong { display: block; font-size: .85rem; color: #374151; margin-bottom: 4px; }
.aw-image-zone-hint span { font-size: .73rem; color: #9ca3af; }

/* Toggle */
.aw-status-row {
    display: flex; align-items: center;
    justify-content: space-between;
    gap: 12px;
}
.aw-status-label strong { display: block; font-size: .88rem; font-weight: 700; color: #1a1f2e; }
.aw-status-label small  { font-size: .74rem; color: #9ca3af; }
.aw-toggle-sw {
    position: relative; display: inline-block;
    width: 52px; height: 28px; flex-shrink: 0;
}
.aw-toggle-sw input { opacity: 0; width: 0; height: 0; }
.aw-toggle-sw-track {
    position: absolute;
    top: 0; left: 0; right: 0; bottom: 0;
    background: #d1d5db; border-radius: 28px;
    cursor: pointer; transition: background .25s;
}
.aw-toggle-sw-track::before {
    content: '';
    position: absolute;
    width: 22px; height: 22px;
    left: 3px; top: 3px;
    background: #fff; border-radius: 50%;
    transition: transform .25s cubic-bezier(.34,1.56,.64,1);
    box-shadow: 0 1px 4px rgba(0,0,0,.18);
}
.aw-toggle-sw input:checked + .aw-toggle-sw-track { background: #16a34a; }
.aw-toggle-sw input:checked + .aw-toggle-sw-track::before { transform: translateX(24px); }

/* Submit bar */
.aw-submit-bar {
    background: #fff;
    border-radius: 16px;
    border: 1px solid #f0f0f0;
    box-shadow: 0 2px 8px rgba(0,0,0,.05);
    padding: 16px 20px;
    display: flex; align-items: center;
    justify-content: flex-end;
    gap: 10px;
    margin-top: 4px;
}

/* Live preview (sticky right column) */
.aw-preview-wrap {
    position: sticky;
    top: 80px;
}
.aw-preview-label {
    font-size: .68rem;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: .12em;
    color: #9ca3af;
    margin-bottom: 12px;
    display: flex; align-items: center; gap: 6px;
}
.aw-preview-label::after {
    content: '';
    flex: 1;
    height: 1px;
    background: #f0f0f0;
}
.aw-preview-card {
    background: linear-gradient(160deg, #f0fdf4 0%, #dcfce7 100%);
    border-radius: 18px;
    border: 1px solid #bbf7d0;
    padding: 24px 20px;
    text-align: center;
}
.aw-preview-avatar-ring {
    display: inline-flex; align-items: center; justify-content: center;
    width: 90px; height: 90px;
    border-radius: 50%;
    background: linear-gradient(135deg, #16a34a, #0D5C2E);
    padding: 3px;
    margin: 0 auto 14px;
    box-shadow: 0 4px 18px rgba(13,92,46,.25);
}
.aw-preview-avatar-inner {
    width: 84px; height: 84px;
    border-radius: 50%;
    background: #f0fdf4;
    display: flex; align-items: center; justify-content: center;
    font-size: 2.2rem; color: #16a34a;
    overflow: hidden;
}
.aw-preview-avatar-inner img { width: 100%; height: 100%; object-fit: cover; }
.aw-preview-name {
    font-size: 1rem; font-weight: 800; color: #0D5C2E;
    margin-bottom: 4px; word-break: break-word;
}
.aw-preview-desc {
    font-size: .75rem; color: #6b7280; margin-bottom: 18px;
    line-height: 1.5; min-height: 22px;
}
.aw-preview-metrics { display: flex; flex-direction: column; gap: 6px; }
.aw-preview-metric {
    display: flex; align-items: center; justify-content: space-between;
    background: rgba(255,255,255,.65);
    border-radius: 10px; padding: 9px 12px;
    backdrop-filter: blur(4px);
}
.aw-preview-metric-label {
    font-size: .75rem; color: #6b7280; font-weight: 600;
    display: flex; align-items: center; gap: 6px;
}
.aw-preview-metric-val { font-size: .88rem; font-weight: 800; color: #1a1f2e; }
</style>
@endpush

<div class="aw-form-header">
    <div>
        <div class="aw-form-eyebrow"><i class="las la-trophy me-1"></i>Award Management</div>
        <div class="aw-form-title">{{ isset($award) ? 'Edit Award' : 'New Award' }}</div>
        <div class="aw-form-sub">
            {{ isset($award) ? 'Update award details and qualification requirements' : 'Configure a new achievement award for your members' }}
        </div>
    </div>
    <a href="{{ route('admin.awards.index') }}" class="btn btn--secondary btn-sm">
        <i class="las la-arrow-left me-1"></i> Back to Awards
    </a>
</div>

<form action="{{ isset($award) ? route('admin.awards.update', $award->id) : route('admin.awards.store') }}"
      method="POST" enctype="multipart/form-data" id="awardForm">
    @csrf

    <div class="row g-4">
        {{-- ── Left: form sections ──────────────────────────────── --}}
        <div class="col-lg-8">

            {{-- Award Details --}}
            <div class="aw-section">
                <div class="aw-section-head">
                    <div class="aw-section-icon icon-green"><i class="las la-star"></i></div>
                    <div class="aw-section-title">Award Details</div>
                </div>
                <div class="aw-section-body">
                    <div class="row g-3">
                        <div class="col-md-8">
                            <label class="form-label fw-semibold" style="font-size:.82rem;">
                                Award Name <span class="text-danger">*</span>
                            </label>
                            <input type="text"
                                   name="name"
                                   id="fieldName"
                                   class="form-control @error('name') is-invalid @enderror"
                                   placeholder="e.g. Diamond Director"
                                   value="{{ old('name', $award->name ?? '') }}"
                                   required>
                            @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold" style="font-size:.82rem;">
                                Sort Order <span class="text-danger">*</span>
                            </label>
                            <input type="number"
                                   name="sort_order"
                                   class="form-control @error('sort_order') is-invalid @enderror"
                                   min="0" placeholder="1"
                                   value="{{ old('sort_order', $award->sort_order ?? 1) }}"
                                   required>
                            @error('sort_order') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold" style="font-size:.82rem;">Description</label>
                            <textarea name="description"
                                      id="fieldDesc"
                                      class="form-control @error('description') is-invalid @enderror"
                                      rows="3"
                                      placeholder="Brief description of what this award recognises…">{{ old('description', $award->description ?? '') }}</textarea>
                            @error('description') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>
                </div>
            </div>

            {{-- PV Requirements --}}
            <div class="aw-section">
                <div class="aw-section-head">
                    <div class="aw-section-icon icon-blue"><i class="las la-chart-bar"></i></div>
                    <div class="aw-section-title">PV Requirements</div>
                    <span class="aw-section-hint">All fields required</span>
                </div>
                <div class="aw-section-body">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <div class="pv-input-group">
                                <div class="pv-input-label pv-color-total">
                                    <span class="pv-dot pv-dot-total"></span> Total PV Required
                                </div>
                                <input type="number"
                                       name="required_total_pv"
                                       id="fieldTotal"
                                       step="0.01" min="0" placeholder="0"
                                       value="{{ old('required_total_pv', $award->required_total_pv ?? '') }}"
                                       required>
                                <div class="pv-input-helper">Combined left + right pairing PV</div>
                            </div>
                            @error('required_total_pv') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-4">
                            <div class="pv-input-group">
                                <div class="pv-input-label pv-color-left">
                                    <span class="pv-dot pv-dot-left"></span> Left Leg PV
                                </div>
                                <input type="number"
                                       name="required_left_pv"
                                       id="fieldLeft"
                                       step="0.01" min="0" placeholder="0"
                                       value="{{ old('required_left_pv', $award->required_left_pv ?? '') }}"
                                       required>
                                <div class="pv-input-helper">Minimum from left leg</div>
                            </div>
                            @error('required_left_pv') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-4">
                            <div class="pv-input-group">
                                <div class="pv-input-label pv-color-right">
                                    <span class="pv-dot pv-dot-right"></span> Right Leg PV
                                </div>
                                <input type="number"
                                       name="required_right_pv"
                                       id="fieldRight"
                                       step="0.01" min="0" placeholder="0"
                                       value="{{ old('required_right_pv', $award->required_right_pv ?? '') }}"
                                       required>
                                <div class="pv-input-helper">Minimum from right leg</div>
                            </div>
                            @error('required_right_pv') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                        </div>
                    </div>
                </div>
            </div>

            {{-- Prerequisite & Payment --}}
            <div class="aw-section">
                <div class="aw-section-head">
                    <div class="aw-section-icon icon-purple"><i class="las la-link"></i></div>
                    <div class="aw-section-title">Prerequisite &amp; Payment</div>
                </div>
                <div class="aw-section-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold" style="font-size:.82rem;">Prerequisite Award</label>
                            <select name="prerequisite_award_id"
                                    class="form-select @error('prerequisite_award_id') is-invalid @enderror">
                                <option value="">— None —</option>
                                @foreach($awards as $a)
                                    @if(!isset($award) || $a->id !== $award->id)
                                        <option value="{{ $a->id }}"
                                            {{ old('prerequisite_award_id', $award->prerequisite_award_id ?? '') == $a->id ? 'selected' : '' }}>
                                            {{ $a->name }}
                                        </option>
                                    @endif
                                @endforeach
                            </select>
                            <small class="text-muted d-block mt-1" style="font-size:.74rem;line-height:1.5;">
                                Members must have at least one person on each leg who has earned this award before qualifying.
                            </small>
                            @error('prerequisite_award_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold" style="font-size:.82rem;">
                                Payment Amount <span class="text-danger">*</span>
                            </label>
                            <div class="pv-input-group">
                                <div class="pv-input-label pv-color-gold">
                                    <span class="pv-dot pv-dot-gold"></span> Award Payout
                                </div>
                                <input type="number"
                                       name="payment_amount"
                                       id="fieldPayment"
                                       step="0.01" min="0" placeholder="0.00"
                                       value="{{ old('payment_amount', $award->payment_amount ?? '') }}"
                                       required>
                                <div class="pv-input-helper">Credited to member's awards wallet on payment</div>
                            </div>
                            @error('payment_amount') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                        </div>
                    </div>
                </div>
            </div>

            {{-- Image & Status --}}
            <div class="aw-section">
                <div class="aw-section-head">
                    <div class="aw-section-icon icon-slate"><i class="las la-image"></i></div>
                    <div class="aw-section-title">Image &amp; Status</div>
                </div>
                <div class="aw-section-body">
                    <div class="row g-3 align-items-start">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold" style="font-size:.82rem;">Award Image</label>
                            <div class="aw-image-zone" id="imageZone"
                                 onclick="document.getElementById('imageInput').click()">
                                @if(isset($award) && $award->image_url)
                                    <img src="{{ $award->image_url }}" id="imagePreview" alt="Award">
                                    <div class="aw-image-zone-hint mt-2" id="imagePlaceholder" style="display:none;">
                                @else
                                    <img src="" id="imagePreview" style="display:none;max-height:150px;">
                                    <div class="aw-image-zone-hint" id="imagePlaceholder">
                                @endif
                                        <i class="las la-cloud-upload-alt"></i>
                                        <strong>Click to upload or drag &amp; drop</strong>
                                        <span>JPG, PNG, GIF · Recommended 200×200px</span>
                                    </div>
                            </div>
                            <input type="file" name="image" id="imageInput" class="d-none"
                                   accept="image/jpeg,image/png,image/jpg,image/gif">
                            @error('image') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold" style="font-size:.82rem;">Award Status</label>
                            <div style="background:#f9fafb;border:1.5px solid #f0f0f0;border-radius:12px;padding:16px;">
                                <div class="aw-status-row">
                                    <div class="aw-status-label">
                                        <strong id="statusText">
                                            {{ old('status', $award->status ?? 1) == 1 ? 'Active' : 'Inactive' }}
                                        </strong>
                                        <small id="statusSub">
                                            {{ old('status', $award->status ?? 1) == 1 ? 'Being evaluated in cron checks' : 'Disabled — not checked' }}
                                        </small>
                                    </div>
                                    <input type="hidden" name="status" value="0">
                                    <label class="aw-toggle-sw">
                                        <input type="checkbox"
                                               name="status" value="1"
                                               id="statusToggle"
                                               {{ old('status', $award->status ?? 1) == 1 ? 'checked' : '' }}>
                                        <span class="aw-toggle-sw-track"></span>
                                    </label>
                                </div>
                                <p style="font-size:.73rem;color:#9ca3af;margin:10px 0 0;">
                                    Inactive awards are skipped during automated qualification checks.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>

        {{-- ── Right: live preview ──────────────────────────────── --}}
        <div class="col-lg-4">
            <div class="aw-preview-wrap">
                <div class="aw-preview-label">Live Preview</div>
                <div class="aw-preview-card">
                    <div class="aw-preview-avatar-ring">
                        <div class="aw-preview-avatar-inner">
                            <img src="" id="previewAvatarImg" style="display:none;">
                            <i class="las la-trophy" id="previewAvatarIcon"></i>
                        </div>
                    </div>
                    <div class="aw-preview-name" id="previewNameOut">
                        {{ old('name', $award->name ?? '') ?: 'Award Name' }}
                    </div>
                    <div class="aw-preview-desc" id="previewDescOut">
                        {{ Str::limit(old('description', $award->description ?? ''), 64) ?: 'Award description will appear here…' }}
                    </div>
                    <div class="aw-preview-metrics">
                        <div class="aw-preview-metric">
                            <span class="aw-preview-metric-label" style="color:#6366f1;">
                                <span style="width:8px;height:8px;border-radius:50%;background:#6366f1;display:inline-block;"></span>
                                Total PV
                            </span>
                            <span class="aw-preview-metric-val" id="previewTotalOut">
                                {{ number_format((float) old('required_total_pv', $award->required_total_pv ?? 0)) }}
                            </span>
                        </div>
                        <div class="aw-preview-metric">
                            <span class="aw-preview-metric-label" style="color:#16a34a;">
                                <span style="width:8px;height:8px;border-radius:50%;background:#16a34a;display:inline-block;"></span>
                                Left Leg PV
                            </span>
                            <span class="aw-preview-metric-val" id="previewLeftOut">
                                {{ number_format((float) old('required_left_pv', $award->required_left_pv ?? 0)) }}
                            </span>
                        </div>
                        <div class="aw-preview-metric">
                            <span class="aw-preview-metric-label" style="color:#e11d48;">
                                <span style="width:8px;height:8px;border-radius:50%;background:#e11d48;display:inline-block;"></span>
                                Right Leg PV
                            </span>
                            <span class="aw-preview-metric-val" id="previewRightOut">
                                {{ number_format((float) old('required_right_pv', $award->required_right_pv ?? 0)) }}
                            </span>
                        </div>
                        <div class="aw-preview-metric">
                            <span class="aw-preview-metric-label" style="color:#d97706;">
                                <span style="width:8px;height:8px;border-radius:50%;background:#d97706;display:inline-block;"></span>
                                Payout
                            </span>
                            <span class="aw-preview-metric-val" id="previewPaymentOut">
                                {{ number_format((float) old('payment_amount', $award->payment_amount ?? 0), 2) }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="aw-submit-bar">
        <a href="{{ route('admin.awards.index') }}" class="btn btn--secondary">
            <i class="las la-times me-1"></i> Cancel
        </a>
        <button type="submit" class="btn btn--primary">
            <i class="las la-save me-1"></i>
            {{ isset($award) ? 'Update Award' : 'Create Award' }}
        </button>
    </div>
</form>

@endsection

@push('script')
<script>
document.addEventListener('DOMContentLoaded', function () {

    // ── Image upload + drag-and-drop ──────────────────────
    var imageInput   = document.getElementById('imageInput');
    var imagePreview = document.getElementById('imagePreview');
    var placeholder  = document.getElementById('imagePlaceholder');
    var previewImg   = document.getElementById('previewAvatarImg');
    var previewIcon  = document.getElementById('previewAvatarIcon');
    var imageZone    = document.getElementById('imageZone');

    function applyImage(src) {
        imagePreview.src = src;
        imagePreview.style.display = 'block';
        if (placeholder) placeholder.style.display = 'none';
        previewImg.src = src;
        previewImg.style.display = 'block';
        if (previewIcon) previewIcon.style.display = 'none';
    }

    if (imageInput) {
        imageInput.addEventListener('change', function () {
            if (!this.files[0]) return;
            var r = new FileReader();
            r.onload = function (e) { applyImage(e.target.result); };
            r.readAsDataURL(this.files[0]);
        });
    }

    if (imageZone) {
        imageZone.addEventListener('dragover', function (e) {
            e.preventDefault();
            this.classList.add('drag-over');
        });
        imageZone.addEventListener('dragleave', function () { this.classList.remove('drag-over'); });
        imageZone.addEventListener('drop', function (e) {
            e.preventDefault();
            this.classList.remove('drag-over');
            var file = e.dataTransfer.files[0];
            if (!file || !file.type.startsWith('image/')) return;
            try {
                var dt = new DataTransfer();
                dt.items.add(file);
                imageInput.files = dt.files;
            } catch (ex) {}
            var r = new FileReader();
            r.onload = function (ev) { applyImage(ev.target.result); };
            r.readAsDataURL(file);
        });
    }

    // ── Live preview bindings ─────────────────────────────
    function bind(fieldId, outId, transform) {
        var el  = document.getElementById(fieldId);
        var out = document.getElementById(outId);
        if (!el || !out) return;
        el.addEventListener('input', function () {
            out.textContent = transform ? transform(this.value) : (this.value || '—');
        });
    }

    bind('fieldName', 'previewNameOut', function (v) {
        return v.trim() || 'Award Name';
    });
    bind('fieldDesc', 'previewDescOut', function (v) {
        if (!v.trim()) return 'Award description will appear here…';
        return v.length > 64 ? v.slice(0, 64) + '…' : v;
    });
    bind('fieldTotal',   'previewTotalOut',   function (v) { return v ? parseInt(v, 10).toLocaleString() : '0'; });
    bind('fieldLeft',    'previewLeftOut',    function (v) { return v ? parseInt(v, 10).toLocaleString() : '0'; });
    bind('fieldRight',   'previewRightOut',   function (v) { return v ? parseInt(v, 10).toLocaleString() : '0'; });
    bind('fieldPayment', 'previewPaymentOut', function (v) { return v ? parseFloat(v).toFixed(2) : '0.00'; });

    // ── Status toggle label ───────────────────────────────
    var toggle     = document.getElementById('statusToggle');
    var statusText = document.getElementById('statusText');
    var statusSub  = document.getElementById('statusSub');
    if (toggle) {
        toggle.addEventListener('change', function () {
            if (statusText) statusText.textContent = this.checked ? 'Active' : 'Inactive';
            if (statusSub)  statusSub.textContent  = this.checked
                ? 'Being evaluated in cron checks'
                : 'Disabled — not checked';
        });
    }
});
</script>
@endpush
