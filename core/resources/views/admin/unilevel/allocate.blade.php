@extends('admin.layouts.app')

@section('panel')

<div class="row g-4">

    {{-- ── Project picker ──────────────────────────────────────── --}}
    <div class="col-12">
        <div class="card b-radius--10 overflow-hidden">
            <div class="card-header">
                <h5 class="card-title mb-0">Allocate Generations to a Project</h5>
                <small class="text-muted">
                    Select a project below, then assign which unilevel generations apply to it.
                </small>
            </div>
            <div class="card-body">
                <div class="ul-project-rail">
                    @forelse($projects as $proj)
                    <a href="{{ route('admin.unilevel.allocate.index', ['project' => $proj->id]) }}"
                       class="ul-proj-pill {{ $selectedProject && $selectedProject->id === $proj->id ? 'ul-proj-pill--active' : '' }}"
                       style="--pill-color: {{ $proj->color }};">
                        <span class="ul-proj-pill-icon" style="background:{{ $proj->color }};">
                            <i class="{{ $proj->icon }}"></i>
                        </span>
                        <span class="ul-proj-pill-body">
                            <span class="ul-proj-pill-name">{{ $proj->title }}</span>
                            <span class="ul-proj-pill-sub">{{ $proj->users_count }} members</span>
                        </span>
                        @if($selectedProject && $selectedProject->id === $proj->id)
                            <span class="ul-proj-pill-check"><i class="las la-check-circle"></i></span>
                        @endif
                    </a>
                    @empty
                        <p class="text-muted py-3">No active projects found.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    {{-- ── Generation allocation ───────────────────────────────── --}}
    @if($selectedProject)
    <div class="col-12">
        <div class="card b-radius--10 overflow-hidden">
            <div class="card-header d-flex align-items-center justify-content-between gap-2 flex-wrap">
                <div class="d-flex align-items-center gap-3">
                    <div class="ul-proj-header-icon" style="background:{{ $selectedProject->color }};">
                        <i class="{{ $selectedProject->icon }}"></i>
                    </div>
                    <div>
                        <h5 class="card-title mb-0">{{ $selectedProject->title }}</h5>
                        <small class="text-muted">
                            Choose which generations receive PRB bonuses when a subscriber makes a purchase
                        </small>
                    </div>
                </div>
                <span class="ul-assigned-chip" id="ulAssignedChip">
                    <i class="las la-layer-group"></i>
                    <span id="ulAssignedCount">{{ $assignedIds->count() }}</span> assigned
                </span>
            </div>

            <form action="{{ route('admin.unilevel.allocate.store', $selectedProject->id) }}" method="POST">
                @csrf

                <div class="card-body">
                    @if($generations->isEmpty())
                        <div class="ul-empty-state">
                            <div class="ul-empty-icon"><i class="las la-layer-group"></i></div>
                            <p class="ul-empty-title">No Generations Configured</p>
                            <p class="ul-empty-sub">
                                You need to create generation tiers first before you can allocate them to projects.
                            </p>
                            <a href="{{ route('admin.unilevel.generations.index') }}" class="btn btn--primary btn-sm">
                                <i class="las la-plus me-1"></i> Add Generations
                            </a>
                        </div>
                    @else
                        <div class="ul-alloc-help">
                            <i class="las la-info-circle"></i>
                            Toggle each generation on or off for this project. Only toggled-on generations
                            will receive a bonus when a <strong>{{ $selectedProject->title }}</strong> subscriber's product is redeemed.
                        </div>

                        <div class="ul-gen-grid" id="ulGenGrid">
                            @foreach($generations as $gen)
                            @php $isAssigned = $assignedIds->contains($gen->id); @endphp
                            <label class="ul-gen-card {{ $isAssigned ? 'ul-gen-card--on' : '' }} {{ !$gen->status ? 'ul-gen-card--disabled' : '' }}"
                                   id="ulCard{{ $gen->id }}">
                                <input type="checkbox"
                                       name="generations[]"
                                       value="{{ $gen->id }}"
                                       class="ul-gen-check"
                                       data-card="ulCard{{ $gen->id }}"
                                       {{ $isAssigned ? 'checked' : '' }}
                                       {{ !$gen->status ? 'disabled' : '' }}>

                                <div class="ul-gen-card-head" style="background:{{ ulGenColor($gen->number) }};">
                                    <span class="ul-gen-num">{{ $gen->number }}</span>
                                    <div class="ul-gen-toggle-dot"></div>
                                </div>

                                <div class="ul-gen-card-body">
                                    <p class="ul-gen-card-title">{{ $gen->title }}</p>
                                    <p class="ul-gen-card-pct">
                                        <i class="las la-percentage"></i>
                                        {{ number_format((float)$gen->percentage, 4) }}% of PRB
                                    </p>
                                    @if(!$gen->status)
                                        <span class="ul-gen-inactive-tag">Inactive</span>
                                    @endif
                                    @if($gen->description)
                                        <p class="ul-gen-card-desc">{{ Str::limit($gen->description, 50) }}</p>
                                    @endif
                                </div>
                            </label>
                            @endforeach
                        </div>
                    @endif
                </div>

                @if($generations->isNotEmpty())
                <div class="card-footer d-flex align-items-center justify-content-between gap-3 flex-wrap">
                    <div class="ul-footer-hint">
                        <i class="las la-info-circle text-muted me-1"></i>
                        <small class="text-muted">Changes take effect on the next product redemption.</small>
                    </div>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn--secondary btn-sm" id="ulDeselectAll">
                            Deselect All
                        </button>
                        <button type="submit" class="btn btn--primary btn-sm">
                            <i class="las la-save me-1"></i> Save Allocation
                        </button>
                    </div>
                </div>
                @endif
            </form>
        </div>
    </div>
    @else
    <div class="col-12">
        <div class="ul-no-project-hint">
            <i class="las la-hand-point-up"></i>
            <p>Select a project above to manage its generation allocation</p>
        </div>
    </div>
    @endif

</div>

@endsection

@push('script')
<script>
(function () {
    'use strict';

    // Toggle card highlight when checkbox changes
    document.querySelectorAll('.ul-gen-check').forEach(function (chk) {
        chk.addEventListener('change', function () {
            var card = document.getElementById(this.dataset.card);
            if (card) {
                card.classList.toggle('ul-gen-card--on', this.checked);
            }
            updateCount();
        });
    });

    function updateCount() {
        var chip = document.getElementById('ulAssignedCount');
        if (!chip) return;
        var n = document.querySelectorAll('.ul-gen-check:checked').length;
        chip.textContent = n;
    }

    var deselectBtn = document.getElementById('ulDeselectAll');
    if (deselectBtn) {
        deselectBtn.addEventListener('click', function () {
            document.querySelectorAll('.ul-gen-check').forEach(function (chk) {
                chk.checked = false;
                var card = document.getElementById(chk.dataset.card);
                if (card) card.classList.remove('ul-gen-card--on');
            });
            updateCount();
        });
    }
})();
</script>
@endpush
