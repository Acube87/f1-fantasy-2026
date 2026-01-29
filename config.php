<?php
// config.php

// Check if a Railway-specific config file exists and load it.
if (file_exists(__DIR__ . '/config.railway.php')) {
    require_once(__DIR__ . '/config.railway.php');
} else {
    // For local development, define credentials here.
    // This file should be in .gitignore
    define('DB_HOST', 'localhost');
    define('DB_USER', 'root');
    define('DB_PASS', '');
    define('DB_NAME', 'f1_predictions');
}

// F1 API
define('F1_API_BASE', 'https://ergast.com/api/f1/');

// Scoring points
define('POINTS_EXACT_POSITION', 10);
define('POINTS_CORRECT_FINISHER', 5);
define('POINTS_POLE_POSITION', 7);
define('POINTS_FASTEST_LAP', 7);

// Start session
session_name('f1_fantasy_session');
session_start();

// Database connection
function getDB() {
    static $conn = null;
    if ($conn === null) {
        try {
            $conn = @new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
            if ($conn->connect_error) {
                throw new Exception("Connection failed: " . $conn->connect_error);
            }
            $conn->set_charset("utf8mb4");
        } catch (Exception $e) {
            throw new Exception("Database connection error: " . $e->getMessage());
        }
    }
    return $conn;
}

