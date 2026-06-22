<?php
session_start();
include "db.php";

header("Content-Type: application/json; charset=UTF-8");

if (isset($conn)) {
    mysqli_set_charset($conn, "utf8mb4");
}

function sendJson($data) {
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit();
}

function tableExists($conn, $tableName) {
    $tableName = mysqli_real_escape_string($conn, $tableName);
    $result = mysqli_query($conn, "SHOW TABLES LIKE '$tableName'");
    return $result && mysqli_num_rows($result) > 0;
}

function textLength($text) {
    if (function_exists("mb_strlen")) {
        return mb_strlen($text, "UTF-8");
    }

    return strlen($text);
}

/* =========================
   Admin only protection
========================= */

if (!isset($_SESSION["user"]) || !isset($_SESSION["user"]["UserID"])) {
    sendJson([
        "success" => false,
        "message" => "Please login first"
    ]);
}

$role = strtolower(trim($_SESSION["user"]["Role"] ?? ""));

if ($role !== "admin") {
    sendJson([
        "success" => false,
        "message" => "Access denied. Admin only can add products."
    ]);
}

/* =========================
   Table check
========================= */

if (!tableExists($conn, "productcatalog")) {
    sendJson([
        "success" => false,
        "message" => "productcatalog table not found"
    ]);
}

/* =========================
   Read request data
========================= */

$data = json_decode(file_get_contents("php://input"), true);

if (!$data || !is_array($data)) {
    sendJson([
        "success" => false,
        "message" => "Invalid request data"
    ]);
}

$name = trim($data["name"] ?? $data["ProductName"] ?? "");
$category = trim($data["category"] ?? $data["Category"] ?? "");
$description = trim($data["description"] ?? $data["Description"] ?? "");
$image = trim($data["image"] ?? $data["ProductImage"] ?? "");

/* =========================
   Validation
========================= */

if ($name === "" || $category === "" || $description === "" || $image === "") {
    sendJson([
        "success" => false,
        "message" => "Please fill all product fields"
    ]);
}

if (textLength($name) < 2 || textLength($name) > 150) {
    sendJson([
        "success" => false,
        "message" => "Product name must be between 2 and 150 characters"
    ]);
}

if (textLength($category) < 2 || textLength($category) > 100) {
    sendJson([
        "success" => false,
        "message" => "Category must be between 2 and 100 characters"
    ]);
}

if (textLength($description) < 5 || textLength($description) > 1000) {
    sendJson([
        "success" => false,
        "message" => "Description must be between 5 and 1000 characters"
    ]);
}

if (textLength($image) > 500) {
    sendJson([
        "success" => false,
        "message" => "Product image path is too long"
    ]);
}

/* =========================
   Prevent duplicate product names
========================= */

$checkStmt = mysqli_prepare($conn, "
    SELECT CatalogProductID
    FROM productcatalog
    WHERE LOWER(ProductName) = LOWER(?)
    LIMIT 1
");

if (!$checkStmt) {
    sendJson([
        "success" => false,
        "message" => "Database error: " . mysqli_error($conn)
    ]);
}

mysqli_stmt_bind_param($checkStmt, "s", $name);
mysqli_stmt_execute($checkStmt);

$checkResult = mysqli_stmt_get_result($checkStmt);

if ($checkResult && mysqli_num_rows($checkResult) > 0) {
    sendJson([
        "success" => false,
        "message" => "Product name already exists"
    ]);
}

/* =========================
   Insert product
========================= */

$sql = "
    INSERT INTO productcatalog
    (
        ProductName,
        Category,
        Description,
        ProductImage
    )
    VALUES (?, ?, ?, ?)
";

$stmt = mysqli_prepare($conn, $sql);

if (!$stmt) {
    sendJson([
        "success" => false,
        "message" => "Database error: " . mysqli_error($conn)
    ]);
}

mysqli_stmt_bind_param(
    $stmt,
    "ssss",
    $name,
    $category,
    $description,
    $image
);

if (!mysqli_stmt_execute($stmt)) {
    sendJson([
        "success" => false,
        "message" => "Failed to add product: " . mysqli_stmt_error($stmt)
    ]);
}

$newProductID = mysqli_insert_id($conn);

$product = [
    "CatalogProductID" => $newProductID,
    "ProductName" => $name,
    "Category" => $category,
    "Description" => $description,
    "ProductImage" => $image
];

sendJson([
    "success" => true,
    "message" => "Product added successfully",
    "CatalogProductID" => $newProductID,
    "product" => $product,
    "data" => $product
]);
?>