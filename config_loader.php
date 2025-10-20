<?php
// config_loader.php

// Define APP_ROOT as the absolute path to the project directory if it's not already defined.
// This makes file includes much more reliable.
if (!defined('APP_ROOT')) {
    define('APP_ROOT', __DIR__);
}

// Define the absolute path to the secure configuration directory.
// This makes the application more portable as the server environment may change.
// The path /home/qdosnetw/brisas_secure_configs was provided by the user.
$secure_config_path = '/home/qdosnetw/brisas_secure_configs/main_config.php';

// Check if the file exists before trying to include it.
if (file_exists($secure_config_path)) {
    require_once $secure_config_path;
} else {
    // If the file doesn't exist, stop execution and show a JSON error.
    // This provides a clearer error on the frontend which expects a JSON response.
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'The main configuration file is missing or the path is incorrect. Please check the server configuration.']);
    exit;
}
