@extends($activeTemplate.'layouts.master')

@section('content')
<div class="tree-page">

    {{-- ── Header ── --}}
    <div class="tree-header-card">
        <div class="tree-header-left">
            <div class="tree-header-icon"><i class="las la-layer-group"></i></div>
            <div>
                <h6 class="tree-header-title">Stage {{ $st }} Tree</h6>
                <p class="tree-header-sub">{{ $user->fullname }} &bull; @ {{ $user->username }}</p>
            </div>
            <span class="tree-stage-badge">Stage {{ $st }}</span>
        </div>
        <form action="{{ route('user.other.tree.search') }}" method="GET" class="tree-search-form">
            <div class="tree-search-wrap">
                <i class="las la-search"></i>
                <input type="text" name="username" placeholder="Search username...">
            </div>
            <button type="submit" class="tree-search-btn"><i class="las la-search"></i> Search</button>
        </form>
    </div>

    {{-- ── Stage Nav Pills ── --}}
    <div class="tree-stage-nav">
        @for($s = 1; $s <= 5; $s++)
        <a href="{{ route('user.my.treeStage', $s) }}"
           class="tree-stage-pill {{ $st == $s ? 'active' : '' }}">
            Stage {{ $s }}
        </a>
        @endfor
    </div>

    {{-- ── Legend ── --}}
    <div class="tree-legend">
        <span class="tree-legend-item"><span class="tree-legend-dot active"></span> Active member</span>
        <span class="tree-legend-item"><span class="tree-legend-dot empty"></span> Empty slot</span>
        <span class="tree-legend-item"><i class="las la-hand-pointer" style="color:#9CA3AF;font-size:.8rem;"></i> Click node for details</span>
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
                        @php echo showSingleUserinTree_new($tree[$k]); @endphp
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
                        @php echo showSingleUserinTree_new($tree[$k]); @endphp
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
                        @php echo showSingleUserinTree_new($tree[$k], 9); @endphp
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

function scaleTree() {
    var canvas = document.getElementById('treeCanvas');
    var outer  = document.getElementById('treeOuter');
    if (!canvas || !outer) return;

    canvas.style.transform = 'scale(1)';
    canvas.style.marginBottom = '0';

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

        $('.tree_name').text(name);
        $('.tree_status').text(status);
        $('.tree_plan').text(plan || '');
        $('.tree_ref').text(refby || '—');
        $('.tree_url').attr('href', url);

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
