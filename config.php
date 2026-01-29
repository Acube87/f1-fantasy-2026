<?php
// config.php

// Database credentials
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') ?: '');
define('DB_NAME', getenv('DB_NAME') ?: 'f1_predictions');

// F1 API
define('F1_API_BASE', getenv('F1_API_BASE') ?: 'https://ergast.com/api/f1/');
define('F1_API_TIMEOUT', getenv('F1_API_TIMEOUT') ?: 30);

// Application settings
define('SITE_NAME', getenv('SITE_NAME') ?: 'F1 2026 Fantasy');
define('SESSION_NAME', getenv('SESSION_NAME') ?: 'f1_fantasy_session');

// Scoring points
define('POINTS_EXACT_POSITION', getenv('POINTS_EXACT_POSITION') ?: 10);
define('POINTS_OFF_BY_ONE', getenv('POINTS_OFF_BY_ONE') ?: 1);
define('POINTS_CORRECT_FINISHER', getenv('POINTS_CORRECT_FINISHER') ?: 5);
define('POINTS_POLE_POSITION', getenv('POINTS_POLE_POSITION') ?: 7);
define('POINTS_FASTEST_LAP', getenv('POINTS_FASTEST_LAP') ?: 7);
define('POINTS_TOP3_BONUS', getenv('POINTS_TOP3_BONUS') ?: 30);
define('POINTS_CONSTRUCTOR_EXACT', getenv('POINTS_CONSTRUCTOR_EXACT') ?: 10);
define('POINTS_CONSTRUCTOR_TOP3', getenv('POINTS_CONSTRUCTOR_TOP3') ?: 30);


// Start session (only if not already started)
session_name(SESSION_NAME);
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

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

