<?php
// fix_image_mapping.php
// Usage (CLI): php fix_image_mapping.php [apply]
// Usage (browser): open file, add ?apply=1 to apply

set_time_limit(0);
require_once __DIR__ . '/db.php'; // <- your existing db.php should set $conn (PDO)

$imagesDir = rtrim(__DIR__, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'images';
if (!is_dir($imagesDir)) {
    die("ERROR: images folder not found at: $imagesDir\n");
}

// normalize function: ascii translit, lowercase, replace spaces/special chars with underscore
function normalize_name($name) {
    $name = mb_substr($name, 0, 255);
    $name = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $name) ?: $name;
    $name = strtolower($name);
    $name = preg_replace('/[^a-z0-9._-]+/', '_', $name);
    $name = preg_replace('/_+/', '_', $name);
    return trim($name, '_');
}

// read files in images dir
$files = array_values(array_filter(scandir($imagesDir), function($f) use ($imagesDir) {
    return is_file($imagesDir . DIRECTORY_SEPARATOR . $f);
}));
$filesNorm = [];
foreach ($files as $f) {
    $filesNorm[$f] = normalize_name($f);
}

// fetch DB rows
$stmt = $conn->query("SELECT id, image FROM recipes ORDER BY id ASC");
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

// collect report rows
$report = [];

foreach ($rows as $r) {
    $id = (int)$r['id'];
    $dbVal = trim((string)$r['image']);
    $dbBase = basename($dbVal);
    $dbBaseNorm = normalize_name($dbBase);

    $foundExact = null;
    // 1) exact match with prefix removed/kept
    if (file_exists($imagesDir . DIRECTORY_SEPARATOR . $dbBase)) {
        $foundExact = $dbBase;
    } else {
        // 2) try normalized exact
        foreach ($filesNorm as $file => $fnorm) {
            if ($fnorm === $dbBaseNorm) {
                $foundExact = $file;
                break;
            }
        }
    }

    if ($foundExact !== null) {
        $report[] = [
            'id' => $id,
            'db' => $dbVal,
            'status' => 'FOUND',
            'file_on_disk' => $foundExact,
            'distance' => 0
        ];
        continue;
    }

    // 3) no direct match — find best fuzzy match by levenshtein on normalized names
    $best = null;
    $bestDist = PHP_INT_MAX;
    foreach ($filesNorm as $file => $fnorm) {
        $dist = levenshtein($dbBaseNorm, $fnorm);
        if ($dist < $bestDist) {
            $bestDist = $dist;
            $best = $file;
        }
    }
    $report[] = [
        'id' => $id,
        'db' => $dbVal,
        'status' => 'MISMATCH',
        'file_on_disk' => $best,
        'distance' => $bestDist
    ];
}

// Output report (CLI-friendly and HTML if run in browser)
$apply = (PHP_SAPI !== 'cli' && isset($_GET['apply'])) || (PHP_SAPI === 'cli' && isset($argv[1]) && $argv[1] === 'apply');

function printLine($s) {
    if (PHP_SAPI === 'cli') echo $s . PHP_EOL;
    else echo nl2br(htmlspecialchars($s)) . "<br>\n";
}

printLine("Images folder: $imagesDir");
printLine("Rows checked: " . count($report));
printLine(str_repeat('-', 60));
foreach ($report as $r) {
    $id = $r['id'];
    printLine("ID: $id");
    printLine("  DB: " . $r['db']);
    printLine("  Status: " . $r['status']);
    printLine("  Disk file suggestion: " . ($r['file_on_disk'] ?? '---'));
    printLine("  Distance: " . $r['distance']);
    if ($r['status'] === 'FOUND') {
        $sql = "UPDATE recipes SET image = 'images/" . addslashes($r['file_on_disk']) . "' WHERE id = $id;";
        printLine("  SQL (safe): " . $sql);
    } else {
        // only suggest update if levenshtein distance reasonably small
        if ($r['distance'] <= 6) {
            $sql = "UPDATE recipes SET image = 'images/" . addslashes($r['file_on_disk']) . "' WHERE id = $id;";
            printLine("  SUGGESTED SQL: " . $sql);
        } else {
            printLine("  SUGGESTION: No safe automatic match (distance too large). Please fix manually.");
        }
    }
    printLine(str_repeat('-', 40));
}

// If apply requested, run safe updates where FOUND or distance <= 3 (conservative)
if ($apply) {
    printLine("APPLY MODE: running safe updates now...");
    $updated = 0;
    foreach ($report as $r) {
        $id = $r['id'];
        $newFile = $r['file_on_disk'];
        if (!$newFile) continue;
        // conservative threshold: accept either FOUND (distance 0) OR distance <= 3
        if ($r['distance'] === 0 || $r['distance'] <= 3) {
            $sql = "UPDATE myapp_recipe SET image = ? WHERE id = ?";
            $s = $conn->prepare($sql);
            $s->execute(['images/' . $newFile, $id]);
            $updated++;
            printLine("Updated id=$id -> images/$newFile");
        } else {
            printLine("Skipped id=$id (distance={$r['distance']})");
        }
    }
    printLine("Done. Rows updated: $updated");
} else {
    printLine("Dry-run complete. To apply safe automatic updates, run:");
    if (PHP_SAPI === 'cli') printLine("php fix_image_mapping.php apply");
    else printLine("visit this page with ?apply=1 or run the CLI version.");
}
