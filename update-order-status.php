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

$data = json_decode(file_get_contents("php://input"), true);

if (!$data || !is_array($data)) {
    sendJson([
        "success" => false,
        "message" => "No data received"
    ]);
}

$orderID = intval($data["OrderID"] ?? $data["orderID"] ?? 0);
$newStatus = trim($data["Status"] ?? $data["status"] ?? "");

$allStatuses = [
    "Pending",
    "Processing",
    "In Production",
    "Ready For Shipping",
    "Shipped",
    "Out for Delivery",
    "Delivered",
    "Cancelled"
];

if ($orderID <= 0 || !in_array($newStatus, $allStatuses, true)) {
    sendJson([
        "success" => false,
        "message" => "Invalid order or status"
    ]);
}

/* =====================================================
   Helpers
===================================================== */

function columnExists($conn, $tableName, $columnName) {
    $tableName = preg_replace('/[^A-Za-z0-9_]/', '', $tableName);
    $columnName = mysqli_real_escape_string($conn, $columnName);

    $result = mysqli_query($conn, "SHOW COLUMNS FROM `$tableName` LIKE '$columnName'");
    return $result && mysqli_num_rows($result) > 0;
}

function getOrderTotalQuantity($conn, $orderID) {
    $stmt = mysqli_prepare($conn, "
        SELECT COALESCE(SUM(Quantity), 0) AS TotalQuantity
        FROM website_order_items
        WHERE OrderID = ?
    ");

    if (!$stmt) {
        return 0;
    }

    mysqli_stmt_bind_param($stmt, "i", $orderID);
    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);

    if ($result && mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        return intval($row["TotalQuantity"] ?? 0);
    }

    return 0;
}

function getDeliveryRuleByQuantity($totalQuantity) {
    if ($totalQuantity >= 101) {
        return [
            "email" => "bulk.delivery@taggy.com",
            "company" => "Bulk Delivery Company"
        ];
    }

    if ($totalQuantity >= 11) {
        return [
            "email" => "medium.delivery@taggy.com",
            "company" => "Medium Delivery Company"
        ];
    }

    return [
        "email" => "small.delivery@taggy.com",
        "company" => "Small Delivery Company"
    ];
}

function getDeliveryUserIDByEmail($conn, $email) {
    $stmt = mysqli_prepare($conn, "
        SELECT UserID
        FROM users
        WHERE Email = ?
        AND LOWER(Role) = 'delivery'
        LIMIT 1
    ");

    if (!$stmt) {
        return 0;
    }

    mysqli_stmt_bind_param($stmt, "s", $email);
    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);

    if ($result && mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        return intval($row["UserID"] ?? 0);
    }

    return 0;
}

function assignDeliveryUserByQuantity($conn, $orderID) {
    $totalQuantity = getOrderTotalQuantity($conn, $orderID);
    $deliveryRule = getDeliveryRuleByQuantity($totalQuantity);

    $deliveryUserID = getDeliveryUserIDByEmail($conn, $deliveryRule["email"]);

    return [
        "DeliveryUserID" => $deliveryUserID,
        "DeliveryCompany" => $deliveryRule["company"],
        "DeliveryEmail" => $deliveryRule["email"],
        "TotalQuantity" => $totalQuantity
    ];
}

/* =====================================================
   Get order
===================================================== */

$hasSupplierUserID = columnExists($conn, "supplier_profiles", "UserID");

