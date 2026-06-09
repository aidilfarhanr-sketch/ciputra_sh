<?php
// Copy file ini menjadi config.local.php untuk XAMPP lokal
// atau config.production.php untuk hosting/VPS production.
// Jangan commit config.local.php/config.production.php jika berisi password asli.

define('CIPUTRA_DB_HOST', '127.0.0.1');
define('CIPUTRA_DB_PORT', '3306');
define('CIPUTRA_DB_NAME', 'ciputra_sh');
define('CIPUTRA_DB_USER', 'root');
define('CIPUTRA_DB_PASS', '');

// Opsional: local | production
// Untuk production juga bisa set environment variable CIPUTRA_ENV=production.
