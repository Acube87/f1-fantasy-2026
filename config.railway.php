<?php
// config.railway.php

// On Railway, you would set these as environment variables.
// For local development, you can create a config.local.php and define them there.

// Database credentials
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') ?: '');
define('DB_NAME', getenv('DB_NAME') ?: 'f1_predictions');

// F1 API
define('F1_API_BASE', 'https://ergast.com/api/f1/');

// Scoring points
define('POINTS_EXACT_POSITION', 10);
define('POINTS_CORRECT_FINISHER', 5);
define('POINTS_POLE_POSITION', 7);
define('POINTS_FASTEST_LAP', 7);