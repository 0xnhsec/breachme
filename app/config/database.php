<?php
// Database configuration
// Reads from environment variables set in docker-compose.yml

$DB_HOST = getenv('DB_HOST') ?: 'localhost';
$DB_USER = getenv('DB_USER') ?: 'nhsec';
$DB_PASS = getenv('DB_PASS') ?: 'nhsec_pass';
$DB_NAME = getenv('DB_NAME') ?: 'nhsec';
$APP_MODE = getenv('APP_MODE') ?: 'vulnerable';

// Create MySQLi connection
$conn = new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);

if ($conn->connect_error) {
    die("Koneksi database gagal: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");
