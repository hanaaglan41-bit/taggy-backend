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

function columnExists($conn, $tableName, $columnName) {
    $tableName = preg_replace('/[^A-Za-z0-9_]/', '', $tableName);
    $columnName = mysqli_real_escape_string($conn, $columnName);
    $result = mysqli_query($conn, "SHOW COLUMNS FROM `$tableName` LIKE '$columnName'");
    return $result && mysqli_num_rows($result) > 0;
}

if (!isset($_SESSION["user"]) || !isset($_SESSION["user"]["UserID"])) {
    sendJson([
        "success" => false,
        "message" => "Please login first"
    ]);
}

$userID = intval($_SESSION["user"]["UserID"] ?? 0);
$role = strtolower(trim($_SESSION["user"]["Role"] ?? ""));

if ($userID <= 0 || $role !== "customer") {
    sendJson([
        "success" => false,
        "message" => "Only customers can report issues"
    ]);
}

$data = json_decode(file_get_contents("php://input"), true);

if (!$data || !is_array($data)) {
    sendJson([
        "success" => false,
        "message" => "Invalid request data"
    ]);
}

$orderID = intval($data["orderID"] ?? $data["OrderID"] ?? 0);
$issueType = trim($data["issueType"] ?? $data["IssueType"] ?? "");
$issueMessage = trim($data["issueMessage"] ?? $data["IssueMessage"] ?? "");

$allowedIssueTypes = [
    "Damaged Item",
    "Wrong Product",
    "Wrong Design",
    "Missing Items",
    "Quality Problem",
    "Late Delivery"
];

if ($orderID <= 0) {
    sendJson([
        "success" => false,
        "message" => "Invalid order"
    ]);
}

if ($issueType === "" || !in_array($issueType, $allowedIssueTypes, true)) {
    sendJson([
        "success" => false,
        "message" => "Invalid issue type"
    ]);
}

if ($issueMessage === "" || mb_strlen($issueMessage) < 10) {
    sendJson([
        "success" => false,
        "message" => "Issue details must be at least 10 characters"
    ]);
}

if (!tableExists($conn, "website_orders")) {
    sendJson([
        "success" => false,
        "message" => "website_orders table not found"
    ]);
}

if (!tableExists($conn, "order_issues")) {
    sendJson([
        "success" => false,
        "message" => "order_issues table not found"
    ]);
}

/* Check order belongs to this customer and is delivered */
$orderStmt = mysqli_prepare($conn, "
    SELECT OrderID, OrderStatus
    FROM website_orders
    WHERE OrderID = ?
    AND UserID = ?
    LIMIT 1
");

if (!$orderStmt) {
    sendJson([
        "success" => false,
        "message" => "Order check failed: " . mysqli_error($conn)
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
        "message" => "You can report issues only after order is delivered"
    ]);
}

/* Prevent duplicate open issue for same order */
if (columnExists($conn, "order_issues", "IssueStatus")) {
    $checkStmt = mysqli_prepare($conn, "
        SELECT IssueID
        FROM order_issues
        WHERE OrderID = ?
        AND UserID = ?
        AND IssueStatus IN ('Open', 'In Review')
        LIMIT 1
    ");

    if ($checkStmt) {
        mysqli_stmt_bind_param($checkStmt, "ii", $orderID, $userID);
        mysqli_stmt_execute($checkStmt);
        $checkResult = mysqli_stmt_get_result($checkStmt);

        if ($checkResult && mysqli_num_rows($checkResult) > 0) {
            sendJson([
                "success" => false,
                "message" => "You already have an open issue for this order"
            ]);
        }
    }
}

/* Build insert dynamically in case your table has optional columns */
$columns = [];
$placeholders = [];
$types = "";
$values = [];

function addInsertValue(&$columns, &$placeholders, &$types, &$values, $conn, $table, $column, $value, $type) {
    if (columnExists($conn, $table, $column)) {
        $columns[] = "`$column`";
        $placeholders[] = "?";
        $types .= $type;
        $values[] = $value;
    }
}

addInsertValue($columns, $placeholders, $types, $values, $conn, "order_issues", "OrderID", $orderID, "i");
addInsertValue($columns, $placeholders, $types, $values, $conn, "order_issues", "UserID", $userID, "i");
addInsertValue($columns, $placeholders, $types, $values, $conn, "order_issues", "IssueType", $issueType, "s");
addInsertValue($columns, $placeholders, $types, $values, $conn, "order_issues", "IssueMessage", $issueMessage, "s");
addInsertValue($columns, $placeholders, $types, $values, $conn, "order_issues", "IssueStatus", "Open", "s");

if (count($columns) < 4) {
    sendJson([
        "success" => false,
        "message" => "order_issues table is missing required columns"
    ]);
}

$hasCreatedAt = columnExists($conn, "order_issues", "CreatedAt");

$sql = "
    INSERT INTO order_issues
    (" . implode(", ", $columns) . ($hasCreatedAt ? ", CreatedAt" : "") . ")
    VALUES
    (" . implode(", ", $placeholders) . ($hasCreatedAt ? ", NOW()" : "") . ")
";

$stmt = mysqli_prepare($conn, $sql);

if (!$stmt) {
    sendJson([
        "success" => false,
        "message" => "Issue prepare failed: " . mysqli_error($conn)
    ]);
}

mysqli_stmt_bind_param($stmt, $types, ...$values);

if (!mysqli_stmt_execute($stmt)) {
    sendJson([
        "success" => false,
        "message" => "Issue submit failed: " . mysqli_stmt_error($stmt)
    ]);
}

sendJson([
    "success" => true,
    "message" => "Issue reported successfully",
    "IssueID" => mysqli_insert_id($conn)
]);
?>
