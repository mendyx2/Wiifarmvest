@extends($activeTemplate.'layouts.master')

@section('content')

@php
/**
 * nodeParent maps every non-root tree key to its parent key and its
 * position (left|right) relative to that parent.
 * This lets us build the "fill this slot" URL without touching the helper.
 */
$nodeParent = [
    'b' => ['key' => 'a', 'pos' => 'left'],
    'c' => ['key' => 'a', 'pos' => 'right'],
    'd' => ['key' => 'b', 'pos' => 'left'],
    'e' => ['key' => 'b', 'pos' => 'right'],
    'f' => ['key' => 'c', 'pos' => 'left'],
    'g' => ['key' => 'c', 'pos' => 'right'],
    'h' => ['key' => 'd', 'pos' => 'left'],
    'i' => ['key' => 'd', 'pos' => 'right'],
    'j' => ['key' => 'e', 'pos' => 'left'],
    'k' => ['key' => 'e', 'pos' => 'right'],
    'l' => ['key' => 'f', 'pos' => 'left'],
    'm' => ['key' => 'f', 'pos' => 'right'],
    'n' => ['key' => 'g', 'pos' => 'left'],
    'o' => ['key' => 'g', 'pos' => 'right'],
];

/**
 * Renders a single tree node.
 *
 * Filled node  → delegates to the existing showSingleUserinTree_new() helper.
 * Empty node   → if the parent node exists in the tree, emits a clickable
 *                anchor that pre-fills the distributor form (parent + position).
 *                If the parent itself is also absent the slot is inert.
 */
$renderNode = function (string $key) use ($tree, $nodeParent): string {
    $node = $tree[$key] ?? null;

    // ── Filled node ────────────────────────────────────────────────────────
    if ($node) {
        return showSingleUserinTree_new($node);
    }

    // ── Empty node: resolve parent context ─────────────────────────────────
    $parentKey  = $nodeParent[$key]['key']  ?? null;
    $position   = $nodeParent[$key]['pos']  ?? null;
    $parentNode = ($parentKey && isset($tree[$parentKey])) ? $tree[$parentKey] : null;

    $emptyImg = getImage_tree('assets/images/user/profile/', '120x120', true);

    if ($parentNode && $position) {
        // Clickable: directs to distributor form with parent + position pre-filled
        $url  = route('user.distributor.index')
              . '?parent=' . urlencode($parentNode->username)
              . '&position=' . $position;

        $label   = ucfirst($position) . ' slot';
        $tooltip = "Fill {$parentNode->username}'s {$label}";

        $html  = "<a href=\"{$url}\" class=\"user tree-slot-link\" title=\"{$tooltip}\">";
        $html .= "<img src=\"{$emptyImg}\" alt=\"empty\" class=\"no-user tree-slot-img\">";
        $html .= "<p class=\"user-name tree-slot-label\">"
               . "<i class=\"las la-plus-circle\"></i> {$label}</p>";
        $html .= "</a>";
    } else {
        // Inert: parent is also absent — render a non-interactive placeholder
        $html  = "<div class=\"user\" type=\"button\">";
        $html .= "<img src=\"{$emptyImg}\" alt=\"empty\" class=\"no-user\">";
        $html .= "<p class=\"user-name\">Empty</p>";
        $html .= "</div>";
    }

    // The helper always appends this connector line; we must match the output.
    $html .= "<span class=\"line\"></span>";

    return $html;
};
@endphp

