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
   Admin only
========================= */

if (
    !isset($_SESSION["user"]) ||
    !isset($_SESSION["user"]["UserID"]) ||
    strtolower(trim($_SESSION["user"]["Role"] ?? "")) !== "admin"
) {
    sendJson([
        "success" => false,
        "message" => "Access denied. Admin only can view all orders.",
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
   Optional filters
   Examples:
   get-admin-orders.php?status=Delivered
   get-admin-orders.php?search=hana
========================= */

$statusFilter = trim($_GET["status"] ?? "all");
$search = trim($_GET["search"] ?? "");

$allowedStatuses = [
    "all",
    "Pending",
    "Processing",
    "In Production",
    "Ready For Shipping",
    "Shipped",
    "Out for Delivery",
    "Delivered",
    "Cancelled"
];

if (!in_array($statusFilter, $allowedStatuses, true)) {
    $statusFilter = "all";
}

$whereParts = [];
$types = "";
$values = [];

if ($statusFilter !== "all") {
    $whereParts[] = "o.OrderStatus = ?";
    $types .= "s";
    $values[] = $statusFilter;
}

if ($search !== "") {
    $whereParts[] = "(
        o.CustomerName LIKE ?
        OR o.Email LIKE ?
        OR o.Phone LIKE ?
        OR o.TrackingNumber LIKE ?
        OR CAST(o.OrderID AS CHAR) LIKE ?
    )";

    $searchLike = "%" . $search . "%";

    $types .= "sssss";
    $values[] = $searchLike;
    $values[] = $searchLike;
    $values[] = $searchLike;
    $values[] = $searchLike;
    $values[] = $searchLike;
}

$whereSql = "";

if (count($whereParts) > 0) {
    $whereSql = "WHERE " . implode(" AND ", $whereParts);
}

/* =========================
   Get admin orders
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

        sp.SupplierName AS AssignedSupplierName,
        sp.ProductionTime AS AssignedSupplierProductionTime,

        du.FullName AS DeliveryCompanyName,
        du.Email AS DeliveryEmail

    FROM website_orders o

    LEFT JOIN supplier_profiles sp
        ON sp.SupplierProfileID = o.SupplierProfileID

    LEFT JOIN users du
        ON du.UserID = o.DeliveryUserID

    $whereSql

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
   Prepare items query
========================= */

$itemStmt = mysqli_prepare($conn, "
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
");

if (!$itemStmt) {
    sendJson([
        "success" => false,
        "message" => "Items query prepare failed: " . mysqli_error($conn),
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
    "PendingOrders" => 0,
    "ProcessingOrders" => 0,
    "InProductionOrders" => 0,
    "ReadyForShippingOrders" => 0,
    "ShippedOrders" => 0,
    "OutForDeliveryOrders" => 0,
    "DeliveredOrders" => 0,
    "CancelledOrders" => 0,
    "TotalRevenue" => 0,
    "TotalQuantity" => 0
];

while ($order = mysqli_fetch_assoc($result)) {
    $orderID = intval($order["OrderID"] ?? 0);

    $items = [];
    $productTexts = [];
    $totalQuantity = 0;

    mysqli_stmt_bind_param($itemStmt, "i", $orderID);
    mysqli_stmt_execute($itemStmt);

    $itemResult = mysqli_stmt_get_result($itemStmt);

    if ($itemResult) {
        while ($item = mysqli_fetch_assoc($itemResult)) {
            $quantity = intval($item["Quantity"] ?? 1);

            if ($quantity <= 0) {
                $quantity = 1;
            }

            $unitPrice = floatval($item["UnitPrice"] ?? 0);
            $totalPrice = floatval($item["TotalPrice"] ?? 0);

            if ($totalPrice <= 0 && $unitPrice > 0) {
                $totalPrice = $unitPrice * $quantity;
            }

            if ($unitPrice <= 0 && $quantity > 0 && $totalPrice > 0) {
                $unitPrice = $totalPrice / $quantity;
            }

            $totalQuantity += $quantity;

            $supplierName = $item["SupplierName"] ?? ($order["AssignedSupplierName"] ?? "Selected Supplier");

            $cleanItem = [
                "ItemID" => intval($item["ItemID"] ?? 0),
                "OrderID" => intval($item["OrderID"] ?? 0),

                "ProductName" => $item["ProductName"] ?? "Product",
                "OptionName" => $item["OptionName"] ?? "Standard Option",

                "Quantity" => $quantity,
                "UnitPrice" => round($unitPrice, 2),
                "TotalPrice" => round($totalPrice, 2),

                "ProductImage" => $item["ProductImage"] ?? "hero-placeholder.svg",

                "SupplierName" => $supplierName,

                "DesignText" => $item["DesignText"] ?? "",
                "DesignColor" => $item["DesignColor"] ?? "",
                "Notes" => $item["Notes"] ?? ""
            ];

            $items[] = $cleanItem;

            $productTexts[] =
                ($item["ProductName"] ?? "Product") .
                " - " .
                ($item["OptionName"] ?? "Standard Option") .
                " x" .
                $quantity;
        }
    }

    $trackingNumber = trim($order["TrackingNumber"] ?? "");

    if ($trackingNumber === "") {
        $trackingNumber = "TAGGY-" . str_pad((string)$orderID, 5, "0", STR_PAD_LEFT);
    }

    $assignedSupplierName = trim($order["AssignedSupplierName"] ?? "");

    if ($assignedSupplierName === "") {
        $assignedSupplierName = "Selected Supplier";
    }

    $deliveryCompanyName = trim($order["DeliveryCompanyName"] ?? "");

    if ($deliveryCompanyName === "") {
        $deliveryCompanyName = "Assigned Delivery";
    }

    $status = $order["OrderStatus"] ?? "Pending";
    $totalAmount = floatval($order["TotalAmount"] ?? 0);

    $summary["TotalOrders"]++;
    $summary["TotalRevenue"] += $totalAmount;
    $summary["TotalQuantity"] += $totalQuantity;

    if ($status === "Pending") {
        $summary["PendingOrders"]++;
    } elseif ($status === "Processing") {
        $summary["ProcessingOrders"]++;
    } elseif ($status === "In Production") {
        $summary["InProductionOrders"]++;
    } elseif ($status === "Ready For Shipping") {
        $summary["ReadyForShippingOrders"]++;
    } elseif ($status === "Shipped") {
        $summary["ShippedOrders"]++;
    } elseif ($status === "Out for Delivery") {
        $summary["OutForDeliveryOrders"]++;
    } elseif ($status === "Delivered") {
        $summary["DeliveredOrders"]++;
    } elseif ($status === "Cancelled") {
        $summary["CancelledOrders"]++;
    }

    $orders[] = [
        "OrderID" => $orderID,
        "OrderNumber" => "TAGGY-" . $orderID,

        "UserID" => intval($order["UserID"] ?? 0),

        "SupplierProfileID" => intval($order["SupplierProfileID"] ?? 0),
        "AssignedSupplierName" => $assignedSupplierName,
        "SupplierName" => $assignedSupplierName,
        "AssignedSupplierProductionTime" => $order["AssignedSupplierProductionTime"] ?? "5-7 days",

        "DeliveryUserID" => intval($order["DeliveryUserID"] ?? 0),
        "DeliveryCompanyName" => $deliveryCompanyName,
        "DeliveryAgentName" => $deliveryCompanyName,
        "DeliveryEmail" => $order["DeliveryEmail"] ?? "",

        "TrackingNumber" => $trackingNumber,

        "CustomerName" => $order["CustomerName"] ?? "-",
        "Phone" => $order["Phone"] ?? "-",
        "Email" => $order["Email"] ?? "-",
        "Address" => $order["Address"] ?? "-",

        "DeliveryType" => $order["DeliveryType"] ?? "Standard Delivery",
        "PaymentMethod" => $order["PaymentMethod"] ?? "-",
        "PaymentStatus" => $order["PaymentStatus"] ?? "Pending",

        "ProductsTotal" => floatval($order["ProductsTotal"] ?? 0),
        "DeliveryFees" => floatval($order["DeliveryFees"] ?? 0),
        "DiscountAmount" => floatval($order["DiscountAmount"] ?? 0),
        "DiscountPercent" => floatval($order["DiscountPercent"] ?? 0),
        "TotalAmount" => $totalAmount,

        "SupplierProductionTime" => $order["SupplierProductionTime"] ?? "5-7 days",
        "FinalProductionTime" => $order["FinalProductionTime"] ?? "5-7 days",
        "EstimatedArrival" => $order["EstimatedArrival"] ?? "6-9 days",

        "OrderStatus" => $status,
        "SubscriptionPlan" => $order["SubscriptionPlan"] ?? "No Business Offer",
        "CreatedAt" => $order["CreatedAt"] ?? "",

        "Products" => count($productTexts) > 0 ? implode(", ", $productTexts) : "-",
        "TotalQuantity" => $totalQuantity,
        "ItemsCount" => count($items),

        "items" => $items
    ];
}

$summary["TotalRevenue"] = round(floatval($summary["TotalRevenue"]), 2);

sendJson([
    "success" => true,
    "message" => "Admin orders loaded successfully",
    "orders" => $orders,
    "data" => $orders,
    "count" => count($orders),
    "summary" => $summary
]);
?>