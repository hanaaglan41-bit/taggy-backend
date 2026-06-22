<?php
/*
  Deployment-ready database connection.
  - Local XAMPP/MAMP still works with the old defaults.
  - Railway works using MYSQLHOST / MYSQLUSER / MYSQLPASSWORD / MYSQLDATABASE / MYSQLPORT
    or DB_HOST / DB_USER / DB_PASSWORD / DB_NAME / DB_PORT.
*/

$databaseUrl = getenv("DATABASE_URL") ?: "";

if ($databaseUrl) {
    $url = parse_url($databaseUrl);
    $host = $url["host"] ?? "localhost";
    $user = $url["user"] ?? "root";
    $password = $url["pass"] ?? "";
    $database = isset($url["path"]) ? ltrim($url["path"], "/") : "custom_gifts_full_db";
    $port = $url["port"] ?? 3306;
} else {
    $host = getenv("MYSQLHOST") ?: getenv("DB_HOST") ?: "localhost";
    $user = getenv("MYSQLUSER") ?: getenv("DB_USER") ?: "root";
    $password = getenv("MYSQLPASSWORD") ?: getenv("DB_PASSWORD") ?: "";
    $database = getenv("MYSQLDATABASE") ?: getenv("DB_NAME") ?: "custom_gifts_full_db";
    $port = getenv("MYSQLPORT") ?: getenv("DB_PORT") ?: 3306;
}

$conn = mysqli_connect($host, $user, $password, $database, (int)$port);

if (!$conn) {
    http_response_code(500);
    header("Content-Type: application/json; charset=UTF-8");

    echo json_encode([
        "success" => false,
        "message" => "Database connection failed. Check Railway MySQL environment variables and imported database.sql.",
        "error" => mysqli_connect_error()
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

    exit();
}

mysqli_set_charset($conn, "utf8mb4");
?>
