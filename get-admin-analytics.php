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
    !isset($_SESSION["user"]["UserID"]) ||
    strtolower(trim($_SESSION["user"]["Role"] ?? "")) !== "admin"
) {
    sendJson([
        "success" => false,
        "message" => "Access denied"
    ]);
}

function cleanTableName($tableName) {
    return preg_replace('/[^A-Za-z0-9_]/', '', $tableName);
}

function cleanColumnName($columnName) {
    return preg_replace('/[^A-Za-z0-9_]/', '', $columnName);
}

function tableExists($conn, $tableName) {
    $tableName = mysqli_real_escape_string($conn, $tableName);
    $result = mysqli_query($conn, "SHOW TABLES LIKE '$tableName'");
    return $result && mysqli_num_rows($result) > 0;
}

function columnExists($conn, $tableName, $columnName) {
    $tableName = cleanTableName($tableName);
    $columnName = mysqli_real_escape_string($conn, $columnName);

    if ($tableName === "" || $columnName === "") {
        return false;
    }

    $result = mysqli_query($conn, "SHOW COLUMNS FROM `$tableName` LIKE '$columnName'");
    return $result && mysqli_num_rows($result) > 0;
}

function getExistingColumn($conn, $tableName, $possibleColumns) {
    foreach ($possibleColumns as $column) {
        if (columnExists($conn, $tableName, $column)) {
            return cleanColumnName($column);
        }
    }

    return null;
}

function getFirstExistingTable($conn, $possibleTables) {
    foreach ($possibleTables as $table) {
        if (tableExists($conn, $table)) {
            return cleanTableName($table);
        }
    }

    return null;
}

function safeFloat($value) {
    return round(floatval($value ?? 0), 2);
}

function getPlanPrice($plan, $savedPrice = 0) {
    $savedPrice = floatval($savedPrice);

    if ($savedPrice > 0) {
        return $savedPrice;
    }

    $plan = strtolower(trim($plan));

    if ($plan === "small" || $plan === "starter") {
        return 250;
    }

    if ($plan === "growth") {
        return 500;
    }

    if ($plan === "premium") {
        return 900;
    }

    return 0;
}

function emptyStatsResponse($message) {
    sendJson([
        "success" => false,
        "message" => $message,
        "stats" => [],
        "subscriptionStats" => [],
        "reviewStats" => [],
        "statusData" => [],
        "revenueByStatus" => [],
        "topProducts" => [],
        "revenueTrend" => [],
        "ratingDistribution" => []
    ]);
}

/* =========================
   Required orders table
========================= */

if (!tableExists($conn, "website_orders")) {
    emptyStatsResponse("website_orders table not found. Please import the correct database.sql file.");
}

$totalAmountColumn = getExistingColumn($conn, "website_orders", ["TotalAmount", "total_amount", "GrandTotal"]);
$orderStatusColumn = getExistingColumn($conn, "website_orders", ["OrderStatus", "Status", "order_status"]);
$dateColumn = getExistingColumn($conn, "website_orders", ["CreatedAt", "OrderDate", "created_at"]);

$totalExpr = $totalAmountColumn ? "`$totalAmountColumn`" : "0";
$statusExpr = $orderStatusColumn ? "`$orderStatusColumn`" : "''";
$dateExpr = $dateColumn ? "`$dateColumn`" : null;

$ordersTodaySql = $dateExpr
    ? "COALESCE(SUM(CASE WHEN DATE($dateExpr) = CURDATE() THEN 1 ELSE 0 END), 0)"
    : "0";

$ordersThisWeekSql = $dateExpr
    ? "COALESCE(SUM(CASE WHEN YEARWEEK($dateExpr, 1) = YEARWEEK(CURDATE(), 1) THEN 1 ELSE 0 END), 0)"
    : "0";

