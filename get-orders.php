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
   Login + Customer only
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

$userID = intval($_SESSION["user"]["UserID"] ?? 0);
$role = strtolower(trim($_SESSION["user"]["Role"] ?? ""));

if ($userID <= 0) {
    sendJson([
        "success" => false,
        "message" => "Invalid session user",
        "orders" => [],
        "data" => [],
        "count" => 0
    ]);
}

if ($role !== "customer") {
    sendJson([
        "success" => false,
        "message" => "Only customers can view their orders here",
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
   Get customer orders
========================= */

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

        du.FullName AS DeliveryCompanyName,
        du.Email AS DeliveryCompanyEmail,

        sp.SupplierName AS AssignedSupplierName,
        sp.ProductionTime AS AssignedSupplierProductionTime

    FROM website_orders o

    LEFT JOIN users du
        ON du.UserID = o.DeliveryUserID

    LEFT JOIN supplier_profiles sp
        ON sp.SupplierProfileID = o.SupplierProfileID

    WHERE o.UserID = ?

    ORDER BY o.OrderID DESC
";

$orderStmt = mysqli_prepare($conn, $orderSql);

if (!$orderStmt) {
    sendJson([
        "success" => false,
        "message" => "Orders query failed: " . mysqli_error($conn),
        "orders" => [],
        "data" => [],
        "count" => 0
    ]);
}

mysqli_stmt_bind_param($orderStmt, "i", $userID);
mysqli_stmt_execute($orderStmt);

$orderResult = mysqli_stmt_get_result($orderStmt);

if (!$orderResult) {
    sendJson([
        "success" => false,
        "message" => "Orders loading failed: " . mysqli_stmt_error($orderStmt),
        "orders" => [],
        "data" => [],
        "count" => 0
    ]);
}

/* =========================
   Prepare items query
========================= */

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
        "message" => "Items query prepare failed: " . mysqli_error($conn),
        "orders" => [],
        "data" => [],
        "count" => 0
    ]);
}