<div class="tree-page">
 
    {{-- ── Header ── --}}
    <div class="tree-header-card">
        <div class="tree-header-left">
            <div class="tree-header-icon"><i class="las la-project-diagram"></i></div>
            <div>
                <h6 class="tree-header-title">My Genealogy Tree</h6>
                <p class="tree-header-sub">{{ $user->fullname }} &bull; @ {{ $user->username }}</p>
            </div>
        </div>
        <form action="{{ route('user.other.tree.search') }}" method="GET" class="tree-search-form">
            <div class="tree-search-wrap">
                <i class="las la-search"></i>
                <input type="text" name="username" placeholder="Search username...">
            </div>
            <button type="submit" class="tree-search-btn"><i class="las la-search"></i> Search</button>
        </form>
    </div>

    {{-- ── Legend ── --}}
    <div class="tree-legend">
        <span class="tree-legend-item"><span class="tree-legend-dot active"></span> Active member</span>
        <span class="tree-legend-item"><span class="tree-legend-dot empty"></span> Empty — click to add</span>
        <span class="tree-legend-item"><i class="las la-hand-pointer" style="color:#9CA3AF;font-size:.8rem;"></i> Click member for details</span>
    </div>

    {{-- ── Tree Canvas ── --}}
    <div class="tree-card">
        <div class="tree-scroll-wrap">
            <div class="tree-canvas-outer" id="treeOuter">
            <div class="tree-canvas" id="treeCanvas">

                {{-- Level 1 — Root --}}
                <div class="tree-level tree-level-1">
                    <div class="tree-node-wrap">
                        @php echo showSingleUserinTree_new($tree['a']); @endphp
                    </div>
                </div>

                {{-- Connector 1→2 --}}
                <div class="tree-connector tree-connector-1-2">
                    <div class="tree-v-line"></div>
                    <div class="tree-h-spread tree-h-spread-2">
                        <div class="tree-h-line"></div>
                        <div class="tree-h-line"></div>
                    </div>
                    <div class="tree-v-pair">
                        <div class="tree-v-line-short"></div>
                        <div class="tree-v-line-short"></div>
                    </div>
                </div>

                {{-- Level 2 --}}
                <div class="tree-level tree-level-2">
                    @foreach(['b','c'] as $k)
                    <div class="tree-node-wrap">
                        @php echo $renderNode($k); @endphp
                    </div>
                    @endforeach
                </div>

                {{-- Connector 2→3 --}}
                <div class="tree-connector tree-connector-2-3">
                    <div class="tree-v-pair">
                        <div class="tree-v-line"></div>
                        <div class="tree-v-line"></div>
                    </div>
                    <div class="tree-h-spread tree-h-spread-4">
                        <div class="tree-h-line"></div>
                        <div class="tree-h-line"></div>
                        <div class="tree-h-line"></div>
                        <div class="tree-h-line"></div>
                    </div>
                    <div class="tree-v-quad">
                        <div class="tree-v-line-short"></div>
                        <div class="tree-v-line-short"></div>
                        <div class="tree-v-line-short"></div>
                        <div class="tree-v-line-short"></div>
                    </div>
                </div>

                {{-- Level 3 --}}
                <div class="tree-level tree-level-3">
                    @foreach(['d','e','f','g'] as $k)
                    <div class="tree-node-wrap">
                        @php echo $renderNode($k); @endphp
                    </div>
                    @endforeach
                </div>

                {{-- Connector 3→4 --}}
                <div class="tree-connector tree-connector-3-4">
                    <div class="tree-v-oct-pre">
                        @for($i=0;$i<4;$i++)<div class="tree-v-line"></div>@endfor
                    </div>
                    <div class="tree-h-spread tree-h-spread-8">
                        @for($i=0;$i<8;$i++)<div class="tree-h-line"></div>@endfor
                    </div>
                    <div class="tree-v-oct">
                        @for($i=0;$i<8;$i++)<div class="tree-v-line-short"></div>@endfor
                    </div>
                </div>

                {{-- Level 4 --}}
                <div class="tree-level tree-level-4">
                    @foreach(['h','i','j','k','l','m','n','o'] as $k)
                    <div class="tree-node-wrap">
                        @php echo $renderNode($k); @endphp
                    </div>
                    @endforeach
                </div>

            </div>
            </div>
        </div>
    </div>

</div>

