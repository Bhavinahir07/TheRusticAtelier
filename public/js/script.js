/**
 * script.js
 * This file contains all the necessary JavaScript for the Recipe Sharing Website homepage.
 * It combines logic for filtering, searching, and all UI interactions like menus.
 */

function toggleMenu() {
    const navMenu = document.querySelector('nav ul');
    const menuOverlay = document.querySelector('.menu-overlay');
    const hamburger = document.querySelector('.hamburger');

    if (navMenu) navMenu.classList.toggle('active');
    if (menuOverlay) menuOverlay.classList.toggle('active');
    if (hamburger) hamburger.classList.toggle('active');
}


document.addEventListener('DOMContentLoaded', function () {

    // --- 1. STATE MANAGEMENT & DOM ELEMENTS ---
    let currentCategory = 'all'; // ✨ CHANGE: Default category is now 'all'
    let searchTimeout; // Used for search delay (debouncing)

    const searchBar = document.getElementById('search-bar');
    const noResultsMessage = document.getElementById('no-results');
    const categoryList = document.querySelector('.category-list');
    const foodCategories = document.querySelectorAll('.food-category');
    const navMenu = document.querySelector('nav ul');
    const menuOverlay = document.querySelector('.menu-overlay');
    const hamburger = document.querySelector('.hamburger');
    const menuClose = document.querySelector('.menu-close');


    // --- 2. CORE FILTERING AND SEARCH LOGIC ---

    /**
     * The master function that updates the view based on the current category and search query.
     */
    function updateView() {
        const searchQuery = searchBar.value.toLowerCase().trim();
        let anyItemsVisible = false;

        foodCategories.forEach(categoryContainer => {
            const categorySlug = categoryContainer.id;
            let itemsFoundInCategory = 0;

            // Determine if this category container should be visible based on the current filter
            const isCategoryVisible = (currentCategory === 'all' || categorySlug === currentCategory);

            if (isCategoryVisible) {
                categoryContainer.style.display = 'grid'; // Ensure container is visible
                const items = categoryContainer.querySelectorAll('.food-item');

                items.forEach(item => {
                    const title = item.querySelector('.p').textContent.toLowerCase();
                    const isMatch = title.includes(searchQuery);

                    if (isMatch) {
                        item.style.display = 'flex'; // Show matching item
                        itemsFoundInCategory++;
                    } else {
                        item.style.display = 'none'; // Hide non-matching item
                    }
                });

                if (itemsFoundInCategory > 0) {
                    anyItemsVisible = true;
                }
            } else {
                categoryContainer.style.display = 'none';
            }
        });

        const showNoResults = !anyItemsVisible && searchQuery !== '';
        noResultsMessage.style.display = showNoResults ? 'block' : 'none';
    }

    /**
     * Handles clicks on category list items.
     * @param {string} slug - The category slug (e.g., 'all', 'pizza').
     * @param {HTMLElement} element - The clicked <li> element.
     */
    function showCategory(slug, element) {
        currentCategory = slug;
        searchBar.value = ''; // Reset search bar on category change

        // Update active class for styling
        if (categoryList) {
            categoryList.querySelectorAll('li').forEach(li => li.classList.remove('active'));
        }
        element.classList.add('active');

        updateView();
    }


    // --- 3. UI AND MENU LOGIC ---

    /**
     * Toggles the dropdown for the sub-navigation on mobile.
     */
    function toggleDropdown() {
        const subNav = document.querySelector('.sub-nav');
        const hiddenItems = subNav.querySelectorAll('li.hidden');
        const toggleBtn = subNav.querySelector('.toggle-more');

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


    // --- 4. EVENT LISTENERS ---

    // Search bar input with debouncing for performance
    if (searchBar) {
        searchBar.addEventListener('input', () => {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(updateView, 250); // Wait 250ms after user stops typing
        });
    }

    // Attach click listeners to all category buttons
    if (categoryList) {
        categoryList.querySelectorAll('li[data-category]').forEach(li => {
            li.addEventListener('click', () => {
                showCategory(li.dataset.category, li);
            });
        });
    }

    if (menuOverlay) {
        menuOverlay.addEventListener('click', toggleMenu);
    }
    if (menuClose) {
        menuClose.addEventListener('click', toggleMenu);
    }

    document.addEventListener('click', function (event) {
        if (navMenu && navMenu.classList.contains('active')) {
            if (!navMenu.contains(event.target) && !hamburger.contains(event.target)) {
                toggleMenu();
            }
        }
    });

    if (hamburger) {
        hamburger.addEventListener('click', (event) => {
            event.stopPropagation();
        });
    }

    // --- 5. INITIALIZATION CODE (RUNS ON PAGE LOAD) ---

    // Profile capsule fade effect
    const capsule = document.getElementById("profileCapsule");
    const firstName = capsule ? capsule.querySelector(".first-name") : null;
    if (capsule && firstName && firstName.textContent.trim() !== '' && !capsule.classList.contains('collapsed')) {
        firstName.style.transition = 'opacity 0.45s ease';
        firstName.style.opacity = '1';

        setTimeout(() => {
            firstName.style.opacity = '0';
            setTimeout(() => {
                firstName.style.display = 'none';
                capsule.classList.add('collapsed');
            }, 500);
        }, 5000);
    }

    // Adjust sub-nav visibility on resize and load
    const subNavHandler = () => {
        const width = window.innerWidth;
        const hiddenItems = document.querySelectorAll('.sub-nav ul li.hidden');
        const toggleMore = document.querySelector('.toggle-more');

        if (width > 768) {
            hiddenItems.forEach(item => item.style.display = "block");
            if (toggleMore) toggleMore.style.display = "none";
        } else {
            if (!document.querySelector('.sub-nav').classList.contains('expanded')) {
                hiddenItems.forEach(item => item.style.display = "none");
                if (toggleMore) toggleMore.textContent = "+";
            }
            if (toggleMore) toggleMore.style.display = "inline-flex";
        }
    };

    window.addEventListener('resize', subNavHandler);
    subNavHandler(); // Run on initial load

});