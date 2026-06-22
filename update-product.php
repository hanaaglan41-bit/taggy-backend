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

if (
    !isset($_SESSION["user"]) ||
    !isset($_SESSION["user"]["UserID"])
) {
    sendJson([
        "success" => false,
        "message" => "Please login first"
    ]);
}

$role = strtolower(trim($_SESSION["user"]["Role"] ?? ""));

if ($role !== "admin") {
    sendJson([
        "success" => false,
        "message" => "Access denied. Admin only can update products."
    ]);
}

$data = json_decode(file_get_contents("php://input"), true);

if (!$data || !is_array($data)) {
    sendJson([
        "success" => false,
        "message" => "Invalid request data"
    ]);
}

$id = intval($data["id"] ?? $data["CatalogProductID"] ?? 0);
$name = trim($data["name"] ?? $data["ProductName"] ?? "");
$category = trim($data["category"] ?? $data["Category"] ?? "");
$description = trim($data["description"] ?? $data["Description"] ?? "");
$image = trim($data["image"] ?? $data["ProductImage"] ?? "");

if ($id <= 0) {
    sendJson([
        "success" => false,
        "message" => "Invalid product ID"
    ]);
}

if ($name === "" || $category === "" || $description === "" || $image === "") {
    sendJson([
        "success" => false,
        "message" => "Please fill all product fields"
    ]);
}

if (mb_strlen($name, "UTF-8") < 2 || mb_strlen($name, "UTF-8") > 150) {
    sendJson([
        "success" => false,
        "message" => "Product name must be between 2 and 150 characters"
    ]);
}

if (mb_strlen($category, "UTF-8") < 2 || mb_strlen($category, "UTF-8") > 100) {
    sendJson([
        "success" => false,
        "message" => "Category must be between 2 and 100 characters"
    ]);
}

if (mb_strlen($description, "UTF-8") < 5 || mb_strlen($description, "UTF-8") > 1000) {
    sendJson([
        "success" => false,
        "message" => "Description must be between 5 and 1000 characters"
    ]);
}

/* Check product exists */
$checkStmt = mysqli_prepare($conn, "
    SELECT CatalogProductID
    FROM productcatalog
    WHERE CatalogProductID = ?
    LIMIT 1
");

if (!$checkStmt) {
    sendJson([
        "success" => false,
        "message" => "Database error: " . mysqli_error($conn)
    ]);
}

mysqli_stmt_bind_param($checkStmt, "i", $id);
mysqli_stmt_execute($checkStmt);

$checkResult = mysqli_stmt_get_result($checkStmt);

if (!$checkResult || mysqli_num_rows($checkResult) === 0) {
    sendJson([
        "success" => false,
        "message" => "Product not found"
    ]);
}

/* Prevent duplicate product name with another product */
$duplicateStmt = mysqli_prepare($conn, "
    SELECT CatalogProductID
    FROM productcatalog
    WHERE LOWER(ProductName) = LOWER(?)
    AND CatalogProductID != ?
    LIMIT 1
");

if (!$duplicateStmt) {
    sendJson([
        "success" => false,
        "message" => "Database error: " . mysqli_error($conn)
    ]);
}

mysqli_stmt_bind_param($duplicateStmt, "si", $name, $id);
mysqli_stmt_execute($duplicateStmt);

$duplicateResult = mysqli_stmt_get_result($duplicateStmt);

if ($duplicateResult && mysqli_num_rows($duplicateResult) > 0) {
    sendJson([
        "success" => false,
        "message" => "Another product already has this name"
    ]);
}

/* Update product */
$stmt = mysqli_prepare($conn, "
    UPDATE productcatalog
    SET
        ProductName = ?,
        Category = ?,
        Description = ?,
        ProductImage = ?
    WHERE CatalogProductID = ?
");

if (!$stmt) {
    sendJson([
        "success" => false,
        "message" => "Database error: " . mysqli_error($conn)
    ]);
}

mysqli_stmt_bind_param(
    $stmt,
    "ssssi",
    $name,
    $category,
    $description,
    $image,
    $id
);

if (!mysqli_stmt_execute($stmt)) {
    sendJson([
        "success" => false,
        "message" => "Update failed: " . mysqli_stmt_error($stmt)
    ]);
}

sendJson([
    "success" => true,
    "message" => "Product updated successfully",
    "product" => [
        "CatalogProductID" => $id,
        "ProductName" => $name,
        "Category" => $category,
        "Description" => $description,
        "ProductImage" => $image
    ]
]);
?>