$deliveredTodaySql = ($dateExpr && $orderStatusColumn)
    ? "COALESCE(SUM(CASE WHEN DATE($dateExpr) = CURDATE() AND $statusExpr = 'Delivered' THEN 1 ELSE 0 END), 0)"
    : "0";

/* =========================
   Main order stats
========================= */

$statsSql = "
    SELECT
        COUNT(*) AS TotalOrders,
        COALESCE(SUM($totalExpr), 0) AS TotalRevenue,
        COALESCE(AVG($totalExpr), 0) AS AverageOrderValue,

        $ordersTodaySql AS OrdersToday,
        $ordersThisWeekSql AS OrdersThisWeek,
        $deliveredTodaySql AS DeliveredToday,

        COALESCE(SUM(CASE WHEN $statusExpr = 'Pending' THEN 1 ELSE 0 END), 0) AS PendingOrders,
        COALESCE(SUM(CASE WHEN $statusExpr = 'Processing' THEN 1 ELSE 0 END), 0) AS ProcessingOrders,
        COALESCE(SUM(CASE WHEN $statusExpr = 'In Production' THEN 1 ELSE 0 END), 0) AS InProductionOrders,
        COALESCE(SUM(CASE WHEN $statusExpr = 'Ready For Shipping' THEN 1 ELSE 0 END), 0) AS ReadyForShippingOrders,
        COALESCE(SUM(CASE WHEN $statusExpr = 'Shipped' THEN 1 ELSE 0 END), 0) AS ShippedOrders,
        COALESCE(SUM(CASE WHEN $statusExpr = 'Out for Delivery' THEN 1 ELSE 0 END), 0) AS OutForDeliveryOrders,
        COALESCE(SUM(CASE WHEN $statusExpr = 'Delivered' THEN 1 ELSE 0 END), 0) AS DeliveredOrders,
        COALESCE(SUM(CASE WHEN $statusExpr = 'Cancelled' THEN 1 ELSE 0 END), 0) AS CancelledOrders
    FROM website_orders
";

$statsResult = mysqli_query($conn, $statsSql);

if (!$statsResult) {
    emptyStatsResponse("Stats error: " . mysqli_error($conn));
}

$stats = mysqli_fetch_assoc($statsResult) ?: [];

/* =========================
   Status distribution
========================= */

$statusData = [];

if ($orderStatusColumn) {
    $statusSql = "
        SELECT 
            $statusExpr AS OrderStatus,
            COUNT(*) AS CountOrders
        FROM website_orders
        GROUP BY $statusExpr
        ORDER BY FIELD(
            $statusExpr,
            'Pending',
            'Processing',
            'In Production',
            'Ready For Shipping',
            'Shipped',
            'Out for Delivery',
            'Delivered',
            'Cancelled'
        )
    ";

    $statusResult = mysqli_query($conn, $statusSql);

    if ($statusResult) {
        while ($row = mysqli_fetch_assoc($statusResult)) {
            $statusData[] = [
                "OrderStatus" => $row["OrderStatus"] ?: "Unknown",
                "CountOrders" => intval($row["CountOrders"] ?? 0)
            ];
        }
    }
}

/* =========================
   Revenue by status
========================= */

$revenueByStatus = [];

if ($orderStatusColumn) {
    $revenueByStatusSql = "
        SELECT 
            $statusExpr AS OrderStatus,
            COALESCE(SUM($totalExpr), 0) AS Revenue
        FROM website_orders
        GROUP BY $statusExpr
        ORDER BY FIELD(
            $statusExpr,
            'Pending',
            'Processing',
            'In Production',
            'Ready For Shipping',
            'Shipped',
            'Out for Delivery',
            'Delivered',
            'Cancelled'
        )
    ";

    $revenueByStatusResult = mysqli_query($conn, $revenueByStatusSql);

    if ($revenueByStatusResult) {
        while ($row = mysqli_fetch_assoc($revenueByStatusResult)) {
            $revenueByStatus[] = [
                "OrderStatus" => $row["OrderStatus"] ?: "Unknown",
                "Revenue" => safeFloat($row["Revenue"] ?? 0)
            ];
        }
    }
}

