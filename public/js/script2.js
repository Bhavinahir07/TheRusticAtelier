// ---------------------
// Category Switching
// ---------------------
function showCategory(category) {
    // Hide all
    const categories = document.querySelectorAll('.food-category');
    categories.forEach(c => c.style.display = 'none');

    // Show selected
    const selected = document.getElementById(category);
    if (selected) {
        selected.style.display = 'grid';
    }
}

// ---------------------
// Search (with delay + no results message)
// ---------------------
let searchTimeout; // delay variable

function searchItems() {
    const searchQuery = document.getElementById('search-bar').value.trim().toLowerCase();
    const categories = document.querySelectorAll('.food-category');
    const noResultMessage = document.getElementById('no-results');
    let hasMatch = false;

    categories.forEach(category => {
        const items = category.querySelectorAll('.food-item');
        let categoryHasResults = false;

        items.forEach(item => {
            const nameTag = item.querySelector('p.p');
            const itemName = nameTag ? nameTag.textContent.toLowerCase() : '';

            if (itemName.includes(searchQuery)) {
                item.style.display = 'block';
                categoryHasResults = true;
                hasMatch = true;
            } else {
                item.style.display = 'none';
            }
        });

        category.style.display = categoryHasResults ? 'grid' : 'none';
    });

    // Show or hide the error message
    if (noResultMessage) {
        noResultMessage.style.display = (!hasMatch && searchQuery !== '') ? 'block' : 'none';
    }
}

// Attach search input listener
document.addEventListener('DOMContentLoaded', function () {
    const searchInput = document.getElementById('search-bar');
    if (searchInput) {
        searchInput.addEventListener('input', function () {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(searchItems, 200); // debounce
        });
    }
});

// ---------------------
// Mobile Menu (hamburger + overlay + close)
// ---------------------
function toggleMenu() {
    const nav = document.querySelector('nav ul');
    const overlay = document.querySelector('.menu-overlay');
    const menuClose = document.querySelector('.menu-close');

    if (nav) nav.classList.toggle('active');
    if (overlay) overlay.classList.toggle('active');

    if (menuClose) {
        menuClose.style.display = nav.classList.contains('active') ? 'block' : 'none';
    }
}

// Close menu if clicked outside
document.addEventListener('click', function (event) {
    const nav = document.querySelector('nav ul');
    const hamburger = document.querySelector('.hamburger');
    const overlay = document.querySelector('.menu-overlay');
    const menuClose = document.querySelector('.menu-close');

    if (nav && !nav.contains(event.target) && event.target !== hamburger && !hamburger.contains(event.target)) {
        nav.classList.remove('active');
        if (overlay) overlay.classList.remove('active');
        if (menuClose) menuClose.style.display = 'none';
    }
});

// Prevent closing when clicking hamburger itself
document.querySelector('.hamburger')?.addEventListener('click', function (event) {
    event.stopPropagation();
});

// Close button + overlay click
document.addEventListener('DOMContentLoaded', function () {
    const closeEl = document.querySelector('.menu-close');
    const overlay = document.querySelector('.menu-overlay');
    const nav = document.querySelector('nav ul');

    if (closeEl) {
        closeEl.addEventListener('click', function (e) {
            e.stopPropagation();
            if (nav) nav.classList.remove('active');
            if (overlay) overlay.classList.remove('active');
            closeEl.style.display = 'none';
        });
    }

    if (overlay) {
        overlay.addEventListener('click', function () {
            if (nav) nav.classList.remove('active');
            overlay.classList.remove('active');
            if (closeEl) closeEl.style.display = 'none';
        });
    }
});

// ---------------------
// Dropdown (sub-nav hidden items)
// ---------------------
function toggleDropdown() {
    const subNav = document.querySelector('.sub-nav');
    const hiddenItems = subNav?.querySelectorAll('li.hidden');
    const toggleBtn = subNav?.querySelector('.toggle-more');

    if (!subNav || !toggleBtn) return;

    subNav.classList.toggle('expanded');

    if (subNav.classList.contains('expanded')) {
        hiddenItems.forEach(item => item.style.display = "block");
        toggleBtn.textContent = "–";
    } else {
        hiddenItems.forEach(item => item.style.display = "none");
        toggleBtn.textContent = "+";
    }
}

// Adjust submenu on resize
window.addEventListener('resize', () => {
    const width = window.innerWidth;
    const hiddenItems = document.querySelectorAll('.sub-nav ul li.hidden');
    const toggleMore = document.querySelector('.toggle-more');

    if (!toggleMore) return;

    if (width > 768) {
        hiddenItems.forEach(item => item.style.display = "block");
        toggleMore.style.display = "none";
    } else {
        if (!document.querySelector('.sub-nav')?.classList.contains('expanded')) {
            hiddenItems.forEach(item => item.style.display = "none");
            toggleMore.textContent = "+";
        }
        toggleMore.style.display = "inline-flex";
    }
});

// Initialize submenu on load
document.addEventListener('DOMContentLoaded', () => {
    const width = window.innerWidth;
    const hiddenItems = document.querySelectorAll('.sub-nav ul li.hidden');
    const toggleMore = document.querySelector('.toggle-more');

    if (!toggleMore) return;

    if (width > 768) {
        hiddenItems.forEach(item => item.style.display = "block");
        toggleMore.style.display = "none";
    } else {
        hiddenItems.forEach(item => item.style.display = "none");
        toggleMore.style.display = "inline-flex";
    }
});

// ---------------------
// Profile Capsule (fade out first name)
// ---------------------
document.addEventListener("DOMContentLoaded", function () {
    const capsule = document.getElementById("profileCapsule");
    const firstName = capsule ? capsule.querySelector(".first-name") : null;

    // Make capsule keyboard operable
    if (capsule) {
        capsule.setAttribute('tabindex', '0');
        capsule.addEventListener('keydown', function (e) {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                capsule.click(); // Bootstrap dropdown toggle
            }
        });
    }

    // Fade out first name
    if (firstName && firstName.textContent.trim() !== '') {
        firstName.style.transition = 'opacity 0.45s ease';
        firstName.style.opacity = '1';

        setTimeout(() => {
            firstName.style.opacity = '0';
            setTimeout(() => {
                firstName.style.display = 'none';
                if (capsule) {
                    capsule.classList.add('collapsed');
                }
            }, 500);
        }, 5000);
    }
});

// ---------------------
// Default category
// ---------------------
document.addEventListener('DOMContentLoaded', function () {
    showCategory('pizza');
});
