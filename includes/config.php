<?php
// config.php
// Database configuration for TechNest
// Permissions: set this file to 600 on deployment for security
// Consider using environment variables for sensitive data in production

define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_NAME', getenv('DB_NAME') ?: 'adams63_brettcommerce');
define('DB_USER', getenv('DB_USER') ?: 'adams63_brettcommerce');
define('DB_PASS', getenv('DB_PASS') ?: 'T5AWvHQT7Q4x6q64WZJ7');