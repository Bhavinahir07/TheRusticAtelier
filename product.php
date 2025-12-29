<?php
// product.php
require_once __DIR__ . "/config/init.php";

// User ID will be null if guest
$user_id = $_SESSION['user']['id'] ?? null;

// --- Get all products ---
$products = [];
$stmt = $conn->query("SELECT id, name, price, image, category FROM products");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
  $products[] = [
    'id' => $row['id'],
    'name' => $row['name'],
    'price' => $row['price'],
    'img' => $row['image'],
    'category' => $row['category']
  ];
}
$products_json = json_encode($products);
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Our Products</title>

  <!-- Your Original CSS Links (Restored) -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" />
  <link rel="stylesheet" href="<?= BASE_URL ?>css/style.css">
  <link rel="stylesheet" href="<?= BASE_URL ?>css/site-name.css">
  <link rel="stylesheet" href="<?= BASE_URL ?>css/footer.css">
  <!-- product.css is removed as requested -->

  <!-- Font styles (kept from your original file) -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link
    href="https://fonts.googleapis.com/css2?family=Alex+Brush&family=Allura&family=Cookie&family=Dancing+Script:wght@400..700&family=Great+Vibes&family=Italianno&family=Parisienne&family=Rouge+Script&family=Sacramento&family=Satisfy&display=swap"
    rel="stylesheet">

  <!-- Page Styles -->
  <style>
    /* Base */
    body {
      color: #1f2937;
      font-family: 'Inter', sans-serif;
    }

    /* Search Bar */
    .search-bar {
      width: 100%;
      max-width: 720px;
      margin: 0 auto;
      padding: 0.9rem 1.5rem;
      border: 2px solid #e5e7eb;
      border-radius: 50px;
      background: #fff;
      font-size: 1rem;
      transition: all 0.3s ease;
    }

    .search-bar:focus {
      border-color: #f97316;
      box-shadow: 0 0 0 4px rgba(249, 115, 22, 0.2);
      outline: none;
    }

    /* Product Grid */
    .products {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
      gap: 1.5rem;
    }

    /* Product Card */
    .product-card {
      background: #fff;
      border-radius: 12px;
      border: 1px solid #e5e7eb;
      box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05), 0 1px 2px rgba(0, 0, 0, 0.06);
      overflow: hidden;
      transition: transform 0.25s ease, box-shadow 0.25s ease;
      display: flex;
      flex-direction: column;
      cursor: pointer;
    }

    .product-card:hover {
      transform: translateY(-5px);
      box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
    }

    /* Full-bleed cover image */
    .product-card-image-container {
      height: 240px;
      /* background: #f3f4f6; */
      background: #ffffff;
      /* Changed from #f3f4f6 to white for a cleaner look */
      display: flex;
      align-items: center;
      justify-content: center;
      overflow: hidden;
    }

    .product-card img {
      width: 100%;
      height: 100%;
      object-fit: contain;
      /* <--- This ensures the whole bottle is visible */
      padding: 10px;
      /* Adds a little breathing room */
      transition: transform 0.3s ease;
      display: block;
      mix-blend-mode: multiply;
      /* Optional: Helps image blend if background is grey */
    }

    .product-card:hover img {
      transform: scale(1.05);
    }

    /* Highlight focus state for Buy Again deep-link */
    .product-card.highlight {
      box-shadow: 0 0 0 3px #f97316 inset, 0 0 0 3px rgba(249, 115, 22, 0.6);
      animation: highlight-pulse 1.2s ease-in-out 2;
    }

    @keyframes highlight-pulse {
      0% {
        transform: translateY(0);
      }

      50% {
        transform: translateY(-2px);
      }

      100% {
        transform: translateY(0);
      }
    }

    /* Product Details */
    .product-details {
      padding: 1rem 1.25rem 1.5rem;
      display: flex;
      flex-direction: column;
      flex-grow: 1;
    }

    .product-details h3 {
      font-size: 1.1rem;
      font-weight: 600;
      color: #111827;
      margin-bottom: 0.5rem;
    }

    .product-details .category {
      font-size: 0.75rem;
      font-weight: 500;
      padding: 0.25rem 0.5rem;
      border-radius: 9999px;
      display: inline-block;
      margin-bottom: 0.75rem;
      width: fit-content;
    }

    .product-details .category.veg {
      background-color: #dcfce7;
      color: #166534;
    }

    .product-details .category.non-veg {
      background-color: #fee2e2;
      color: #991b1b;
    }

    .product-details .price {
      font-size: 1.4rem;
      font-weight: 700;
      color: #111827;
      margin-bottom: 1rem;
    }

    /* Add to Cart Button */
    .product-details .cart-btn {
      width: 100%;
      background: #000;
      /* black */
      color: #fff;
      font-weight: 600;
      padding: 0.75rem 1rem;
      border-radius: 6px;
      border: none;
      transition: background-color 0.2s ease, box-shadow 0.2s ease;
      margin-top: auto;
      font-size: 1rem;
      letter-spacing: 0.5px;
    }

    .product-details .cart-btn:hover {
      background: #222;
      /* darker black hover */
    }

    /* Cart Pill (fixed, no count) */
    .cart-pill {
      position: fixed;
      right: 24px;
      bottom: 24px;
      display: inline-flex;
      align-items: center;
      gap: 10px;
      height: 54px;
      padding: 0 18px 0 16px;
      border-radius: 9999px;
      background: #000;
      /* black */
      color: #fff;
      text-decoration: none;
      z-index: 1000;
      transition: transform .15s ease, box-shadow .2s ease, background-color .2s ease;
      box-shadow: 0 10px 22px rgba(0, 0, 0, 0.35);
    }

    .cart-pill:hover {
      transform: translateY(-2px);
      background: #333;
      /* hover to #333 */
      box-shadow: 0 14px 28px #333;
      color: #fff;
    }

    .cart-pill .fa-shopping-cart {
      font-size: 1.15rem;
    }

    .cart-text {
      font-weight: 700;
      letter-spacing: .2px;
    }

    .cart-pill.bump {
      animation: cart-bump .5s ease;
    }

    @keyframes cart-bump {
      0% {
        transform: scale(1);
      }

      25% {
        transform: scale(1.06);
      }

      50% {
        transform: scale(0.98);
      }

      100% {
        transform: scale(1);
      }
    }

    /* Toast (simple, neutral) */
    #toast-container {
      position: fixed;
      bottom: 2rem;
      right: 2rem;
      z-index: 9999;
      display: flex;
      flex-direction: column;
      gap: 0.5rem;
    }

    .toast {
      display: flex;
      align-items: center;
      gap: 8px;
      background: #111827;
      /* dark neutral */
      color: #fff;
      border-radius: 8px;
      padding: 10px 12px;
      font-size: 14px;
      opacity: 0;
      transform: translateY(10px);
      transition: opacity .25s ease, transform .25s ease;
    }

    .toast.show {
      opacity: 1;
      transform: translateY(0);
    }
  </style>
