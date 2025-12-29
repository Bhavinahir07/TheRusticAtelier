<?php
// details.php
// require 'db.php';
// session_start();
require_once __DIR__ . "/config/init.php";

/**
 * MODIFIED HELPER FUNCTION
 * Helper function to determine indentation level based on preceding tabs OR spaces.
 * Assumes 1 tab = 4 spaces.
 * @param string $line The line of text to check.
 * @return int The indentation level (0, 1, 2, or 3).
 */
function get_indent_level($line) {
  // We MUST check from most specific (most indented) to least.
  
  // Level 3: Check for 3 tabs OR 12+ spaces
  if (preg_match('/^(\t{3}| {12,})/', $line)) return 3;
  
  // Level 2: Check for 2 tabs OR 8+ spaces
  if (preg_match('/^(\t{2}| {8,})/', $line)) return 2;
  
  // Level 1: Check for 1 tab OR 4+ spaces
  if (preg_match('/^(\t| {4,})/', $line)) return 1;
  
  // Level 0: No indent (or < 4 spaces)
  return 0;
}


$category_slug = $_GET['category'] ?? null;
$recipe_slug = $_GET['slug'] ?? null;
// $category_slug = $_GET['category'] ?? null;
// $recipe_slug = $_GET['recipe'] ?? null; // ✅ match index.php link


if (!$category_slug || !$recipe_slug) {
  echo "Invalid recipe URL";
  exit;
}

// Fetch the recipe
$stmt = $conn->prepare("SELECT * FROM recipes WHERE slug = ? AND category_id = (SELECT id FROM categories WHERE slug = ? LIMIT 1)");
$stmt->execute([$recipe_slug, $category_slug]);
$recipe = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$recipe) {
  echo "Recipe not found";
  exit;
}

// --- START: Parse Cooking Time ---
$time_components = [];
if (!empty($recipe['cooking_time'])) {
  // This regular expression splits the string by one or more newline characters
  // OR by two or more whitespace characters. This handles both data entry formats.
  $time_parts = preg_split('/(\r\n|\r|\n|\s{2,})/', trim($recipe['cooking_time']));

  foreach ($time_parts as $part) {
    // Trim each part to remove any extra spaces from the split.
    $trimmed_part = trim($part);

    // Skip any empty parts that might result from the split.
    if (empty($trimmed_part)) {
      continue;
    }

    // Split the part into a label and value at the first colon.
    $details = explode(':', $trimmed_part, 2);

    if (count($details) === 2) {
      $label = trim($details[0]);
      $value = trim($details[1]);

      // Ensure we have a valid label and value before storing it.
      if (!empty($label) && !empty($value)) {
        $time_components[$label] = $value;
      }
    }
  }
}
// --- END: Parse Cooking Time ---

// --- START: Navbar profile management ---
$user = $_SESSION['user'] ?? null;
$is_authenticated = isset($user) && !empty($user['id']);

$first_name = '';
$profile_image = "images/user_profile/download.png"; // default fallback

if ($is_authenticated) {
  // Prefer session first_name if present
  if (!empty($user['first_name'])) {
    $first_name = htmlspecialchars($user['first_name'], ENT_QUOTES, 'UTF-8');
  }

  // Prefer session profile image if present
  if (!empty($user['profile_image'])) {
    $profile_image = htmlspecialchars($user['profile_image'], ENT_QUOTES, 'UTF-8');
  }

  // If first_name or profile_image are still missing, fetch from DB
  if ($first_name === '' || $profile_image === "images/user_profile/download.png") {
    $stmt = $conn->prepare("SELECT first_name, profile_image FROM users WHERE id = ?");
    $stmt->execute([$user['id']]);
    if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
      if (!empty($row['first_name'])) {
        $first_name = htmlspecialchars($row['first_name'], ENT_QUOTES, 'UTF-8');
        $_SESSION['user']['first_name'] = $row['first_name'];
      }
      if (!empty($row['profile_image'])) {
        $profile_image = htmlspecialchars($row['profile_image'], ENT_QUOTES, 'UTF-8');
        $_SESSION['user']['profile_image'] = $row['profile_image'];
      }
    }
  }


  // Final fallback: use username's first token if still empty
  if ($first_name === '' && !empty($user['username'])) {
    $parts = preg_split('/\s+/', trim($user['username']));
    $first_name = htmlspecialchars($parts[0] ?? $user['username'], ENT_QUOTES, 'UTF-8');
  }
}
// --- END: Navbar profile management ---

?>


