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

function cleanName($name) {
    return preg_replace('/[^A-Za-z0-9_]/', '', $name);
}

function tableExists($conn, $tableName) {
    $tableName = mysqli_real_escape_string($conn, $tableName);
    $result = mysqli_query($conn, "SHOW TABLES LIKE '$tableName'");
    return $result && mysqli_num_rows($result) > 0;
}

function columnExists($conn, $tableName, $columnName) {
    $tableName = cleanName($tableName);
    $columnName = mysqli_real_escape_string($conn, $columnName);

    $result = mysqli_query($conn, "SHOW COLUMNS FROM `$tableName` LIKE '$columnName'");
    return $result && mysqli_num_rows($result) > 0;
}

/* =========================
   Login + role protection
========================= */

if (!isset($_SESSION["user"]) || !isset($_SESSION["user"]["UserID"])) {
    sendJson([
        "success" => false,
        "message" => "Please login first",
        "orders" => [],
        "data" => [],
        "count" => 0
    ]);
}

$currentUserID = intval($_SESSION["user"]["UserID"] ?? 0);
$currentUserEmail = trim($_SESSION["user"]["Email"] ?? "");
$role = strtolower(trim($_SESSION["user"]["Role"] ?? ""));

if ($currentUserID <= 0) {
    sendJson([
        "success" => false,
        "message" => "Invalid session user",
        "orders" => [],
        "data" => [],
        "count" => 0
    ]);
}

if ($role !== "supplier" && $role !== "admin") {
    sendJson([
        "success" => false,
        "message" => "Access denied. Supplier dashboard is for supplier and admin only.",
        "orders" => [],
        "data" => [],
        "count" => 0
    ]);
}

/* =========================
   Required tables check
========================= */

if (!tableExists($conn, "website_orders")) {
    sendJson([
        "success" => false,
        "message" => "website_orders table not found",
        "orders" => [],
        "data" => [],
        "count" => 0
    ]);
}

if (!tableExists($conn, "supplier_profiles")) {
    sendJson([
        "success" => false,
        "message" => "supplier_profiles table not found",
        "orders" => [],
        "data" => [],
        "count" => 0
    ]);
}

/* =========================
   Supplier linking
   Supplier dashboard must show orders assigned to the logged-in supplier.
   Best linking:
   1) supplier_profiles.UserID = users.UserID
   2) OR supplier_profiles.Email = users.Email
   This is important because some databases have UserID column but it is empty.
========================= */

$hasSupplierUserID = columnExists($conn, "supplier_profiles", "UserID");
$hasSupplierEmail = columnExists($conn, "supplier_profiles", "Email");

$whereParts = [
    "o.OrderStatus IN ('Processing', 'In Production', 'Ready For Shipping')"
];

$types = "";
$values = [];

if ($role === "supplier") {
    if ($hasSupplierUserID && $hasSupplierEmail && $currentUserEmail !== "") {
        $whereParts[] = "(sp.UserID = ? OR LOWER(TRIM(sp.Email)) = LOWER(TRIM(?)))";
        $types .= "is";
        $values[] = $currentUserID;
        $values[] = $currentUserEmail;
    } elseif ($hasSupplierUserID) {
        $whereParts[] = "sp.UserID = ?";
        $types .= "i";
        $values[] = $currentUserID;
    } elseif ($hasSupplierEmail && $currentUserEmail !== "") {
        $whereParts[] = "LOWER(TRIM(sp.Email)) = LOWER(TRIM(?))";
        $types .= "s";
        $values[] = $currentUserEmail;
    } else {
        sendJson([
            "success" => false,
            "message" => "Supplier account is not linked. Add UserID or Email in supplier_profiles.",
            "orders" => [],
            "data" => [],
            "count" => 0
        ]);
    }
}

$whereSql = implode(" AND ", $whereParts);

$supplierUserIDSelect = $hasSupplierUserID ? "sp.UserID AS SupplierUserID" : "0 AS SupplierUserID";
$supplierEmailSelect = $hasSupplierEmail ? "sp.Email AS SupplierEmail" : "'' AS SupplierEmail";

/* =========================
   Query supplier orders
========================= */

$sql = "
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

        $supplierUserIDSelect,
        $supplierEmailSelect,
        sp.SupplierName AS AssignedSupplierName,
        sp.ProductionTime AS AssignedSupplierProductionTime,

        GROUP_CONCAT(
            DISTINCT CONCAT(
                COALESCE(oi.ProductName, 'Product'),
                ' - ',
                COALESCE(oi.OptionName, 'Standard Option'),
                ' x',
                COALESCE(oi.Quantity, 1)
            )
            ORDER BY oi.ItemID
            SEPARATOR ', '
        ) AS Products,

        COALESCE(SUM(COALESCE(oi.Quantity, 0)), 0) AS TotalQuantity,
        COUNT(oi.ItemID) AS ItemsCount

    FROM website_orders o

    LEFT JOIN supplier_profiles sp
        ON sp.SupplierProfileID = o.SupplierProfileID

    LEFT JOIN website_order_items oi
        ON oi.OrderID = o.OrderID

    WHERE $whereSql

    GROUP BY
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
        sp.ProductionTime
        " . ($hasSupplierUserID ? ", sp.UserID" : "") . "
        " . ($hasSupplierEmail ? ", sp.Email" : "") . "

    ORDER BY o.OrderID DESC