/* =========================
   Top products
========================= */

$topProducts = [];
$orderItemsTable = getFirstExistingTable($conn, ["website_order_items", "order_items"]);

if ($orderItemsTable) {
    $productNameColumn = getExistingColumn($conn, $orderItemsTable, ["ProductName", "Product", "ItemName", "OptionName"]);
    $quantityColumn = getExistingColumn($conn, $orderItemsTable, ["Quantity", "Qty", "quantity"]);
    $itemTotalColumn = getExistingColumn($conn, $orderItemsTable, ["TotalPrice", "LineTotal", "Subtotal", "Total"]);

    $productNameExpr = $productNameColumn ? "`$productNameColumn`" : "'Product'";
    $quantityExpr = $quantityColumn ? "`$quantityColumn`" : "1";
    $itemTotalExpr = $itemTotalColumn ? "`$itemTotalColumn`" : "0";

    $topProductsSql = "
        SELECT 
            $productNameExpr AS ProductName,
            COALESCE(SUM($quantityExpr), 0) AS TotalQuantity,
            COALESCE(SUM($itemTotalExpr), 0) AS TotalSales
        FROM `$orderItemsTable`
        GROUP BY $productNameExpr
        ORDER BY TotalQuantity DESC
        LIMIT 5
    ";

    $topProductsResult = mysqli_query($conn, $topProductsSql);

    if ($topProductsResult) {
        while ($row = mysqli_fetch_assoc($topProductsResult)) {
            $topProducts[] = [
                "ProductName" => $row["ProductName"] ?: "Product",
                "TotalQuantity" => intval($row["TotalQuantity"] ?? 0),
                "TotalSales" => safeFloat($row["TotalSales"] ?? 0)
            ];
        }
    }
}

if (count($topProducts) === 0) {
    $topProducts[] = [
        "ProductName" => "No products yet",
        "TotalQuantity" => 0,
        "TotalSales" => 0
    ];
}

/* =========================
   Revenue trend - last 7 order days
========================= */

$revenueTrend = [];

if ($dateExpr) {
    $revenueTrendSql = "
        SELECT 
            DATE($dateExpr) AS OrderDay,
            COALESCE(SUM($totalExpr), 0) AS Revenue,
            COUNT(*) AS OrdersCount
        FROM website_orders
        GROUP BY DATE($dateExpr)
        ORDER BY DATE($dateExpr) DESC
        LIMIT 7
    ";

    $trendResult = mysqli_query($conn, $revenueTrendSql);

    if ($trendResult) {
        while ($row = mysqli_fetch_assoc($trendResult)) {
            $revenueTrend[] = [
                "OrderDay" => $row["OrderDay"] ?? "",
                "Revenue" => safeFloat($row["Revenue"] ?? 0),
                "OrdersCount" => intval($row["OrdersCount"] ?? 0)
            ];
        }

        $revenueTrend = array_reverse($revenueTrend);
    }
}

/* =========================
   Business subscription stats
========================= */

$subscriptionStats = [
    "BusinessClients" => 0,
    "IndividualClients" => 0,
    "ActiveSubscriptions" => 0,
    "SubscriptionRevenue" => 0,
    "SmallPlanClients" => 0,
    "GrowthPlanClients" => 0,
    "PremiumPlanClients" => 0
];

