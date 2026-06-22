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

$userID = intval($_SESSION["user"]["UserID"] ?? 0);
$role = strtolower(trim($_SESSION["user"]["Role"] ?? ""));

if ($userID <= 0) {
    sendJson([
        "success" => false,
        "message" => "Invalid session user"
    ]);
}

/*
   Track order is for customers.
   Admin, supplier, and delivery have their own dashboards.
*/
if ($role !== "customer") {
    sendJson([
        "success" => false,
        "message" => "Only customers can track their orders here"
    ]);
}

$orderID = intval($_GET["orderID"] ?? $_GET["OrderID"] ?? 0);
$trackingNumber = trim($_GET["trackingNumber"] ?? $_GET["TrackingNumber"] ?? "");

if ($orderID <= 0 && $trackingNumber === "") {
    sendJson([
        "success" => false,
        "message" => "Order ID or tracking number is required"
    ]);
}

/* Build order query */
if ($orderID > 0) {
    $orderSql = "
        SELECT 
            o.OrderID,
            o.UserID,
            o.SupplierProfileID,
            o.DeliveryUserID,
            o.TrackingNumber,
            o.CustomerName,
            o.Phone,
            o.Email,
            o.Address,
            o.DeliveryType,
            o.PaymentMethod,
            o.PaymentStatus,
            o.ProductsTotal,
            o.DeliveryFees,
            o.DiscountAmount,
            o.DiscountPercent,
            o.TotalAmount,
            o.SupplierProductionTime,
            o.FinalProductionTime,
            o.EstimatedArrival,
            o.OrderStatus,
            o.SubscriptionPlan,
            o.CreatedAt,
            sp.SupplierName,
            d.FullName AS DeliveryName,
            d.Email AS DeliveryEmail
        FROM website_orders o
        LEFT JOIN supplier_profiles sp
            ON sp.SupplierProfileID = o.SupplierProfileID
        LEFT JOIN users d
            ON d.UserID = o.DeliveryUserID
        WHERE o.OrderID = ?
        AND o.UserID = ?
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

} else {
    $orderSql = "
        SELECT 
            o.OrderID,
            o.UserID,
            o.SupplierProfileID,
            o.DeliveryUserID,
            o.TrackingNumber,
            o.CustomerName,
            o.Phone,
            o.Email,
            o.Address,
            o.DeliveryType,
            o.PaymentMethod,
            o.PaymentStatus,
            o.ProductsTotal,
            o.DeliveryFees,
            o.DiscountAmount,
            o.DiscountPercent,
            o.TotalAmount,
            o.SupplierProductionTime,
            o.FinalProductionTime,
            o.EstimatedArrival,
            o.OrderStatus,
            o.SubscriptionPlan,
            o.CreatedAt,
            sp.SupplierName,
            d.FullName AS DeliveryName,
            d.Email AS DeliveryEmail
        FROM website_orders o
        LEFT JOIN supplier_profiles sp
            ON sp.SupplierProfileID = o.SupplierProfileID
        LEFT JOIN users d
            ON d.UserID = o.DeliveryUserID
        WHERE o.TrackingNumber = ?
        AND o.UserID = ?
        LIMIT 1
    ";

    $orderStmt = mysqli_prepare($conn, $orderSql);

    if (!$orderStmt) {
        sendJson([
            "success" => false,
            "message" => "Database error: " . mysqli_error($conn)
        ]);
    }

    mysqli_stmt_bind_param($orderStmt, "si", $trackingNumber, $userID);
}

mysqli_stmt_execute($orderStmt);

$orderResult = mysqli_stmt_get_result($orderStmt);

if (!$orderResult || mysqli_num_rows($orderResult) === 0) {
    sendJson([
        "success" => false,
        "message" => "Order not found"
    ]);
}

$order = mysqli_fetch_assoc($orderResult);
$orderID = intval($order["OrderID"] ?? 0);

/* Get order items */
$itemSql = "
    SELECT
        ItemID,
        OrderID,
        ProductName,
        OptionName,
        Quantity,
        UnitPrice,
        TotalPrice,
        ProductImage,
        SupplierName,
        DesignText,
        DesignColor,
        Notes
    FROM website_order_items
    WHERE OrderID = ?
    ORDER BY ItemID ASC
";

$itemStmt = mysqli_prepare($conn, $itemSql);

if (!$itemStmt) {
    sendJson([
        "success" => false,
        "message" => "Items query error: " . mysqli_error($conn)
    ]);
}

mysqli_stmt_bind_param($itemStmt, "i", $orderID);
mysqli_stmt_execute($itemStmt);

$itemResult = mysqli_stmt_get_result($itemStmt);

$items = [];

if ($itemResult) {
    while ($item = mysqli_fetch_assoc($itemResult)) {
        $item["ItemID"] = intval($item["ItemID"] ?? 0);
        $item["OrderID"] = intval($item["OrderID"] ?? 0);
        $item["Quantity"] = intval($item["Quantity"] ?? 0);
        $item["UnitPrice"] = floatval($item["UnitPrice"] ?? 0);
        $item["TotalPrice"] = floatval($item["TotalPrice"] ?? 0);

        $items[] = $item;
    }
}

/* Timeline */
$timelineSteps = [
    "Processing",
    "In Production",
    "Ready For Shipping",
    "Shipped",
    "Out for Delivery",
    "Delivered"
];

$currentStatus = trim($order["OrderStatus"] ?? "Processing");
$currentStepIndex = array_search($currentStatus, $timelineSteps, true);

if ($currentStepIndex === false) {
    $currentStepIndex = -1;
}

$timeline = [];

foreach ($timelineSteps as $index => $step) {
    $timeline[] = [
        "status" => $step,
        "completed" => $index <= $currentStepIndex,
        "current" => $index === $currentStepIndex
    ];
}

/* Normalize numbers */
$order["OrderID"] = intval($order["OrderID"] ?? 0);
$order["UserID"] = intval($order["UserID"] ?? 0);
$order["SupplierProfileID"] = intval($order["SupplierProfileID"] ?? 0);
$order["DeliveryUserID"] = intval($order["DeliveryUserID"] ?? 0);
$order["ProductsTotal"] = floatval($order["ProductsTotal"] ?? 0);
$order["DeliveryFees"] = floatval($order["DeliveryFees"] ?? 0);
$order["DiscountAmount"] = floatval($order["DiscountAmount"] ?? 0);
$order["DiscountPercent"] = floatval($order["DiscountPercent"] ?? 0);
$order["TotalAmount"] = floatval($order["TotalAmount"] ?? 0);

sendJson([
    "success" => true,
    "message" => "Order tracking loaded successfully",
    "order" => $order,
    "items" => $items,
    "timeline" => $timeline,
    "tracking" => [
        "OrderID" => $order["OrderID"],
        "TrackingNumber" => $order["TrackingNumber"] ?? "",
        "CurrentStatus" => $currentStatus,
        "EstimatedArrival" => $order["EstimatedArrival"] ?? "",
        "DeliveryType" => $order["DeliveryType"] ?? "",
        "DeliveryName" => $order["DeliveryName"] ?? "",
        "DeliveryEmail" => $order["DeliveryEmail"] ?? ""
    ]
]);
?>