<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($recipe['title']) ?></title>

  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"
    integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">


  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Alex+Brush&family=Allura&family=Cookie&family=Dancing+Script:wght@400..700&family=Great+Vibes&family=Italianno&family=Parisienne&family=Rouge+Script&family=Sacramento&family=Satisfy&display=swap" rel="stylesheet">

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
    integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz"
    crossorigin="anonymous"></script>

  <link rel="stylesheet" href="<?= BASE_URL ?>css/style.css">
  <link rel="stylesheet" href="<?= BASE_URL ?>css/site-name.css">
  <link rel="stylesheet" href="<?= BASE_URL ?>css/footer.css">

  <style>
    /* General Page Styles */
    body {
      font-family: 'Segoe UI', sans-serif;
      margin: 0;
      padding: 0;
    }
    /* Match my_recipe_view.php header spacing */
    header .site-name { margin: 0; padding: 0; text-align: left; }
    header .site-name i { display: inline-block; transform: translateY(-10%); }
    header .header-row { padding-top: 0.46rem; padding-bottom: 0.46rem; }
    header { box-shadow: none !important; }

    .container {
      max-width: 900px;
      margin: 50px auto;
      padding: 30px;
      background: #fff;
      box-shadow: 0 0 15px rgba(0, 0, 0, 0.1);
      border-radius: 16px;
    }

    .heading h1 {
      font-size: 36px;
      margin: 10px 0 30px 0;
    }

    .tagline {
      font-size: 18px;
      font-style: italic;
      color: #444;
      margin-bottom: 30px;
    }

    .Image {
      text-align: center;
      margin-bottom: 30px;
    }

    .Image img {
      max-width: 100%;
      height: auto;
      max-height: 400px;
      border-radius: 12px;
      box-shadow: 0 6px 20px rgba(0, 0, 0, 0.15);
      background-color: #f8f8f8;
      /* padding: 4px; */
    }

    .summary {
      display: flex;
      flex-wrap: wrap;
      gap: 15px;
      font-size: 16px;
      margin-bottom: 20px;
    }

    .summary div {
      /* background: #f5f5f5; */
      /* background: linear-gradient(135deg, #fdf7ea, #fef6df, #f6eade); */
      background: linear-gradient(135deg, #f9f0dbff, #faeec8ff, #fce1c6ff);
      padding: 10px 16px;
      border-radius: 8px;
    }

    .buttons {
      margin: 30px 0;
      text-align: center;
    }

    .buttons button {
      background: #000;
      color: #fff;
      border: none;
      padding: 12px 20px;
      margin: 5px;
      border-radius: 8px;
      font-weight: bold;
      font-size: 1rem;
      cursor: pointer;
      transition: background 0.3s ease;
    }

    .buttons button:hover {
      background: #333;
    }

    h2 {
      margin-top: 40px;
      margin-bottom: 16px;
      font-size: 24px;
    }

    /* === START: ISOLATED RECIPE STYLES === */
    /* All recipe list styles are now prefixed with #recipe-content */

    #recipe-content ul,
    #recipe-content ol {
      font-size: 17px;
      line-height: 1.8;
      padding-left: 25px;
    }

    #recipe-content ul li,
    #recipe-content ol li {
      margin-bottom: 12px;
    }

    /* --- Styles for Ingredients List (ul) --- */
    #recipe-content ul li.list-heading {
      list-style-type: none;
      margin-left: -20px;
      /* Counteract padding */
    }

    #recipe-content ul li.list-heading strong {
      font-size: 18px;
      /* font-weight: 600; */
      color: #000;
      display: block;
      margin-top: 15px;
    }

    /* MODIFICATION: Replaced .subingredient with new hierarchy */
    #recipe-content ul li.list-main {
      margin-left: 0px;
      list-style-type: disc;
      font-size: 16px;
    }

    #recipe-content ul li.list-sub {
      margin-left: 20px; /* Indent level 2 */
      list-style-type: circle;
      font-size: 16px;
      color: #444;
    }

    #recipe-content ul li.list-sub-sub {
      margin-left: 40px; /* Indent level 3 */
      list-style-type: square;
      font-size: 16px;
      color: #555;
    }


    /* To change the color of list bullets */
    #recipe-content ul li.list-main::marker { /* MODIFICATION: Changed from .subingredient */
      color: orange;
    }
    
    /* Added markers for sub-lists */
    #recipe-content ul li.list-sub::marker {
      color: orange;
    }
    #recipe-content ul li.list-sub-sub::marker {
      color: orange;
    }


    /* --- Styles for Instructions List (ol) --- */
    #recipe-content ol {
      list-style: none;
      /* Keeps manual numbering */
    }

    /* Rule for the instruction heading's LAYOUT */
    #recipe-content ol li.instruction-heading {
      margin-left: -25px;
      /* Removes indentation */
      margin-bottom: 15px;
    }

    /* Rule for the instruction heading's TEXT */
    #recipe-content ol li.instruction-heading strong {
      font-weight: bold;
      display: block;
      font-size: 18px;
      color: #000;
    }

    /* MODIFICATION: Replaced .substep with new hierarchy */
    #recipe-content ol li.list-main {
      margin-left: 0px;
      list-style-type: none; /* Main content for instructions has no bullet */
      font-size: 16px;
    }

    #recipe-content ol li.list-sub {
      margin-left: 20px; /* Indent level 2 */
      list-style-type: disc;
      font-size: 16px;
      color: #444;
    }

    #recipe-content ol li.list-sub-sub {
      margin-left: 40px; /* Indent level 3 */
      list-style-type: circle;
      font-size: 16px;
      color: #555;
    }

    /* === END: ISOLATED RECIPE STYLES === */
  </style>