if (tableExists($conn, "users")) {
    $roleColumn = getExistingColumn($conn, "users", ["Role", "role"]);
    $accountTypeColumn = getExistingColumn($conn, "users", ["AccountType", "account_type"]);
    $subscriptionPlanColumn = getExistingColumn($conn, "users", ["SubscriptionPlan", "subscription_plan"]);
    $subscriptionStatusColumn = getExistingColumn($conn, "users", ["SubscriptionStatus", "subscription_status"]);
    $subscriptionPriceColumn = getExistingColumn($conn, "users", ["SubscriptionPrice", "subscription_price"]);

    if ($accountTypeColumn && $subscriptionPlanColumn && $subscriptionStatusColumn) {
        $roleFilter = $roleColumn
            ? "WHERE LOWER(`$roleColumn`) = 'customer' OR `$roleColumn` IS NULL OR `$roleColumn` = ''"
            : "";

        if ($subscriptionPriceColumn) {
            $subscriptionRevenueSql = "
                COALESCE(SUM(
                    CASE 
                        WHEN `$accountTypeColumn` = 'business'
                             AND `$subscriptionStatusColumn` = 'active'
                        THEN
                            CASE
                                WHEN `$subscriptionPriceColumn` > 0 THEN `$subscriptionPriceColumn`
                                WHEN `$subscriptionPlanColumn` IN ('small', 'starter') THEN 250
                                WHEN `$subscriptionPlanColumn` = 'growth' THEN 500
                                WHEN `$subscriptionPlanColumn` = 'premium' THEN 900
                                ELSE 0
                            END
                        ELSE 0
                    END
                ), 0)
            ";
        } else {
            $subscriptionRevenueSql = "
                COALESCE(SUM(
                    CASE 
                        WHEN `$accountTypeColumn` = 'business'
                             AND `$subscriptionStatusColumn` = 'active'
                             AND `$subscriptionPlanColumn` IN ('small', 'starter')
                        THEN 250
                        WHEN `$accountTypeColumn` = 'business'
                             AND `$subscriptionStatusColumn` = 'active'
                             AND `$subscriptionPlanColumn` = 'growth'
                        THEN 500
                        WHEN `$accountTypeColumn` = 'business'
                             AND `$subscriptionStatusColumn` = 'active'
                             AND `$subscriptionPlanColumn` = 'premium'
                        THEN 900
                        ELSE 0
                    END
                ), 0)
            ";
        }

        $subscriptionSql = "
            SELECT
                COALESCE(SUM(CASE WHEN `$accountTypeColumn` = 'business' THEN 1 ELSE 0 END), 0) AS BusinessClients,
                COALESCE(SUM(CASE WHEN `$accountTypeColumn` = 'individual' OR `$accountTypeColumn` IS NULL OR `$accountTypeColumn` = '' THEN 1 ELSE 0 END), 0) AS IndividualClients,
                COALESCE(SUM(CASE WHEN `$accountTypeColumn` = 'business' AND `$subscriptionStatusColumn` = 'active' THEN 1 ELSE 0 END), 0) AS ActiveSubscriptions,

                $subscriptionRevenueSql AS SubscriptionRevenue,

                COALESCE(SUM(CASE WHEN `$subscriptionPlanColumn` IN ('small', 'starter') THEN 1 ELSE 0 END), 0) AS SmallPlanClients,
                COALESCE(SUM(CASE WHEN `$subscriptionPlanColumn` = 'growth' THEN 1 ELSE 0 END), 0) AS GrowthPlanClients,
                COALESCE(SUM(CASE WHEN `$subscriptionPlanColumn` = 'premium' THEN 1 ELSE 0 END), 0) AS PremiumPlanClients
            FROM users
            $roleFilter
        ";

        $subscriptionResult = mysqli_query($conn, $subscriptionSql);

        if ($subscriptionResult) {
            $subscriptionStats = mysqli_fetch_assoc($subscriptionResult) ?: $subscriptionStats;
        }
    }
}

/* =========================
   Reviews stats
========================= */

$reviewStats = [
    "TotalReviews" => 0,
    "AverageRating" => 0
];

$ratingDistribution = [];
$reviewsTable = getFirstExistingTable($conn, ["website_reviews", "reviews"]);

