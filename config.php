<?php
// config.php

// Database credentials - Railway-compatible
// Railway MySQL provides: MYSQLHOST, MYSQLPORT, MYSQLUSER, MYSQLPASSWORD, MYSQLDATABASE
define('DB_HOST', getenv('MYSQLHOST') ?: getenv('DB_HOST') ?: 'localhost');
define('DB_USER', getenv('MYSQLUSER') ?: getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('MYSQLPASSWORD') ?: getenv('DB_PASS') ?: '');
define('DB_NAME', getenv('MYSQLDATABASE') ?: getenv('DB_NAME') ?: 'railway');
define('DB_PORT', getenv('MYSQLPORT') ?: getenv('DB_PORT') ?: 3306);

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
            $conn = @new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);
            if ($conn->connect_error) {
                throw new Exception("Connection failed: " . $conn->connect_error);
            }
            $conn->set_charset("utf8mb4");
            
            // Auto-create tables if they don't exist (Railway-friendly)
            setupDatabaseTables($conn);
        } catch (Exception $e) {
            throw new Exception("Database connection error: " . $e->getMessage());
        }
    }
    return $conn;
}

// Auto-setup database tables on first run
function setupDatabaseTables($conn) {
    // Check if users table exists
    $result = $conn->query("SHOW TABLES LIKE 'users'");
    if ($result->num_rows > 0) {
        return; // Tables already exist
    }
    
    // Create tables - Railway database is already created, just need tables
    $sql = file_get_contents(__DIR__ . '/database.sql');
    
    // Remove CREATE DATABASE and USE statements - Railway DB already exists
    $sql = preg_replace('/CREATE DATABASE[^;]+;/i', '', $sql);
    $sql = preg_replace('/USE [^;]+;/i', '', $sql);
    
    // Split into individual statements and execute
    $statements = array_filter(array_map('trim', explode(';', $sql)));
    
    foreach ($statements as $statement) {
        if (!empty($statement)) {
            if (!$conn->query($statement)) {
                error_log("Failed to create table: " . $conn->error);
            }
        }
    }
}

