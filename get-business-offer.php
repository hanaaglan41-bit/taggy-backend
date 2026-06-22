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

function cleanColumnName($columnName) {
    return preg_replace('/[^A-Za-z0-9_]/', '', $columnName);
}

function columnExists($conn, $tableName, $columnName) {
    $tableName = preg_replace('/[^A-Za-z0-9_]/', '', $tableName);
    $columnName = mysqli_real_escape_string($conn, $columnName);

    $result = mysqli_query($conn, "SHOW COLUMNS FROM `$tableName` LIKE '$columnName'");
    return $result && mysqli_num_rows($result) > 0;
}

function selectColumnOrDefault($conn, $tableName, $columnName, $defaultValue) {
    $safeColumn = cleanColumnName($columnName);

    if (columnExists($conn, $tableName, $safeColumn)) {
        return "`$safeColumn`";
    }

    if (is_numeric($defaultValue)) {
        return $defaultValue . " AS `$safeColumn`";
    }

    if ($defaultValue === null) {
        return "NULL AS `$safeColumn`";
    }

    $escapedDefault = mysqli_real_escape_string($conn, $defaultValue);
    return "'$escapedDefault' AS `$safeColumn`";
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

function getPlanDiscount($plan) {
    $plan = strtolower(trim($plan));

    if ($plan === "small" || $plan === "starter") {
        return 5;
    }

    if ($plan === "growth") {
        return 10;
    }

    if ($plan === "premium") {
        return 15;
    }

    return 0;
}

function getPlanName($plan) {
    $plan = strtolower(trim($plan));

    if ($plan === "small" || $plan === "starter") {
        return "Small Plan";
    }

    if ($plan === "growth") {
        return "Growth Plan";
    }

    if ($plan === "premium") {
        return "Premium Plan";
    }

    return "No Business Offer";
}

/* =========================
   Login protection
========================= */

if (!isset($_SESSION["user"]) || !isset($_SESSION["user"]["UserID"])) {
    sendJson([
        "success" => false,
        "hasBusinessOffer" => false,
        "discountPercent" => 0,
        "planName" => "No Business Offer",
        "message" => "Not logged in"
    ]);
}

$userID = intval($_SESSION["user"]["UserID"] ?? 0);
$role = strtolower(trim($_SESSION["user"]["Role"] ?? ""));

if ($userID <= 0) {
    sendJson([
        "success" => false,
        "hasBusinessOffer" => false,
        "discountPercent" => 0,
        "planName" => "No Business Offer",
        "message" => "Invalid session user"
    ]);
}

/*
   Business offers are for customers only.
   Admin / supplier / delivery do not get checkout discounts.
*/
if ($role !== "customer") {
    sendJson([
        "success" => true,
        "hasBusinessOffer" => false,
        "accountType" => "non-customer",
        "subscriptionPlan" => "none",
        "subscriptionStatus" => "inactive",
        "subscriptionPrice" => 0,
        "subscriptionPaymentMethod" => "",
        "subscriptionPaymentReference" => "",
        "subscriptionStartDate" => null,
        "subscriptionEndDate" => null,
        "planName" => "No Business Offer",
        "discountPercent" => 0,
        "message" => "Business discounts are available for customers only"
    ]);
}

/* =========================
   Get subscription data
========================= */

$selectParts = [
    selectColumnOrDefault($conn, "users", "AccountType", "individual"),
    selectColumnOrDefault($conn, "users", "SubscriptionPlan", "none"),
    selectColumnOrDefault($conn, "users", "SubscriptionStatus", "inactive"),
    selectColumnOrDefault($conn, "users", "SubscriptionPrice", 0),
    selectColumnOrDefault($conn, "users", "SubscriptionPaymentMethod", ""),
    selectColumnOrDefault($conn, "users", "SubscriptionPaymentReference", ""),
    selectColumnOrDefault($conn, "users", "SubscriptionStartDate", null),
    selectColumnOrDefault($conn, "users", "SubscriptionEndDate", null)
];

$sql = "
    SELECT 
        " . implode(",\n        ", $selectParts) . "
    FROM users
    WHERE UserID = ?
    LIMIT 1
";

$stmt = mysqli_prepare($conn, $sql);

if (!$stmt) {
    sendJson([
        "success" => false,
        "hasBusinessOffer" => false,
        "discountPercent" => 0,
        "planName" => "No Business Offer",
        "message" => "Database error: " . mysqli_error($conn)
    ]);
}

mysqli_stmt_bind_param($stmt, "i", $userID);
mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);
$user = mysqli_fetch_assoc($result);

if (!$user) {
    sendJson([
        "success" => false,
        "hasBusinessOffer" => false,
        "discountPercent" => 0,
        "planName" => "No Business Offer",
        "message" => "User not found"
    ]);
}

