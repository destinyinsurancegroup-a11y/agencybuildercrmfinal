<?php
/**
 * Agency Builder CRM - Tier 1
 * Clean Index Entry Point (no layout.php dependency)
 */

// Set timezone
date_default_timezone_set('America/Detroit');

// Define path constants
$basePath = realpath(__DIR__ . '/../');
$viewsPath = $basePath . '/resources/views/';

// Get requested page (if any)
$page = $_GET['page'] ?? 'dashboard';

// Sanitize input
$page = preg_replace('/[^a-zA-Z0-9_-]/', '', $page);

// Resolve path to the requested view
$targetFile = $viewsPath . $page . '.php';

// If the requested view exists, load it
if (file_exists($targetFile)) {
    include($targetFile);
} else {
    // Fallback to dashboard if page not found
    include($viewsPath . 'dashboard.php');
}