/* =========================
   Format orders
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
    "TotalSpent" => 0,
    "TotalQuantity" => 0
];

while ($order = mysqli_fetch_assoc($orderResult)) {
    $orderID = intval($order["OrderID"] ?? 0);

    $items = [];
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

            $items[] = [
                "ItemID" => intval($item["ItemID"] ?? 0),
                "OrderID" => $orderID,

                "product" => $item["ProductName"] ?? "Product",
                "ProductName" => $item["ProductName"] ?? "Product",

                "option" => $item["OptionName"] ?? "Standard Option",
                "OptionName" => $item["OptionName"] ?? "Standard Option",

                "quantity" => $quantity,
                "Quantity" => $quantity,

                "price" => number_format($totalPrice, 2) . " EGP",
                "unitPrice" => round($unitPrice, 2),
                "UnitPrice" => round($unitPrice, 2),
                "TotalPrice" => round($totalPrice, 2),

                "image" => $item["ProductImage"] ?? "hero-placeholder.svg",
                "ProductImage" => $item["ProductImage"] ?? "hero-placeholder.svg",

                "SupplierName" => $supplierName,
                "supplierName" => $supplierName,

                "supplierBaseProduction" => $order["SupplierProductionTime"] ?? "5-7 days",
                "finalProductionTime" => $order["FinalProductionTime"] ?? "5-7 days",
                "estimatedArrival" => $order["EstimatedArrival"] ?? "6-9 days",

                "designText" => $item["DesignText"] ?? "",
                "DesignText" => $item["DesignText"] ?? "",

                "designColor" => $item["DesignColor"] ?? "",
                "DesignColor" => $item["DesignColor"] ?? "",

                "notes" => $item["Notes"] ?? "",
                "Notes" => $item["Notes"] ?? "",

                "SupplierQABadge" => "QA Checked"
            ];
        }
    }

    $status = trim($order["OrderStatus"] ?? "Pending");
    $paymentStatus = trim($order["PaymentStatus"] ?? "Pending");
    $trackingNumber = trim($order["TrackingNumber"] ?? "");

    if ($trackingNumber === "") {
        $trackingNumber = "TAGGY-" . str_pad((string)$orderID, 5, "0", STR_PAD_LEFT);
    }

    $totalAmount = floatval($order["TotalAmount"] ?? 0);

    $summary["TotalOrders"]++;
    $summary["TotalSpent"] += $totalAmount;
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

    /*
       Reviews and issues are allowed only after Delivered.
       This matches add-review.php and report-issue.php rules.
    */
    $canReview = $status === "Delivered";
    $canReportIssue = $status === "Delivered";

    $orders[] = [
        "orderId" => "TAGGY-" . $orderID,
        "OrderID" => $orderID,
        "OrderNumber" => "TAGGY-" . $orderID,

        "UserID" => intval($order["UserID"] ?? 0),

        "createdAt" => $order["CreatedAt"] ?? "",
        "CreatedAt" => $order["CreatedAt"] ?? "",

        "status" => $status,
        "OrderStatus" => $status,

        "paymentMethod" => $order["PaymentMethod"] ?? "-",
        "PaymentMethod" => $order["PaymentMethod"] ?? "-",

        "paymentStatus" => $paymentStatus,
        "PaymentStatus" => $paymentStatus,

        "trackingNumber" => $trackingNumber,
        "TrackingNumber" => $trackingNumber,

        "DeliveryType" => $order["DeliveryType"] ?? "Standard Delivery",
        "deliveryType" => $order["DeliveryType"] ?? "Standard Delivery",

        "DeliveryUserID" => intval($order["DeliveryUserID"] ?? 0),
        "DeliveryCompanyName" => $order["DeliveryCompanyName"] ?? "Assigned Delivery",
        "DeliveryCompanyEmail" => $order["DeliveryCompanyEmail"] ?? "",

        "SupplierProfileID" => intval($order["SupplierProfileID"] ?? 0),
        "AssignedSupplierName" => $order["AssignedSupplierName"] ?? "Selected Supplier",
        "AssignedSupplierProductionTime" => $order["AssignedSupplierProductionTime"] ?? "5-7 days",

        "canReview" => $canReview,
        "canReportIssue" => $canReportIssue,

        "customer" => [
            "fullName" => $order["CustomerName"] ?? "-",
            "email" => $order["Email"] ?? "-",
            "phone" => $order["Phone"] ?? "-",
            "address" => $order["Address"] ?? "-",
            "notes" => ""
        ],

        "items" => $items,

        "summary" => [
            "itemsCount" => count($items),
            "totalQuantity" => $totalQuantity,

            "subtotal" => floatval($order["ProductsTotal"] ?? 0),
            "productsTotal" => floatval($order["ProductsTotal"] ?? 0),

            "deliveryFees" => floatval($order["DeliveryFees"] ?? 0),

            "discountAmount" => floatval($order["DiscountAmount"] ?? 0),
            "discountPercent" => floatval($order["DiscountPercent"] ?? 0),

            "finalTotal" => $totalAmount,
            "totalAmount" => $totalAmount,

            "supplierBaseProduction" => $order["SupplierProductionTime"] ?? "5-7 days",
            "finalProductionTime" => $order["FinalProductionTime"] ?? "5-7 days",
            "estimatedArrival" => $order["EstimatedArrival"] ?? "6-9 days",

            "subscriptionPlan" => $order["SubscriptionPlan"] ?? "No Business Offer",

            "trackingNumber" => $trackingNumber,
            "paymentStatus" => $paymentStatus,
            "deliveryType" => $order["DeliveryType"] ?? "Standard Delivery",
            "deliveryCompany" => $order["DeliveryCompanyName"] ?? "Assigned Delivery",
            "deliveryCompanyEmail" => $order["DeliveryCompanyEmail"] ?? "",
            "assignedSupplier" => $order["AssignedSupplierName"] ?? "Selected Supplier"
        ]
    ];
}

$summary["TotalSpent"] = round(floatval($summary["TotalSpent"]), 2);

sendJson([
    "success" => true,
    "message" => "Orders loaded successfully",
    "orders" => $orders,
    "data" => $orders,
    "count" => count($orders),
    "summary" => $summary
]);
?>