</head>

<body>

  <div class="menu-overlay"></div>
  <header class="bg-white border-bottom" style="border-color:#e5e7eb;">
    <div class="container-fluid px-0">
      <div class="d-flex justify-content-between align-items-center header-row">
        <h1 class="site-name d-flex align-items-center gap-2 m-0">
          <i class="fas fa-utensils"></i>
          TheRusticAtelier
        </h1>
        <span class="home-link">
          <a href="index.php" class="text-decoration-none fw-medium d-inline-flex align-items-center" style="color:#4b5563;">
            <i class="fas fa-compass me-2"></i> Explore Recipes
          </a>
        </span>
      </div>
    </div>
  </header>

  <div class="container" id="recipe-content">
    <div class="heading">
      <h1><?= htmlspecialchars($recipe['title']) ?></h1>
    </div>
    <div class="tagline">
      <p><?= nl2br(htmlspecialchars($recipe['intro'])) ?></p>
    </div>

    <div class="Image">
      <img src="<?= htmlspecialchars($recipe['image']) ?>" alt="<?= htmlspecialchars($recipe['title']) ?>">
    </div>

    <div class="summary">
      <?php
      // Loop through the array we created at the top of the file
      if (!empty($time_components)):
        foreach ($time_components as $label => $value):
      ?>
          <div><strong><?= htmlspecialchars($label) ?>:</strong> <?= htmlspecialchars($value) ?></div>
        <?php
        endforeach;
      endif;

      // Display the difficulty as before
      if (!empty($recipe['difficulty'])):
        ?>
        <div><strong>Difficulty:</strong> <?= htmlspecialchars($recipe['difficulty']) ?></div>
      <?php endif; ?>
    </div>

    <div class="buttons">
      <button onclick="toggleCookMode()">Cook Mode</button>
      <button onclick="window.print()">🖨️ Print</button>
    </div>

    <h2>Ingredients</h2>
    <ul>
      <?php
      // MODIFICATION: New loop logic for Ingredients
      $ingredients = preg_split('/\r\n|\r|\n/', trim($recipe['ingredients']));

      foreach ($ingredients as $line) {
        $level = get_indent_level($line); // Get level from helper function
        $line_content = trim($line); // Get clean content

        if ($line_content === '') continue; // Skip empty lines

        switch ($level) {
          case 0: // Heading
            echo "<li class='list-heading'><strong>" . htmlspecialchars($line_content) . "</strong></li>";
            break;
          case 1: // Main content
            echo "<li class='list-main'>" . htmlspecialchars($line_content) . "</li>";
            break;
          case 2: // Sub-content
            echo "<li class='list-sub'>" . htmlspecialchars($line_content) . "</li>";
            break;
          case 3: // Sub-sub-content
            echo "<li class='list-sub-sub'>" . htmlspecialchars($line_content) . "</li>";
            break;
        }
      }
      ?>
    </ul>


    <h2>Instructions</h2>
    <ol>
      <?php
      // MODIFICATION: New loop logic for Instructions
      $step_counter = 1;
      $instructions = preg_split('/\r\n|\r|\n/', trim($recipe['content']));

      foreach ($instructions as $line) {
        $level = get_indent_level($line); // Get level from helper function
        $line_content = trim($line); // Get clean content

        if ($line_content === '') continue; // Skip empty lines

        switch ($level) {
          case 0: // Heading
            // Remove any user-typed numbers
            $text_only = preg_replace('/^\d+\.\s*/', '', $line_content);
            // Add our own number
            echo "<li class='instruction-heading'><strong>" . $step_counter . ". " . htmlspecialchars($text_only) . "</strong></li>";
            $step_counter++; // Only increment counter for main headings
            break;
          case 1: // Main content
            echo "<li class='list-main'>" . htmlspecialchars($line_content) . "</li>";
            break;
          case 2: // Sub-content
            echo "<li class='list-sub'>" . htmlspecialchars($line_content) . "</li>";
            break;
          case 3: // Sub-sub-content
            echo "<li class='list-sub-sub'>" . htmlspecialchars($line_content) . "</li>";
            break;
        }
      }
      ?>
    </ol>


    </div>



  <script>
    function toggleCookMode() {
      if ('wakeLock' in navigator && navigator.wakeLock.request) {
        navigator.wakeLock.request('screen')
          .then(lock => console.log('Cook Mode Enabled'))
          .catch(err => alert('Cook Mode not supported.'));
      } else {
        alert('Cook Mode not supported on this browser.');
      }
    }
  </script>
</body>

</html>