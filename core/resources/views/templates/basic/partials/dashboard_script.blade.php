{{-- Sidebar toggle, avatar menu and accordion groups for the Blade dashboard partials. --}}
<script>
(function() {
    const toggle = document.getElementById('sidebarToggle');
    const close  = document.getElementById('sidebarClose');
    const overlay = document.getElementById('sidebarOverlay');
    const sidebar = document.getElementById('bankSidebar');

    function openSidebar() {
        sidebar.classList.add('open');
        overlay.classList.add('active');
        document.body.style.overflow = 'hidden';
    }

    function closeSidebar() {
        sidebar.classList.remove('open');
        overlay.classList.remove('active');
        document.body.style.overflow = '';
    }

    if (toggle) toggle.addEventListener('click', openSidebar);
    if (close) close.addEventListener('click', closeSidebar);
    if (overlay) overlay.addEventListener('click', closeSidebar);

    // ── Avatar dropdown ──────────────────────────────
    const avatarToggle = document.getElementById('avatarToggle');
    const avatarMenu   = document.getElementById('avatarMenu');

    if (avatarToggle && avatarMenu) {
        avatarToggle.addEventListener('click', function (e) {
            e.stopPropagation();
            const open = avatarMenu.classList.toggle('open');
            avatarToggle.setAttribute('aria-expanded', open);
        });

        // Close when clicking outside
        document.addEventListener('click', function (e) {
            if (!avatarMenu.contains(e.target) && e.target !== avatarToggle) {
                avatarMenu.classList.remove('open');
                avatarToggle.setAttribute('aria-expanded', 'false');
            }
        });

        // Close on Escape key
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') {
                avatarMenu.classList.remove('open');
                avatarToggle.setAttribute('aria-expanded', 'false');
            }
        });
    }

    // ── Accordion nav groups ──────────────────────────────
    document.querySelectorAll('.bk-nav-group-toggle').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var group  = this.closest('.bk-nav-group');
            var isOpen = group.classList.contains('open');
            document.querySelectorAll('.bk-nav-group.open').forEach(function(g) {
                g.classList.remove('open');
            });
            if (!isOpen) group.classList.add('open');
        });
    });
})();
</script>
