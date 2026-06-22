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
   Login + customer only
========================= */

if (!isset($_SESSION["user"]) || !isset($_SESSION["user"]["UserID"])) {
    sendJson([
        "success" => false,
        "message" => "Please login first"
    ]);
}

$userID = intval($_SESSION["user"]["UserID"] ?? 0);
$role = strtolower(trim($_SESSION["user"]["Role"] ?? ""));

if ($userID <= 0) {
    sendJson([
        "success" => false,
        "message" => "Invalid session user"
    ]);
}

if ($role !== "customer") {
    sendJson([
        "success" => false,
        "message" => "Only customers can submit reviews"
    ]);
}

/* =========================
   Table checks
========================= */

if (!tableExists($conn, "website_orders")) {
    sendJson([
        "success" => false,
        "message" => "website_orders table not found"
    ]);
}

if (!tableExists($conn, "website_reviews")) {
    sendJson([
        "success" => false,
        "message" => "website_reviews table not found"
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

$orderID = intval($data["orderID"] ?? $data["OrderID"] ?? 0);
$rating = intval($data["rating"] ?? $data["Rating"] ?? 0);
$comment = trim($data["comment"] ?? $data["Comment"] ?? "");

$customerName = trim($_SESSION["user"]["FullName"] ?? "Customer");

if ($customerName === "") {
    $customerName = "Customer";
}

/* =========================
   Validation
========================= */

if ($orderID <= 0) {
    sendJson([
        "success" => false,
        "message" => "Order ID is required"
    ]);
}

if ($rating < 1 || $rating > 5) {
    sendJson([
        "success" => false,
        "message" => "Rating must be between 1 and 5"
    ]);
}

if ($comment === "") {
    sendJson([
        "success" => false,
        "message" => "Review comment is required"
    ]);
}

if (textLength($comment) < 3) {
    sendJson([
        "success" => false,
        "message" => "Review comment is too short"
    ]);
}

if (textLength($comment) > 1000) {
    sendJson([
        "success" => false,
        "message" => "Review comment is too long"
    ]);
}

/* =========================
   Check order belongs to customer
   and is delivered
========================= */

$orderSql = "
    SELECT 
        OrderID,
        UserID,
        CustomerName,
        OrderStatus
    FROM website_orders
    WHERE OrderID = ?
    AND UserID = ?
    LIMIT 1
";

$orderStmt = mysqli_prepare($conn, $orderSql);

if (!$orderStmt) {
    sendJson([
        "success" => false,
        "message" => "Database error: " . mysqli_error($conn)
    ]);
}

mysqli_stmt_bind_param($orderStmt, "ii", $orderID, $userID);
mysqli_stmt_execute($orderStmt);

$orderResult = mysqli_stmt_get_result($orderStmt);

if (!$orderResult || mysqli_num_rows($orderResult) === 0) {
    sendJson([
        "success" => false,
        "message" => "Order not found"
    ]);
}

$order = mysqli_fetch_assoc($orderResult);
$orderStatus = strtolower(trim($order["OrderStatus"] ?? ""));

if ($orderStatus !== "delivered") {
    sendJson([
        "success" => false,
        "message" => "You can review only delivered orders"
    ]);
}

/*
   Use CustomerName from order if available,
   because it matches checkout name.
*/
$orderCustomerName = trim($order["CustomerName"] ?? "");

if ($orderCustomerName !== "") {
    $customerName = $orderCustomerName;
}

/* =========================
   Prevent duplicate review
========================= */

$checkSql = "
    SELECT ReviewID
    FROM website_reviews
    WHERE OrderID = ?
    AND UserID = ?
    LIMIT 1
";

$checkStmt = mysqli_prepare($conn, $checkSql);

if (!$checkStmt) {
    sendJson([
        "success" => false,
        "message" => "Database error: " . mysqli_error($conn)
    ]);
}

mysqli_stmt_bind_param($checkStmt, "ii", $orderID, $userID);
mysqli_stmt_execute($checkStmt);

$checkResult = mysqli_stmt_get_result($checkStmt);

if ($checkResult && mysqli_num_rows($checkResult) > 0) {
    sendJson([
        "success" => false,
        "message" => "You already reviewed this order"
    ]);
}

/* =========================
   Insert review
========================= */

$sql = "
    INSERT INTO website_reviews
    (
        OrderID,
        UserID,
        CustomerName,
        Rating,
        Comment
    )
    VALUES (?, ?, ?, ?, ?)
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
    "iisis",
    $orderID,
    $userID,
    $customerName,
    $rating,
    $comment
);

if (!mysqli_stmt_execute($stmt)) {
    sendJson([
        "success" => false,
        "message" => "Failed to submit review: " . mysqli_stmt_error($stmt)
    ]);
}

$reviewID = mysqli_insert_id($conn);

sendJson([
    "success" => true,
    "message" => "Review submitted successfully",
    "review" => [
        "ReviewID" => $reviewID,
        "OrderID" => $orderID,
        "UserID" => $userID,
        "CustomerName" => $customerName,
        "Rating" => $rating,
        "Comment" => $comment
    ]
]);
?>