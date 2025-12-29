(function () {
  const sidebar = document.getElementById('adminSidebar');
  const hamburger = document.getElementById('hamburgerBtn');
  const nav = document.getElementById('adminNav');
  const pageTitle = document.getElementById('pageTitle');
  const newBtn = document.getElementById('newActionBtn');

  function setActive(target) {
    if (!nav) return;
    [...nav.querySelectorAll('a')].forEach(a => {
      const isActive = a.dataset.target === target;
      a.classList.toggle('active', isActive);
    });
    const mapping = {
      recipes: { title: 'Manage Recipes', newLabel: 'New Recipe' },
      users: { title: 'View Users', newLabel: 'Invite User' },
      stats: { title: 'Site Stats', newLabel: 'Export' },
      products: { title: 'Manage Products', newLabel: 'New Product' },
      shared: { title: 'User Shared Recipes', newLabel: 'Review' },
    };
    const conf = mapping[target] || mapping.recipes;
    if (pageTitle) pageTitle.textContent = conf.title;
    if (newBtn) newBtn.innerHTML = `<i class="fa-solid fa-plus"></i>&nbsp;${conf.newLabel}`;
  }

  function wireNav() {
    if (!nav) return;
    nav.addEventListener('click', (e) => {
      const a = e.target.closest('a');
      if (!a) return;
      const href = a.getAttribute('href') || '';
      const t = a.dataset.target;
      // If link points to a real page, allow navigation
      if (href && href !== '#' && !t) return;
      // Otherwise, handle as in-page tab switch
      e.preventDefault();
      if (t) setActive(t);
      // You can load content via AJAX here based on t
    });

    // Cards CTA
    document.querySelectorAll('[data-card] .cta').forEach(cta => {
      cta.addEventListener('click', (e) => {
        const href = cta.getAttribute('href') || '';
        const t = cta.getAttribute('data-target');
        // If this card links to a real page, allow navigation
        if (href && href !== '#' && !t) return;
        e.preventDefault();
        if (t) setActive(t);
      });
    });
  }

  function wireHamburger() {
    if (!hamburger || !sidebar) return;
    hamburger.addEventListener('click', () => {
      sidebar.classList.toggle('open');
    });
  }

  document.addEventListener('DOMContentLoaded', () => {
    // Do not force a default tab; preserve real page title
    wireNav();
    wireHamburger();
  });
})();
