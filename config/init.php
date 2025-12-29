<?php
/**
 * init.php
 * Global setup for Recipe Sharing & Product Website
 * - Defines project root path
 * - Starts session
 * - Loads database connection
 */

// 1. Define the base project folder (team_project) as absolute filesystem path
if (!defined("BASE_PATH")) {
    define("BASE_PATH", realpath(__DIR__ . "/.."));
    // Example: C:/xampp/htdocs/team_project
}

// 2. Define the base URL for assets (public folder, used in HTML)
if (!defined("BASE_URL")) {
    define("BASE_URL", "/team_project/public/");
}

// 3. Start session (only once)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 4. Load database connection
require_once BASE_PATH . "/config/db.php";

// 5. (Optional later) load helper functions
// require_once BASE_PATH . "/config/functions.php";
