<?php

$host = "localhost";
$user = "root";
$pass = "";
$db   = "atenea";

/** @var mysqli $conn */
$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Error de conexión: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");