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

function columnExists($conn, $tableName, $columnName) {
    $tableName = preg_replace('/[^A-Za-z0-9_]/', '', $tableName);
    $columnName = mysqli_real_escape_string($conn, $columnName);

    $result = mysqli_query($conn, "SHOW COLUMNS FROM `$tableName` LIKE '$columnName'");
    return $result && mysqli_num_rows($result) > 0;
}

function tableExists($conn, $tableName) {
    $tableName = mysqli_real_escape_string($conn, $tableName);

    $result = mysqli_query($conn, "SHOW TABLES LIKE '$tableName'");
    return $result && mysqli_num_rows($result) > 0;
}

if (!isset($_SESSION["user"]) || !isset($_SESSION["user"]["UserID"])) {
    sendJson([
        "success" => false,
        "message" => "Please login first"
    ]);
}

$currentUserID = intval($_SESSION["user"]["UserID"] ?? 0);
$role = strtolower(trim($_SESSION["user"]["Role"] ?? ""));

if ($currentUserID <= 0) {
    sendJson([
        "success" => false,
        "message" => "Invalid session user"
    ]);
}

$orderID = intval($_GET["orderID"] ?? $_GET["OrderID"] ?? 0);

if ($orderID <= 0) {
    sendJson([
        "success" => false,
        "message" => "Order ID is required"
    ]);
}

if (!tableExists($conn, "order_status_history")) {
    sendJson([
        "success" => false,
        "message" => "order_status_history table does not exist. Please create it first."
    ]);
}

/* =====================================================
   Check order access
===================================================== */

$hasSupplierUserID = columnExists($conn, "supplier_profiles", "UserID");

if ($hasSupplierUserID) {
    $orderSql = "
        SELECT
            o.OrderID,
            o.UserID,
            o.SupplierProfileID,
            o.DeliveryUserID,
            o.TrackingNumber,
            o.OrderStatus,
            o.CreatedAt,
            sp.UserID AS SupplierUserID
        FROM website_orders o
        LEFT JOIN supplier_profiles sp
            ON sp.SupplierProfileID = o.SupplierProfileID
        WHERE o.OrderID = ?
        LIMIT 1
    ";
} else {
    $orderSql = "
        SELECT
            o.OrderID,
            o.UserID,
            o.SupplierProfileID,
            o.DeliveryUserID,
            o.TrackingNumber,
            o.OrderStatus,
            o.CreatedAt,
            0 AS SupplierUserID
        FROM website_orders o
        WHERE o.OrderID = ?
        LIMIT 1
    ";
}

$orderStmt = mysqli_prepare($conn, $orderSql);

if (!$orderStmt) {
    sendJson([
        "success" => false,
        "message" => "Order query failed: " . mysqli_error($conn)
    ]);
}

mysqli_stmt_bind_param($orderStmt, "i", $orderID);
mysqli_stmt_execute($orderStmt);

$orderResult = mysqli_stmt_get_result($orderStmt);

if (!$orderResult || mysqli_num_rows($orderResult) === 0) {
    sendJson([
        "success" => false,
        "message" => "Order not found"
    ]);
}

$order = mysqli_fetch_assoc($orderResult);

$orderOwnerID = intval($order["UserID"] ?? 0);
$supplierUserID = intval($order["SupplierUserID"] ?? 0);
$deliveryUserID = intval($order["DeliveryUserID"] ?? 0);

/*
    Access rules:
    - Admin can view any order history.
    - Customer can view only his/her own order history.
    - Supplier can view only orders assigned to his/her supplier profile.
    - Delivery can view only orders assigned to his/her delivery user.
*/

if ($role === "admin") {
    // allowed

} elseif ($role === "customer") {
    if ($orderOwnerID !== $currentUserID) {
        sendJson([
            "success" => false,
            "message" => "Access denied. You can view only your own order history."
        ]);
    }

} elseif ($role === "supplier") {
    if (!$hasSupplierUserID) {
        sendJson([
            "success" => false,
            "message" => "Supplier account link is missing in supplier_profiles."
        ]);
    }

    if ($supplierUserID <= 0 || $supplierUserID !== $currentUserID) {
        sendJson([
            "success" => false,
            "message" => "Access denied. This order is assigned to another supplier."
        ]);
    }

} elseif ($role === "delivery") {
    if ($deliveryUserID <= 0 || $deliveryUserID !== $currentUserID) {
        sendJson([
            "success" => false,
            "message" => "Access denied. This order is assigned to another delivery company."
        ]);
    }

} else {
    sendJson([
        "success" => false,
        "message" => "Access denied"
    ]);
}

/* =====================================================
   Get status history
===================================================== */

$historySql = "
    SELECT
        h.HistoryID,
        h.OrderID,
        h.ChangedByUserID,
        h.OldStatus,
        h.NewStatus,
        h.Source,
        h.Note,
        h.CreatedAt,

        u.FullName AS ChangedByName,
        u.Email AS ChangedByEmail,
        u.Role AS ChangedByRole
    FROM order_status_history h
    LEFT JOIN users u
        ON h.ChangedByUserID = u.UserID
    WHERE h.OrderID = ?
    ORDER BY h.HistoryID ASC
";

$historyStmt = mysqli_prepare($conn, $historySql);

if (!$historyStmt) {
    sendJson([
        "success" => false,
        "message" => "History query failed: " . mysqli_error($conn)
    ]);
}

mysqli_stmt_bind_param($historyStmt, "i", $orderID);
mysqli_stmt_execute($historyStmt);

$historyResult = mysqli_stmt_get_result($historyStmt);

$history = [];

while ($row = mysqli_fetch_assoc($historyResult)) {
    $history[] = [
        "HistoryID" => intval($row["HistoryID"]),
        "OrderID" => intval($row["OrderID"]),

        "ChangedByUserID" => $row["ChangedByUserID"] !== null
            ? intval($row["ChangedByUserID"])
            : null,

        "ChangedByName" => $row["ChangedByName"] ?? "System",
        "ChangedByEmail" => $row["ChangedByEmail"] ?? "",
        "ChangedByRole" => $row["ChangedByRole"] ?? "",

        "OldStatus" => $row["OldStatus"] ?? "",
        "NewStatus" => $row["NewStatus"] ?? "",
        "Source" => $row["Source"] ?? "",
        "Note" => $row["Note"] ?? "",
        "CreatedAt" => $row["CreatedAt"] ?? ""
    ];
}

sendJson([
    "success" => true,
    "message" => "Order status history loaded successfully",
    "order" => [
        "OrderID" => intval($order["OrderID"]),
        "TrackingNumber" => $order["TrackingNumber"] ?? "",
        "CurrentStatus" => $order["OrderStatus"] ?? "",
        "CreatedAt" => $order["CreatedAt"] ?? ""
    ],
    "history" => $history
]);
?>