";

$stmt = mysqli_prepare($conn, $sql);

if (!$stmt) {
    sendJson([
        "success" => false,
        "message" => "Query prepare failed: " . mysqli_error($conn),
        "orders" => [],
        "data" => [],
        "count" => 0
    ]);
}

if ($types !== "") {
    $bindParams = [];
    $bindParams[] = $types;

    foreach ($values as $key => $value) {
        $bindParams[] = &$values[$key];
    }

    call_user_func_array([$stmt, "bind_param"], $bindParams);
}

mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (!$result) {
    sendJson([
        "success" => false,
        "message" => "Query failed: " . mysqli_stmt_error($stmt),
        "orders" => [],
        "data" => [],
        "count" => 0
    ]);
}

/* =========================
   Format response
========================= */

$orders = [];

$summary = [
    "TotalOrders" => 0,
    "ProcessingOrders" => 0,
    "InProductionOrders" => 0,
    "ReadyForShippingOrders" => 0,
    "TotalQuantity" => 0,
    "TotalValue" => 0
];

while ($row = mysqli_fetch_assoc($result)) {
    $orderID = intval($row["OrderID"] ?? 0);

    $trackingNumber = trim($row["TrackingNumber"] ?? "");

    if ($trackingNumber === "") {
        $trackingNumber = "TAGGY-" . str_pad((string)$orderID, 5, "0", STR_PAD_LEFT);
    }

    $status = $row["OrderStatus"] ?? "Processing";
    $totalQuantity = intval($row["TotalQuantity"] ?? 0);
    $totalAmount = floatval($row["TotalAmount"] ?? 0);

    $summary["TotalOrders"]++;
    $summary["TotalQuantity"] += $totalQuantity;
    $summary["TotalValue"] += $totalAmount;

    if ($status === "Processing") {
        $summary["ProcessingOrders"]++;
    } elseif ($status === "In Production") {
        $summary["InProductionOrders"]++;
    } elseif ($status === "Ready For Shipping") {
        $summary["ReadyForShippingOrders"]++;
    }

    $orders[] = [
        "OrderID" => $orderID,
        "OrderNumber" => "TAGGY-" . $orderID,

        "UserID" => intval($row["UserID"] ?? 0),

        "SupplierProfileID" => intval($row["SupplierProfileID"] ?? 0),
        "SupplierUserID" => intval($row["SupplierUserID"] ?? 0),
        "SupplierEmail" => $row["SupplierEmail"] ?? "",
        "AssignedSupplierName" => $row["AssignedSupplierName"] ?? "Selected Supplier",
        "AssignedSupplierProductionTime" => $row["AssignedSupplierProductionTime"] ?? "5-7 days",

        "DeliveryUserID" => intval($row["DeliveryUserID"] ?? 0),

        "TrackingNumber" => $trackingNumber,

        "CustomerName" => $row["CustomerName"] ?? "-",
        "Phone" => $row["Phone"] ?? "-",
        "Email" => $row["Email"] ?? "-",
        "Address" => $row["Address"] ?? "-",

        "DeliveryType" => $row["DeliveryType"] ?? "Standard Delivery",
        "PaymentMethod" => $row["PaymentMethod"] ?? "-",
        "PaymentStatus" => $row["PaymentStatus"] ?? "Pending",

        "ProductsTotal" => floatval($row["ProductsTotal"] ?? 0),
        "DeliveryFees" => floatval($row["DeliveryFees"] ?? 0),
        "DiscountAmount" => floatval($row["DiscountAmount"] ?? 0),
        "DiscountPercent" => floatval($row["DiscountPercent"] ?? 0),
        "TotalAmount" => $totalAmount,

        "SupplierProductionTime" => $row["SupplierProductionTime"] ?? "5-7 days",
        "FinalProductionTime" => $row["FinalProductionTime"] ?? "5-7 days",
        "EstimatedArrival" => $row["EstimatedArrival"] ?? "6-9 days",

        "OrderStatus" => $status,
        "SubscriptionPlan" => $row["SubscriptionPlan"] ?? "No Business Offer",
        "CreatedAt" => $row["CreatedAt"] ?? "",

        "Products" => $row["Products"] ?? "-",
        "TotalQuantity" => $totalQuantity,
        "ItemsCount" => intval($row["ItemsCount"] ?? 0)
    ];
}

$summary["TotalValue"] = round(floatval($summary["TotalValue"]), 2);

sendJson([
    "success" => true,
    "message" => "Supplier orders loaded successfully",
    "orders" => $orders,
    "data" => $orders,
    "count" => count($orders),
    "summary" => $summary,
    "currentUser" => [
        "UserID" => $currentUserID,
        "Email" => $currentUserEmail,
        "Role" => $role
    ]
]);
?>
