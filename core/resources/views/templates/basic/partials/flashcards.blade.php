@php
    $pendingFlashcards = auth()->user()->pendingFlashcards();
@endphp

@if($pendingFlashcards->count())
{{-- Flashcard overlay --}}
<div class="fc-overlay" id="fcOverlay">

    {{-- Backdrop --}}
    <div class="fc-backdrop"></div>

    {{-- Card wrapper --}}
    <div class="fc-modal" id="fcModal">

        {{-- Progress dots --}}
        @if($pendingFlashcards->count() > 1)
        <div class="fc-dots" id="fcDots">
            @foreach($pendingFlashcards as $i => $fc)
            <span class="fc-dot {{ $i === 0 ? 'fc-dot-active' : '' }}" data-index="{{ $i }}"></span>
            @endforeach
        </div>
        @endif

        {{-- Close button --}}
        <button class="fc-close" id="fcClose" title="Close">
            <i class="las la-times"></i>
        </button>

        {{-- Slides --}}
        <div class="fc-slides" id="fcSlides">
            @foreach($pendingFlashcards as $i => $fc)
            <div class="fc-slide {{ $i === 0 ? 'fc-slide-active' : '' }}"
                 data-id="{{ $fc->id }}"
                 data-index="{{ $i }}">

                {{-- Media --}}
                <div class="fc-media">
                    @if($fc->type === 'image')
                        <img src="{{ $fc->file_url }}" alt="{{ $fc->title }}" class="fc-img">
                    @else
                        <video class="fc-video" controls playsinline>
                            <source src="{{ $fc->file_url }}" type="video/mp4">
                        </video>
                    @endif
                </div>

                {{-- Content --}}
                @if($fc->title || $fc->description)
                <div class="fc-content">
                    @if($fc->title)
                    <h5 class="fc-title">{{ $fc->title }}</h5>
                    @endif
                    @if($fc->description)
                    <p class="fc-desc">{{ $fc->description }}</p>
                    @endif
                </div>
                @endif

                {{-- Footer --}}
                <div class="fc-footer">
                    <span class="fc-counter">
                        {{ $i + 1 }} of {{ $pendingFlashcards->count() }}
                    </span>
                    <button class="fc-done-btn" onclick="fcDismissCurrent()">
                        <i class="las la-check"></i>
                        {{ $i + 1 < $pendingFlashcards->count() ? 'Got it, Next' : "Got it, Close" }}
                    </button>
                </div>

            </div>
            @endforeach
        </div>

    </div>
</div>

<script>
(function() {
    var overlay      = document.getElementById('fcOverlay');
    var modal        = document.getElementById('fcModal');
    var slides       = document.querySelectorAll('.fc-slide');
    var dots         = document.querySelectorAll('.fc-dot');
    var closeBtn     = document.getElementById('fcClose');
    var currentIndex = 0;
    var total        = slides.length;
    var csrfToken    = '{{ csrf_token() }}';
    var dismissUrl   = '{{ url("/user/flashcard") }}';

    // Show overlay with animation
    requestAnimationFrame(function() {
        overlay.classList.add('fc-visible');
    });

    // Close button — dismiss ALL remaining without recording
    closeBtn.addEventListener('click', function() {
        dismissCurrentAndContinue(true);
    });

    function dismissCurrentAndContinue(closeAll) {
        var slide = slides[currentIndex];
        var id    = slide.dataset.id;

        // Record view via AJAX
        fetch(dismissUrl + '/' + id + '/dismiss', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            }
        }).catch(function() {}); // silent fail

        // Animate current slide out
        slide.classList.add('fc-slide-out');

        setTimeout(function() {
            slide.classList.remove('fc-slide-active', 'fc-slide-out');

            if (!closeAll && currentIndex + 1 < total) {
                currentIndex++;
                slides[currentIndex].classList.add('fc-slide-active');
                updateDots();
                // Update done button text
                var btn = slides[currentIndex].querySelector('.fc-done-btn');
                if (btn) {
                    btn.innerHTML = currentIndex + 1 < total
                        ? '<i class="las la-check"></i> Got it, Next'
                        : '<i class="las la-check"></i> Got it, Close';
                }
            } else {
                // Close overlay — record all remaining as viewed too
                if (closeAll) {
                    // Dismiss all remaining slides silently
                    for (var i = currentIndex + 1; i < total; i++) {
                        var rid = slides[i].dataset.id;
                        fetch(dismissUrl + '/' + rid + '/dismiss', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken }
                        }).catch(function() {});
                    }
                }
                overlay.classList.remove('fc-visible');
                setTimeout(function() { overlay.remove(); }, 400);
            }
        }, 350);
    }

    // Expose for inline onclick
    window.fcDismissCurrent = function() {
        dismissCurrentAndContinue(false);
    };

    function updateDots() {
        dots.forEach(function(d, i) {
            d.classList.toggle('fc-dot-active', i === currentIndex);
        });
    }
})();
</script>
@endif