/* =========================
   Normalize data
========================= */

$accountType = strtolower(trim($user["AccountType"] ?? "individual"));
$subscriptionPlan = strtolower(trim($user["SubscriptionPlan"] ?? "none"));
$subscriptionStatus = strtolower(trim($user["SubscriptionStatus"] ?? "inactive"));
$subscriptionEndDate = $user["SubscriptionEndDate"] ?? null;
$subscriptionStartDate = $user["SubscriptionStartDate"] ?? null;

if ($subscriptionPlan === "starter") {
    $subscriptionPlan = "small";
}

if ($accountType === "") {
    $accountType = "individual";
}

if ($subscriptionPlan === "") {
    $subscriptionPlan = "none";
}

if ($subscriptionStatus === "") {
    $subscriptionStatus = "inactive";
}

$subscriptionPrice = getPlanPrice($subscriptionPlan, $user["SubscriptionPrice"] ?? 0);
$discountPercent = 0;
$planName = "No Business Offer";
$message = "No active business subscription";
$hasBusinessOffer = false;
$isExpired = false;

/* =========================
   Expiration check
========================= */

if (
    $accountType === "business" &&
    $subscriptionStatus === "active" &&
    $subscriptionEndDate &&
    strtotime($subscriptionEndDate) < strtotime(date("Y-m-d"))
) {
    $isExpired = true;
    $subscriptionStatus = "expired";

    /*
       Optional: update DB status to expired if column exists.
    */
    if (columnExists($conn, "users", "SubscriptionStatus")) {
        $expireStmt = mysqli_prepare($conn, "
            UPDATE users
            SET SubscriptionStatus = 'expired'
            WHERE UserID = ?
        ");

        if ($expireStmt) {
            mysqli_stmt_bind_param($expireStmt, "i", $userID);
            mysqli_stmt_execute($expireStmt);
        }
    }

    $_SESSION["user"]["SubscriptionStatus"] = "expired";

    sendJson([
        "success" => true,
        "hasBusinessOffer" => false,
        "isExpired" => true,
        "accountType" => $accountType,
        "subscriptionPlan" => $subscriptionPlan,
        "subscriptionStatus" => "expired",
        "subscriptionPrice" => floatval($subscriptionPrice),
        "subscriptionPaymentMethod" => $user["SubscriptionPaymentMethod"] ?? "",
        "subscriptionPaymentReference" => $user["SubscriptionPaymentReference"] ?? "",
        "subscriptionStartDate" => $subscriptionStartDate,
        "subscriptionEndDate" => $subscriptionEndDate,
        "planName" => "Subscription Expired",
        "discountPercent" => 0,
        "message" => "Business subscription has expired"
    ]);
}

/* =========================
   Active business discount
========================= */

if ($accountType === "business" && $subscriptionStatus === "active") {
    $discountPercent = getPlanDiscount($subscriptionPlan);
    $planName = getPlanName($subscriptionPlan);

    if ($discountPercent > 0) {
        $hasBusinessOffer = true;
        $message = $planName . " discount applied";
    }
}

/* =========================
   Update session cache
========================= */

$_SESSION["user"]["AccountType"] = $accountType;
$_SESSION["user"]["SubscriptionPlan"] = $subscriptionPlan;
$_SESSION["user"]["SubscriptionStatus"] = $subscriptionStatus;
$_SESSION["user"]["SubscriptionPrice"] = $subscriptionPrice;
$_SESSION["user"]["SubscriptionPaymentMethod"] = $user["SubscriptionPaymentMethod"] ?? "";
$_SESSION["user"]["SubscriptionPaymentReference"] = $user["SubscriptionPaymentReference"] ?? "";
$_SESSION["user"]["SubscriptionStartDate"] = $subscriptionStartDate;
$_SESSION["user"]["SubscriptionEndDate"] = $subscriptionEndDate;

/* =========================
   Final response
========================= */

sendJson([
    "success" => true,
    "hasBusinessOffer" => $hasBusinessOffer,
    "isExpired" => $isExpired,

    "accountType" => $accountType,
    "subscriptionPlan" => $subscriptionPlan,
    "subscriptionStatus" => $subscriptionStatus,
    "subscriptionPrice" => floatval($subscriptionPrice),
    "subscriptionPaymentMethod" => $user["SubscriptionPaymentMethod"] ?? "",
    "subscriptionPaymentReference" => $user["SubscriptionPaymentReference"] ?? "",
    "subscriptionStartDate" => $subscriptionStartDate,
    "subscriptionEndDate" => $subscriptionEndDate,

    "planName" => $planName,
    "discountPercent" => $discountPercent,
    "message" => $message
]);
?>