</head>

<body>
  <div class="menu-overlay"></div>

  <?php
  $activePage = "products";
  $showCapsuleEffect = false;
  include __DIR__ . "/partials/header.php";
  ?>

  <main class="container my-5">
    <div class="search-wrapper mb-4 text-center">
      <input type="search" id="search" class="search-bar" placeholder="Search our products..."
        oninput="filterProducts()" />
    </div>

    <!-- Cart Pill (no count) -->
    <a href="cart.php" class="cart-pill" id="floating-cart">
      <i class="fas fa-shopping-cart"></i>
      <span class="cart-text d-none d-sm-inline">Cart</span>
    </a>

    <!-- Product Grid -->
    <section class="products" id="product-list"></section>

    <div id="no-results-container" style="display: none;" class="text-center mt-5">
      <p class="text-muted fs-5">No matching products found.</p>
    </div>
  </main>

  <div id="toast-container"></div>

  <script>
    const products = <?php echo $products_json; ?>;
    const productList = document.getElementById("product-list");
    const noResultsContainer = document.getElementById("no-results-container");
    const searchEl = document.getElementById("search");

    // Normalize category values from DB
    function normalizeCategory(value) {
      const v = (value || '').toString().trim().toLowerCase();
      if (v.startsWith('veg')) return 'vegetarian';
      if (v.startsWith('non')) return 'non-vegetarian';
      if (v.includes('non') && v.includes('veg')) return 'non-vegetarian';
      if (v.replace(/[\s-]/g, '') === 'nonvegetarian') return 'non-vegetarian';
      return 'vegetarian';
    }

    function createProductCard(product) {
      const cat = normalizeCategory(product.category);
      const categoryClass = cat === 'vegetarian' ? 'veg' : 'non-veg';
      const categoryText = cat === 'vegetarian' ? 'Vegetarian' : 'Non-Vegetarian';

      return `
                <div class="product-card" id="product-${product.id}">
                    <div class="product-card-image-container">
                        <img src="${product.img || 'https://placehold.co/400x300/e2e8f0/718096?text=No+Image'}" alt="${product.name}" />
                    </div>
                    <div class="product-details">
                        <h3>${product.name}</h3>
                        <p class="category ${categoryClass}">${categoryText}</p>
                        <p class="price">₹${product.price}</p>
                        <button class="cart-btn" onclick="addToCart(${product.id})">
                            Add to Cart
                        </button>
                    </div>
                </div>
            `;
    }

    function renderProducts(list) {
      if (!list || list.length === 0) {
        productList.innerHTML = '';
        noResultsContainer.style.display = 'block';
        return;
      }
      noResultsContainer.style.display = 'none';
      productList.innerHTML = list.map(createProductCard).join('');
    }

    function filterProducts() {
      const q = (searchEl?.value || '').toLowerCase();
      const filtered = products.filter(p => p.name.toLowerCase().includes(q));
      renderProducts(filtered);
    }

    // Simple neutral toast
    function showToast(message) {
      const container = document.getElementById('toast-container');
      const toast = document.createElement('div');
      toast.className = 'toast';
      toast.textContent = message;
      container.appendChild(toast);
      requestAnimationFrame(() => toast.classList.add('show'));
      setTimeout(() => {
        toast.classList.remove('show');
        setTimeout(() => toast.remove(), 250);
      }, 1500);
    }

    // Small cart bump animation
    function bumpCartPill() {
      const cartPill = document.getElementById('floating-cart');
      if (cartPill) {
        cartPill.classList.remove('bump');
        void cartPill.offsetWidth;
        cartPill.classList.add('bump');
      }
    }

    // Add to cart (no count updates)
    async function addToCart(productId) {
      try {
        const resp = await fetch("add_to_cart.php", {
          method: "POST",
          headers: {
            "Content-Type": "application/json",
            "Cache-Control": "no-cache"
          },
          body: JSON.stringify({
            productId,
            quantity: 1
          })
        });


        let data = {};
        try {
          data = await resp.json();
        } catch (_) {
          data = {};
        }

        if (!data.success && data.redirect) {
          window.location.href = data.redirect;
          return;
        }

        if (data.success) {
          showToast("Added to cart");
          bumpCartPill();
        } else {
          showToast(data.message || "Could not add item");
        }
      } catch (e) {
        showToast("An error occurred");
      }
    }

    function toggleMenu() {
      document.querySelector('nav ul').classList.toggle('active');
      document.querySelector('.menu-overlay').classList.toggle('active');
    }

    document.addEventListener('DOMContentLoaded', () => {
      // Let the search "x" also refresh
      if (searchEl) searchEl.addEventListener('search', filterProducts);
      renderProducts(products);

      // If arrived with a specific product id, scroll to and highlight it
      try {
        const params = new URLSearchParams(window.location.search);
        const focusId = params.get('id');
        if (focusId) {
          const target = document.getElementById(`product-${focusId}`);
          if (target) {
            target.scrollIntoView({
              behavior: 'smooth',
              block: 'center'
            });
            target.classList.add('highlight');
            setTimeout(() => target.classList.remove('highlight'), 2500);
          }
        }
      } catch (e) {
        /* no-op */
      }
    });
  </script>
  <footer>
    <p>&copy; 2025 MyRecipe. All rights reserved.</p>
    <a href="index.php">Home Page</a>
    <a href="about_us.php">About Us</a>
    <a href="#">Our Products</a>
    <a href="#">Terms of Service</a>
    <a href="#">Privacy Policy</a>
    <div class="footer-social">
      <a href="https://facebook.com" target="_blank" class="social-icon facebook"><i
          class="fab fa-facebook-f"></i></a>
      <a href="https://twitter.com" target="_blank" class="social-icon twitter"><i class="fab fa-twitter"></i></a>
      <a href="https://instagram.com" target="_blank" class="social-icon instagram"><i
          class="fab fa-instagram"></i></a>
    </div>
  </footer>
</body>

</html>