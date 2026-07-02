<?php

$configPath = __DIR__ . "/../config.local.php";

if (!is_file($configPath)) {
    die("Falta config.local.php. Copia config.local.example.php y completa tus datos locales.");
}

require_once $configPath;

/** @var mysqli $conn */
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if ($conn->connect_error) {
    die("Error de conexión: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");