if ($hasSupplierUserID) {
    $orderSql = "
        SELECT
            o.OrderID,
            o.OrderStatus,
            o.SupplierProfileID,
            o.DeliveryUserID,
            o.TrackingNumber,
            o.PaymentStatus,
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
            o.OrderStatus,
            o.SupplierProfileID,
            o.DeliveryUserID,
            o.TrackingNumber,
            o.PaymentStatus,
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

$currentStatus = trim($order["OrderStatus"] ?? "Pending");

if ($newStatus === $currentStatus) {
    sendJson([
        "success" => true,
        "message" => "Status is already " . $newStatus,
        "OrderID" => $orderID,
        "OldStatus" => $currentStatus,
        "NewStatus" => $newStatus
    ]);
}

/* =====================================================
   Role permissions and workflow rules
===================================================== */

$allowedTransitions = [
    "supplier" => [
        "Processing" => ["In Production"],
        "In Production" => ["Ready For Shipping"]
    ],
    "delivery" => [
        "Ready For Shipping" => ["Shipped"],
        "Shipped" => ["Out for Delivery"],
        "Out for Delivery" => ["Delivered"]
    ]
];

/*
   Admin is view-only.
   Customer cannot update order status.
*/
if ($role === "admin") {
    sendJson([
        "success" => false,
        "message" => "Admin is view-only. Status updates are handled by supplier and delivery roles."
    ]);
}

if ($role === "customer") {
    sendJson([
        "success" => false,
        "message" => "Customers cannot update order status."
    ]);
}

if ($role === "supplier") {
    if (!$hasSupplierUserID) {
        sendJson([
            "success" => false,
            "message" => "Supplier account link is missing in supplier_profiles. Please add UserID column or assign supplier correctly."
        ]);
    }

    $supplierUserID = intval($order["SupplierUserID"] ?? 0);

    if ($supplierUserID <= 0 || $supplierUserID !== $currentUserID) {
        sendJson([
            "success" => false,
            "message" => "Access denied. This order is assigned to another supplier."
        ]);
    }

    $nextStatuses = $allowedTransitions["supplier"][$currentStatus] ?? [];

    if (!in_array($newStatus, $nextStatuses, true)) {
        sendJson([
            "success" => false,
            "message" => "Invalid workflow. Supplier can only move Processing → In Production → Ready For Shipping."
        ]);
    }

} elseif ($role === "delivery") {
    $deliveryUserID = intval($order["DeliveryUserID"] ?? 0);

    if ($deliveryUserID <= 0 || $deliveryUserID !== $currentUserID) {
        sendJson([
            "success" => false,
            "message" => "Access denied. This order is assigned to another delivery company."
        ]);
    }

    $nextStatuses = $allowedTransitions["delivery"][$currentStatus] ?? [];

    if (!in_array($newStatus, $nextStatuses, true)) {
        sendJson([
            "success" => false,
            "message" => "Invalid workflow. Delivery can only move Ready For Shipping → Shipped → Out for Delivery → Delivered."
        ]);
    }

} else {
    sendJson([
        "success" => false,
        "message" => "Access denied"
    ]);
}

/* =====================================================
   Tracking, payment, delivery assignment
===================================================== */

$trackingNumber = trim($order["TrackingNumber"] ?? "");

if (
    in_array($newStatus, ["Ready For Shipping", "Shipped", "Out for Delivery", "Delivered"], true)
    && $trackingNumber === ""
) {
    $trackingNumber = "TAGGY-" . str_pad((string)$orderID, 5, "0", STR_PAD_LEFT);
}

$paymentStatus = trim($order["PaymentStatus"] ?? "Pending");

if ($newStatus === "Delivered") {
    $paymentStatus = "Paid";
}

$deliveryUserID = intval($order["DeliveryUserID"] ?? 0);
$deliveryAssignment = null;

if (
    $deliveryUserID <= 0
    && in_array($newStatus, ["Ready For Shipping", "Shipped", "Out for Delivery", "Delivered"], true)
) {
    $deliveryAssignment = assignDeliveryUserByQuantity($conn, $orderID);
    $deliveryUserID = intval($deliveryAssignment["DeliveryUserID"] ?? 0);

    if ($deliveryUserID <= 0) {
        sendJson([
            "success" => false,
            "message" => "No delivery company is available for this order quantity"
        ]);
    }
}

/* =====================================================
   Update order + insert status history
===================================================== */

mysqli_begin_transaction($conn);

$updateStmt = mysqli_prepare($conn, "
    UPDATE website_orders
    SET
        OrderStatus = ?,
        TrackingNumber = ?,
        PaymentStatus = ?,
        DeliveryUserID = ?
    WHERE OrderID = ?
");

if (!$updateStmt) {
    mysqli_rollback($conn);

    sendJson([
        "success" => false,
        "message" => "Update prepare failed: " . mysqli_error($conn)
    ]);
}

mysqli_stmt_bind_param(
    $updateStmt,
    "sssii",
    $newStatus,
    $trackingNumber,
    $paymentStatus,
    $deliveryUserID,
    $orderID
);

if (!mysqli_stmt_execute($updateStmt)) {
    mysqli_rollback($conn);

    sendJson([
        "success" => false,
        "message" => "Update failed: " . mysqli_stmt_error($updateStmt)
    ]);
}

/*
   Order Status History:
   Insert one history record for every real status change.
*/
$historyStmt = mysqli_prepare($conn, "
    INSERT INTO order_status_history
    (
        OrderID,
        ChangedByUserID,
        OldStatus,
        NewStatus,
        Source,
        Note
    )
    VALUES (?, ?, ?, ?, ?, ?)
");

if (!$historyStmt) {
    mysqli_rollback($conn);

    sendJson([
        "success" => false,
        "message" => "Status history prepare failed: " . mysqli_error($conn)
    ]);
}

$historySource = $role;

if ($role === "supplier") {
    $historyNote = "Production status updated by supplier";
} elseif ($role === "delivery") {
    $historyNote = "Delivery status updated by delivery user";
} else {
    $historyNote = "Order status updated";
}

mysqli_stmt_bind_param(
    $historyStmt,
    "iissss",
    $orderID,
    $currentUserID,
    $currentStatus,
    $newStatus,
    $historySource,
    $historyNote
);

if (!mysqli_stmt_execute($historyStmt)) {
    mysqli_rollback($conn);

    sendJson([
        "success" => false,
        "message" => "Status history insert failed: " . mysqli_stmt_error($historyStmt)
    ]);
}

mysqli_commit($conn);

sendJson([
    "success" => true,
    "message" => "Status updated successfully",
    "OrderID" => $orderID,
    "OldStatus" => $currentStatus,
    "NewStatus" => $newStatus,
    "TrackingNumber" => $trackingNumber,
    "PaymentStatus" => $paymentStatus,
    "DeliveryUserID" => $deliveryUserID,
    "DeliveryAssignment" => $deliveryAssignment
]);
?>