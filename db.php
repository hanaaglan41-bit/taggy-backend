<?php

$host = getenv("MYSQLHOST");
$user = getenv("MYSQLUSER");
$password = getenv("MYSQLPASSWORD");
$database = getenv("MYSQLDATABASE");
$port = getenv("MYSQLPORT");

$conn = mysqli_connect(
    $host,
    $user,
    $password,
    $database,
    $port
);

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
