<?php
// config.php
// Database configuration for TechNest.
//
// Credentials come from the environment. There are deliberately no defaults
// for the name, user and password: this file is committed to a public
// repository, so any fallback baked in here is a published credential. An
// earlier version of this file did exactly that.
//
// Set these before serving the app:
//   export DB_HOST=localhost
//   export DB_NAME=technest
//   export DB_USER=technest_user
//   export DB_PASS=...
//
// See INSTALLATION_GUIDE.md for the full setup, including the schema and seed.

define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_NAME', getenv('DB_NAME') ?: '');
define('DB_USER', getenv('DB_USER') ?: '');
define('DB_PASS', getenv('DB_PASS') ?: '');
