{{--
    One dashboard-style panel of widget boxes.
    $group: ['label', 'icon', 'lines', 'total'?]  ·  $bg: widget colour  ·  $note: optional caption
    Long panels (more than 4 figures) hide empty boxes and name them underneath.
--}}
@php
    $hideEmpty = count($group['lines']) > 4;
    $boxes     = $hideEmpty ? array_filter($group['lines'], fn ($l) => $l['amount'] != 0) : $group['lines'];
    $empty     = $hideEmpty ? array_filter($group['lines'], fn ($l) => $l['amount'] == 0) : [];
@endphp

<div class="db-finance-block fr-boxes">
    <div class="db-finance-block__head fr-block-head">
        <span><i class="{{ $group['icon'] }}"></i> {{ __($group['label']) }}</span>
        @isset($group['total'])
            <span class="fr-block-head__total">{{ showAmount($group['total']) }}</span>
        @endisset
    </div>

    <div class="row gy-4">
        @forelse ($boxes as $line)
            <div class="col-xxl-4 col-md-6">
                <x-widget style="6" outline="true" :bg="$bg" :icon="$line['icon']" :link="$line['link']"
                    :title="$line['label']" :value="showAmount($line['amount'])" :subheading="$line['note']" />
            </div>
        @empty
            <div class="col-12">
                <p class="fr-idle mb-0">@lang('Nothing recorded for this period.')</p>
            </div>
        @endforelse
    </div>

    @if ($empty)
        <p class="fr-idle">
            <strong>@lang('Nothing recorded'):</strong>
            {{ implode(' · ', array_map(fn ($l) => __($l['label']), $empty)) }}
        </p>
    @endif

    @isset($note)
        <p class="fr-note">{{ $note }}</p>
    @endisset
</div>
