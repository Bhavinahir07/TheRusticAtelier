<header>
    <div class="hamburger" onclick="toggleMenu()" aria-label="Toggle navigation">
      <div></div>
      <div></div>
      <div></div>
    </div>
    <h1 class="site-name"><i class="fas fa-utensils"></i> TheRusticAtelier</h1>
    <nav>
      <ul>
        <!-- Close button -->
        <!-- <li class="close-btn">&times;</li> -->
        <li><a href="index.php">Home</a></li>
        <li><a href="product.php">Products</a></li>
        <li><a href="about_us2.php">About Us</a></li>
        <li><a href="share_recipe.php">Share Recipe</a></li>
        <!-- Login and signup button -->
        <li class="hideLogin"><a href="login.php">Login</a></li>
        <li class="mobile-only"><a href="signup.php">Signup</a></li>
      </ul>
    </nav>
    <!-- FIXED close button for mobile menu (top-left). Shown only in mobile via CSS -->
    <div class="menu-close" aria-hidden="true">&times;</div>

    <div class="auth-buttons">
      <?php if ($is_authenticated): ?>
        <div class="dropdown">
          <!-- capsule IS the dropdown toggle now -->
          <div class="profile-capsule dropdown-toggle"
            id="profileCapsule"
            role="button"
            data-bs-toggle="dropdown"
            aria-expanded="false"
            aria-haspopup="true"
            tabindex="0">
            <img src="<?= htmlspecialchars($profile_image) ?>" alt="Profile" class="profile-avatar">
            <span class="first-name"><?= htmlspecialchars($first_name) ?></span>
          </div>

          <!-- menu linked to the capsule by aria-labelledby -->
          <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="profileCapsule">
            <li class="px-3 py-2 d-flex align-items-center gap-2">
              <img src="<?= $profile_image ?>" alt="Profile" class="profile-avatar">
              <div>
                <strong><?= htmlspecialchars($user['username']) ?></strong><br>
                <small class="text-muted"><?= htmlspecialchars($user['email']) ?></small>
              </div>
            </li>
            <li>
              <hr class="dropdown-divider">
            </li>
            <li><a class="dropdown-item" href="user_profile.php">Your Profile</a></li>
            <li><a class="dropdown-item" href="show_address.php">Address</a></li>
            <li><a class="dropdown-item" href="orders.php">My Orders</a></li>
            <li><a class="dropdown-item" href="logout.php">Logout</a></li>
          </ul>
        </div>
      <?php else: ?>
        <a href="login.php"><button class="btn">Login</button></a>
        <a href="signup.php"><button class="btn">Sign Up</button></a>
      <?php endif; ?>
    </div>
  </header>