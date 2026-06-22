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

if ($role !== "delivery" && $role !== "admin") {
    sendJson([
        "success" => false,
        "message" => "Access denied. Delivery dashboard is for delivery and admin only.",
        "orders" => [],
        "data" => [],
        "count" => 0
    ]);
}

/* =========================
   Tables check
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

if (!tableExists($conn, "website_order_items")) {
    sendJson([
        "success" => false,
        "message" => "website_order_items table not found",
        "orders" => [],
        "data" => [],
        "count" => 0
    ]);
}

/* =========================
   Delivery Dashboard Flow

   Delivery sees only orders assigned to DeliveryUserID.
   Admin can view all delivery orders.
   Visible statuses:
   Ready For Shipping
   Shipped
   Out for Delivery
   Delivered
========================= */

$whereParts = [
    "o.OrderStatus IN ('Ready For Shipping', 'Shipped', 'Out for Delivery', 'Delivered')"
];

$types = "";
$values = [];

if ($role === "delivery") {
    $whereParts[] = "o.DeliveryUserID = ?";
    $types .= "i";
    $values[] = $currentUserID;
}

$whereSql = implode(" AND ", $whereParts);

/* =========================
   Get delivery orders
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

        du.FullName AS DeliveryCompanyName,
        du.Email AS DeliveryEmail,

        sp.SupplierName AS AssignedSupplierName,

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

    LEFT JOIN users du
        ON du.UserID = o.DeliveryUserID

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

        du.FullName,
        du.Email,

        sp.SupplierName

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
    "ReadyForShippingOrders" => 0,
    "ShippedOrders" => 0,
    "OutForDeliveryOrders" => 0,
    "DeliveredOrders" => 0,
    "TotalQuantity" => 0,
    "TotalDeliveryFees" => 0,
    "TotalValue" => 0
];

while ($row = mysqli_fetch_assoc($result)) {
    $orderID = intval($row["OrderID"] ?? 0);

    $trackingNumber = trim($row["TrackingNumber"] ?? "");

    if ($trackingNumber === "") {
        $trackingNumber = "TAGGY-" . str_pad((string)$orderID, 5, "0", STR_PAD_LEFT);
    }

    $deliveryCompanyName = trim($row["DeliveryCompanyName"] ?? "");

    if ($deliveryCompanyName === "") {
        $deliveryCompanyName = "Assigned Delivery";
    }

    $status = $row["OrderStatus"] ?? "Ready For Shipping";
    $totalQuantity = intval($row["TotalQuantity"] ?? 0);
    $deliveryFees = floatval($row["DeliveryFees"] ?? 0);
    $totalAmount = floatval($row["TotalAmount"] ?? 0);

    $summary["TotalOrders"]++;
    $summary["TotalQuantity"] += $totalQuantity;
    $summary["TotalDeliveryFees"] += $deliveryFees;
    $summary["TotalValue"] += $totalAmount;

    if ($status === "Ready For Shipping") {
        $summary["ReadyForShippingOrders"]++;
    } elseif ($status === "Shipped") {
        $summary["ShippedOrders"]++;
    } elseif ($status === "Out for Delivery") {
        $summary["OutForDeliveryOrders"]++;
    } elseif ($status === "Delivered") {
        $summary["DeliveredOrders"]++;
    }

    $orders[] = [
        "OrderID" => $orderID,
        "OrderNumber" => "TAGGY-" . $orderID,

        "UserID" => intval($row["UserID"] ?? 0),

        "SupplierProfileID" => intval($row["SupplierProfileID"] ?? 0),
        "AssignedSupplierName" => $row["AssignedSupplierName"] ?? "Selected Supplier",

        "DeliveryUserID" => intval($row["DeliveryUserID"] ?? 0),
        "DeliveryCompanyName" => $deliveryCompanyName,
        "DeliveryAgentName" => $deliveryCompanyName,
        "DeliveryEmail" => $row["DeliveryEmail"] ?? "",

        "TrackingNumber" => $trackingNumber,

        "CustomerName" => $row["CustomerName"] ?? "-",
        "Phone" => $row["Phone"] ?? "-",
        "Email" => $row["Email"] ?? "-",
        "Address" => $row["Address"] ?? "-",

        "DeliveryType" => $row["DeliveryType"] ?? "Standard Delivery",
        "PaymentMethod" => $row["PaymentMethod"] ?? "-",
        "PaymentStatus" => $row["PaymentStatus"] ?? "Pending",

        "ProductsTotal" => floatval($row["ProductsTotal"] ?? 0),
        "DeliveryFees" => $deliveryFees,
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

$summary["TotalDeliveryFees"] = round(floatval($summary["TotalDeliveryFees"]), 2);
$summary["TotalValue"] = round(floatval($summary["TotalValue"]), 2);

sendJson([
    "success" => true,
    "message" => "Delivery orders loaded successfully",
    "orders" => $orders,
    "data" => $orders,
    "count" => count($orders),
    "summary" => $summary
]);
?>