if ($reviewsTable) {
    $ratingColumn = getExistingColumn($conn, $reviewsTable, ["Rating", "rating", "Stars", "stars"]);

    if ($ratingColumn) {
        $reviewStatsSql = "
            SELECT
                COUNT(*) AS TotalReviews,
                COALESCE(AVG(`$ratingColumn`), 0) AS AverageRating
            FROM `$reviewsTable`
        ";

        $reviewStatsResult = mysqli_query($conn, $reviewStatsSql);

        if ($reviewStatsResult) {
            $reviewStats = mysqli_fetch_assoc($reviewStatsResult) ?: $reviewStats;
        }

        $ratingDistributionSql = "
            SELECT
                `$ratingColumn` AS Rating,
                COUNT(*) AS CountRatings
            FROM `$reviewsTable`
            GROUP BY `$ratingColumn`
            ORDER BY `$ratingColumn` DESC
        ";

        $ratingDistributionResult = mysqli_query($conn, $ratingDistributionSql);

        if ($ratingDistributionResult) {
            while ($row = mysqli_fetch_assoc($ratingDistributionResult)) {
                $ratingDistribution[] = [
                    "Rating" => intval($row["Rating"] ?? 0),
                    "CountRatings" => intval($row["CountRatings"] ?? 0)
                ];
            }
        }
    }
}

/* =========================
   Final JSON response
========================= */

sendJson([
    "success" => true,
    "message" => "Admin analytics loaded successfully",

    "stats" => [
        "TotalOrders" => intval($stats["TotalOrders"] ?? 0),
        "TotalRevenue" => safeFloat($stats["TotalRevenue"] ?? 0),
        "AverageOrderValue" => safeFloat($stats["AverageOrderValue"] ?? 0),

        "OrdersToday" => intval($stats["OrdersToday"] ?? 0),
        "OrdersThisWeek" => intval($stats["OrdersThisWeek"] ?? 0),

        /*
            OrdersDoneToday kept for frontend cards.
            It means Delivered Today.
        */
        "OrdersDoneToday" => intval($stats["DeliveredToday"] ?? 0),
        "DeliveredToday" => intval($stats["DeliveredToday"] ?? 0),

        "PendingOrders" => intval($stats["PendingOrders"] ?? 0),
        "ProcessingOrders" => intval($stats["ProcessingOrders"] ?? 0),
        "InProductionOrders" => intval($stats["InProductionOrders"] ?? 0),
        "ReadyForShippingOrders" => intval($stats["ReadyForShippingOrders"] ?? 0),
        "ShippedOrders" => intval($stats["ShippedOrders"] ?? 0),
        "OutForDeliveryOrders" => intval($stats["OutForDeliveryOrders"] ?? 0),
        "DeliveredOrders" => intval($stats["DeliveredOrders"] ?? 0),
        "CancelledOrders" => intval($stats["CancelledOrders"] ?? 0)
    ],

    "subscriptionStats" => [
        "BusinessClients" => intval($subscriptionStats["BusinessClients"] ?? 0),
        "IndividualClients" => intval($subscriptionStats["IndividualClients"] ?? 0),
        "ActiveSubscriptions" => intval($subscriptionStats["ActiveSubscriptions"] ?? 0),
        "SubscriptionRevenue" => safeFloat($subscriptionStats["SubscriptionRevenue"] ?? 0),
        "SmallPlanClients" => intval($subscriptionStats["SmallPlanClients"] ?? 0),
        "GrowthPlanClients" => intval($subscriptionStats["GrowthPlanClients"] ?? 0),
        "PremiumPlanClients" => intval($subscriptionStats["PremiumPlanClients"] ?? 0)
    ],

    "reviewStats" => [
        "TotalReviews" => intval($reviewStats["TotalReviews"] ?? 0),
        "AverageRating" => round(floatval($reviewStats["AverageRating"] ?? 0), 1)
    ],

    "statusData" => $statusData,
    "revenueByStatus" => $revenueByStatus,
    "topProducts" => $topProducts,
    "revenueTrend" => $revenueTrend,
    "ratingDistribution" => $ratingDistribution
]);
?>