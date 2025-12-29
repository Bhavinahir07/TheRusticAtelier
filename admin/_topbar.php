<?php
// Reusable Admin Topbar
// Usage in a page: set $__page_title and then include this file.
// Example: $__page_title = 'Manage Recipes'; include __DIR__ . '/_topbar.php';

$__display_title = isset($__page_title) && $__page_title !== '' ? $__page_title : 'Admin';
$__admin_name = htmlspecialchars($_SESSION['user']['username'] ?? 'Admin');
?>
<header class="admin-topbar">
  <div class="admin-topbar__inner">
    <div class="page-title"><?php echo htmlspecialchars($__display_title); ?></div>
    <div class="admin-identity" title="You are in the Admin area">
      <span class="admin-badge">ADMIN</span>
      <div class="admin-user"><i class="fa-solid fa-user-shield"></i><span><?php echo $__admin_name; ?></span></div>
    </div>
  </div>
</header>

<!-- Global flash styles and auto-hide behavior for admin pages -->
<style>
  .flash-card{transition: opacity .5s ease, transform .5s ease}
  .flash-hide{opacity:0; transform: translateY(-6px)}
</style>
<script>
  (function(){
    function hideFlashElements() {
      const nodes = document.querySelectorAll('[data-flash]');
      if (!nodes || nodes.length === 0) return false;
      
      const hideAfterMs = 2800;
      setTimeout(() => {
        nodes.forEach(n => n.classList && n.classList.add('flash-hide'));
        setTimeout(() => { nodes.forEach(n => { try { n.remove(); } catch(e){} }); }, 600);
      }, hideAfterMs);
      
      try {
        const url = new URL(window.location.href);
        if (url.searchParams.has('flash')) {
          url.searchParams.delete('flash');
          window.history.replaceState({}, document.title, url.toString());
        }
      } catch(e){}
      
      return true;
    }
    
    // Try immediately, then check periodically until elements are found
    if (!hideFlashElements()) {
      const checkInterval = setInterval(() => {
        if (hideFlashElements()) {
          clearInterval(checkInterval);
        }
      }, 100);
      
      // Stop checking after 5 seconds
      setTimeout(() => clearInterval(checkInterval), 5000);
    }
  })();
</script>
