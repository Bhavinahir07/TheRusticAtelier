<!-- Place this after the PHP logic above -->
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Share Your Recipe</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
  <!-- bootstrap link -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"
    integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
    integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz"
    crossorigin="anonymous"></script>
  <link rel="stylesheet" href="<?= BASE_URL ?>css/share_recipe.css">
</head>

<body>
  <header>
    <div class="hamburger" onclick="toggleMenu()" aria-label="Toggle navigation">
      <div></div>
      <div></div>
      <div></div>
    </div>
    <h1><i class="fas fa-utensils"></i> MyRecipes</h1>
    <nav>
      <ul>
        <li><a href="index.php">Home</a></li>
        <li><a href="product.php">Products</a></li>
        <li><a href="about_us.php">About Us</a></li>
        <li><a href="share_recipe.php">Share Recipe</a></li>
      </ul>
    </nav>

    <div class="auth-buttons">
      <?php if ($is_authenticated): ?>
        <div class="dropdown">
          <button class="btn btn-secondary dropdown-toggle d-flex align-items-center" type="button" id="profileDropdown"
            data-bs-toggle="dropdown" aria-expanded="false">
            <span class="me-2">
              <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor"
                class="bi bi-person-circle" viewBox="0 0 16 16">
                <path
                  d="M13.468 12.37C12.758 11.226 11.522 10.5 10 10.5s-2.757.726-3.468 1.87A6.987 6.987 0 0 1 2 8a7 7 0 1 1 14 0 6.987 6.987 0 0 1-2.532 4.37z" />
                <path fill-rule="evenodd" d="M8 9a3 3 0 1 0 0-6 3 3 0 0 0 0 6z" />
              </svg>
            </span>
            <span>
              <?= htmlspecialchars($user['username']) ?>
            </span>
          </button>
          <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="profileDropdown">
            <li class="px-3 py-2">
              <strong>
                <?= htmlspecialchars($user['username']) ?>
              </strong><br>
              <small class="text-muted">
                <?= htmlspecialchars($user['email']) ?>
              </small>
            </li>
            <li>
              <hr class="dropdown-divider">
            </li>
            <li><a class="dropdown-item" href="show_address.php">Address</a></li>
            <li><a class="dropdown-item" href="orders.php">My Orders</a></li>
            <li><a class="dropdown-item" href="logout.php">Logout</a></li>
          </ul>
        </div>
      <?php else: ?>
        <a href="login.php"><button class="btn">Login</button></a>
        <a href="signup.php"><button class="btn btn-signup">Sign Up</button></a>
      <?php endif; ?>
    </div>
  </header>
  <div class="recipe-form">
    <h2>Share Your Recipe</h2>
    <form class="f1" enctype="multipart/form-data" method="post">
      <div class="form-group">
        <label for="recipe-name">Recipe Name:</label>
        <input type="text" id="recipe-name" class="form-control" name="recipeName" required>
      </div>
      <div class="form-group">
        <label for="ingredients">Ingredients:</label>
        <textarea id="ingredients" name="recipeIngredients" class="form-control" rows="5" required></textarea>
      </div>
      <div class="form-group">
        <label for="cooking-time-mins">Cooking Time (minutes)</label>
        <input
          type="number"
          id="cooking-time-mins"
          name="recipeTimeMins"
          class="form-control"
          min="1"
          placeholder="e.g. 45"
          required>
      </div>
      <div class="form-group">
        <label for="instructions">Instructions:</label>
        <textarea id="instructions" class="form-control" name="recipeInstruction" rows="5" required></textarea>
      </div>
      <div class="form-group">
        <label for="recipe-photo">Upload Photo:</label>
        <input type="file" id="recipe-photo" name="recipeFile" class="form-control" accept="image/*" required>
        <span id="file-info">No file chosen</span>
      </div>
      <button type="submit" class="sub">Submit Recipe</button>
    </form>
  </div>

  <footer>
    <p>&copy; 2025 MyRecipe. All rights reserved.</p>
    <a href="index.php">Home</a>
    <a href="about_us.php">About Us</a>
    <a href="#">Our Products</a>
    <a href="#">Terms of Service</a>
    <a href="#">Privacy Policy</a>
    <div class="footer-social">
      <a href="https://facebook.com" target="_blank" class="social-icon facebook"><i class="fab fa-facebook-f"></i></a>
      <a href="https://twitter.com" target="_blank" class="social-icon twitter"><i class="fab fa-twitter"></i></a>
      <a href="https://instagram.com" target="_blank" class="social-icon instagram"><i class="fab fa-instagram"></i></a>
    </div>
  </footer>
  <script>
    function toggleMenu() {
      const nav = document.querySelector('nav ul');
      const overlay = document.querySelector('.menu-overlay');
      nav.classList.toggle('active');
      overlay.classList.toggle('active');
    }

    // Close the menu if clicked outside
    document.addEventListener('click', function(event) {
      const nav = document.querySelector('nav ul');
      const hamburger = document.querySelector('.hamburger'); // Update this if the class for your hamburger icon is different
      const overlay = document.querySelector('.menu-overlay');

      // If the click is outside the menu or hamburger, close the menu
      if (!nav.contains(event.target) && event.target !== hamburger && !hamburger.contains(event.target)) {
        nav.classList.remove('active');
        overlay.classList.remove('active');
      }
    });

    // Prevent closing the menu when clicking inside the menu or on the hamburger icon
    document.querySelector('.hamburger').addEventListener('click', function(event) {
      event.stopPropagation(); // Prevent the event from reaching the document listener
    });
    document.getElementById("recipe-photo").addEventListener("change", function() {
      const fileInfo = document.getElementById("file-info");
      if (this.files.length > 0) {
        fileInfo.textContent = `Selected: ${this.files[0].name} (${this.files[0].type})`;
      } else {
        fileInfo.textContent = "No file chosen";
      }
    });
  </script>
</body>

</html>