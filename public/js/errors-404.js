(function(){
  // Simple interactivity for 404 page
  const player = document.getElementById('lottie404');
  const searchForm = document.getElementById('errorsSearch');
  const searchInput = document.getElementById('searchInput');

  // Hover interactions: speed up on hover, slow on leave
  if (player) {
    player.addEventListener('mouseenter', () => {
      player.setPlayerSpeed(1.2);
    });
    player.addEventListener('mouseleave', () => {
      player.setPlayerSpeed(1.0);
    });
  }

  // Search behavior: redirect to home with query param (handled by landing page)
  if (searchForm) {
    searchForm.addEventListener('submit', function(e){
      e.preventDefault();
      const q = (searchInput.value || '').trim();
      const base = window.location.origin + '<?= base_url('/') ?>'.replace(/\/$/, '');
      if (!q) {
        window.location.href = base + '/';
        return;
      }
      // Redirect to landing page with query param 'q' (implementation on landing page expected)
      window.location.href = base + '/?q=' + encodeURIComponent(q);
    });
  }
})();