{{-- ── User Details Modal ── --}}
@push('modal')
<div class="modal fade" id="exampleModalCenter" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content tree-modal-content">
            <div class="tree-modal-header">
                <div class="tree-modal-avatar" id="modalAvatar">
                    <img src="#" alt="" class="tree_image" id="modalImg" style="display:none;">
                    <span id="modalInitials"></span>
                </div>
                <div class="tree-modal-info">
                    <h6 class="tree-modal-name tree_name"></h6>
                    <p class="tree-modal-status tree_status"></p>
                    <p class="tree-modal-plan tree_plan"></p>
                </div>
                <button type="button" class="tree-modal-close" data-bs-dismiss="modal">
                    <i class="las la-times"></i>
                </button>
            </div>
            <div class="tree-modal-body">
                <div class="tree-modal-row">
                    <span class="tree-modal-label"><i class="las la-user-friends"></i> Referred By</span>
                    <span class="tree-modal-value tree_ref">—</span>
                </div>

                <div class="bk-pv-row" id="modalPvRow">
                    <div class="bk-pv-label">
                        <i class="las la-project-diagram"></i> Binary Pairing PV
                    </div>
                    <div class="bk-pv-bars">
                        <div class="bk-pv-leg">
                            <div class="bk-pv-leg-top">
                                <span class="bk-pv-leg-lbl"><i class="las la-arrow-alt-circle-left"></i> Left PV</span>
                                <span class="bk-pv-leg-val" id="modalPvLeftVal">0.00</span>
                            </div>
                            <div class="bk-pv-bar-track">
                                <div class="bk-pv-bar-fill bk-pv-left" id="modalPvLeftBar" style="width:0%"></div>
                            </div>
                        </div>
                        <div class="bk-pv-leg">
                            <div class="bk-pv-leg-top">
                                <span class="bk-pv-leg-lbl"><i class="las la-arrow-alt-circle-right"></i> Right PV</span>
                                <span class="bk-pv-leg-val" id="modalPvRightVal">0.00</span>
                            </div>
                            <div class="bk-pv-bar-track">
                                <div class="bk-pv-bar-fill bk-pv-right" id="modalPvRightBar" style="width:0%"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bk-pv-row" id="modalPvRow">
                    <div class="bk-pv-label">
                        <i class="las la-project-diagram"></i> Ranking PV
                    </div>
                    <div class="bk-pv-bars">
                        <div class="bk-pv-leg">
                            <div class="bk-pv-leg-top">
                                <span class="bk-pv-leg-lbl"><i class="las la-arrow-alt-circle-left"></i> Left PV</span>
                                <span class="bk-pv-leg-val" id="modalPvLeftRankVal">0.00</span>
                            </div>
                        </div>
                        <div class="bk-pv-leg">
                            <div class="bk-pv-leg-top">
                                <span class="bk-pv-leg-lbl"><i class="las la-arrow-alt-circle-right"></i> Right PV</span>
                                <span class="bk-pv-leg-val" id="modalPvRightRankVal">0.00</span>
                            </div>
                        </div>
                    </div>
                </div>

                <a href="#" class="tree-modal-btn tree_url">
                    <i class="las la-project-diagram"></i> View Their Tree
                </a>
            </div>
        </div>
    </div>
</div>
@endpush

@endsection

@push('script')
<script>
"use strict";

// Auto-scale tree to fit — mobile only
function scaleTree() {
    var canvas = document.getElementById('treeCanvas');
    var outer  = document.getElementById('treeOuter');
    if (!canvas || !outer) return;

    // Always reset first
    canvas.style.transform = 'scale(1)';
    canvas.style.marginBottom = '0';

    // Only scale on mobile screens
    if (window.innerWidth >= 768) return;

    var canvasW = canvas.offsetWidth;
    var outerW  = outer.offsetWidth;

    if (canvasW > outerW) {
        var scale = outerW / canvasW;
        canvas.style.transform = 'scale(' + scale + ')';
        var scaledH = canvas.offsetHeight * scale;
        canvas.style.marginBottom = -(canvas.offsetHeight - scaledH) + 'px';
    }
}

document.addEventListener('DOMContentLoaded', scaleTree);
window.addEventListener('resize', scaleTree);

(function($) {
    $('.showDetails').on('click', function() {
        var name   = $(this).data('name');
        var status = $(this).data('status');
        var plan   = $(this).data('plan');
        var img    = $(this).data('image');
        var refby  = $(this).data('refby');
        var url    = $(this).data('treeurl');
        var pvLeft  = parseFloat($(this).data('pvleft'))  || 0;
        var pvRight = parseFloat($(this).data('pvright')) || 0;
        var pvLeftrank  = parseFloat($(this).data('pvleftrank'))  || 0;
        var pvRightrank = parseFloat($(this).data('pvrightrank')) || 0;

        $('.tree_name').text(name);
        $('.tree_status').text(status);
        $('.tree_plan').text(plan || '');
        $('.tree_ref').text(refby || '—');
        $('.tree_url').attr('href', url);

        var pvMax = Math.max(pvLeft, pvRight, 1);
        $('#modalPvLeftVal').text(pvLeft.toFixed(2));
        $('#modalPvRightVal').text(pvRight.toFixed(2));

        $('#modalPvLeftRankVal').text(pvLeftrank.toFixed(2));
        $('#modalPvRightRankVal').text(pvRightrank.toFixed(2));
        $('#modalPvLeftBar').css('width', Math.min(100, Math.round(pvLeft / pvMax * 100)) + '%');
        $('#modalPvRightBar').css('width', Math.min(100, Math.round(pvRight / pvMax * 100)) + '%');

        // Avatar: show image, fallback to initials
        var initial = name ? name.trim().charAt(0).toUpperCase() : '?';
        $('#modalInitials').text(initial);
        var $img = $('#modalImg');
        if (img && img !== '') {
            $img.attr('src', img).show();
            $('#modalInitials').hide();
        } else {
            $img.hide();
            $('#modalInitials').show();
        }

        $('#exampleModalCenter').modal('show');
    });
})(jQuery);
</script>
@endpush
