<?php

$host = getenv("DB_HOST") ?: getenv("MYSQLHOST");
$user = getenv("DB_USER") ?: getenv("MYSQLUSER");
$password = getenv("DB_PASS") ?: getenv("MYSQLPASSWORD");
$database = getenv("DB_NAME") ?: getenv("MYSQLDATABASE");
$port = getenv("MYSQLPORT") ?: 3306;

$conn = mysqli_connect($host, $user, $password, $database, (int)$port);

if (!$conn) {
    header("Content-Type: application/json; charset=UTF-8");
    echo json_encode([
        "success" => false,
        "message" => "Database connection failed",
        "error" => mysqli_connect_error()
    ]);
    exit();
}

mysqli_set_charset($conn, "utf8mb4");

?>
