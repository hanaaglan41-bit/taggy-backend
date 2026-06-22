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

function selectColumnOrNull($conn, $tableName, $columnName) {
    $safeColumn = cleanColumnName($columnName);

    if (columnExists($conn, $tableName, $safeColumn)) {
        return "`$safeColumn`";
    }

    return "NULL AS `$safeColumn`";
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

function getPlanLabel($plan) {
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
        "message" => "Please login first"
    ]);
}

$userID = intval($_SESSION["user"]["UserID"] ?? 0);

if ($userID <= 0) {
    sendJson([
        "success" => false,
        "message" => "Invalid session user"
    ]);
}

/* =========================
   Select user profile
========================= */

$columns = [
    "UserID",
    "FullName",
    "Email",
    "Role",
    "Phone",
    "Address",
    "AccountType",
    "CompanyName",
    "BusinessType",
    "OrderVolume",
    "SubscriptionPlan",
    "SubscriptionStatus",
    "SubscriptionPrice",
    "SubscriptionPaymentMethod",
    "SubscriptionPaymentReference",
    "SubscriptionStartDate",
    "SubscriptionEndDate"
];

$selectParts = [];

foreach ($columns as $column) {
    $selectParts[] = selectColumnOrNull($conn, "users", $column);
}

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
        "message" => "User not found"
    ]);
}

/* =========================
   Normalize returned values
========================= */

$user["UserID"] = intval($user["UserID"] ?? 0);
$user["FullName"] = $user["FullName"] ?? "";
$user["Email"] = $user["Email"] ?? "";
$user["Role"] = strtolower(trim($user["Role"] ?? ""));

$user["Phone"] = $user["Phone"] ?? "";
$user["Address"] = $user["Address"] ?? "";

$user["AccountType"] = strtolower(trim($user["AccountType"] ?? "individual"));
$user["CompanyName"] = $user["CompanyName"] ?? "";
$user["BusinessType"] = $user["BusinessType"] ?? "";
$user["OrderVolume"] = $user["OrderVolume"] ?? "";

$user["SubscriptionPlan"] = strtolower(trim($user["SubscriptionPlan"] ?? "none"));
$user["SubscriptionStatus"] = strtolower(trim($user["SubscriptionStatus"] ?? "inactive"));

if ($user["AccountType"] === "") {
    $user["AccountType"] = "individual";
}

if ($user["SubscriptionPlan"] === "") {
    $user["SubscriptionPlan"] = "none";
}

if ($user["SubscriptionStatus"] === "") {
    $user["SubscriptionStatus"] = "inactive";
}

$user["SubscriptionPrice"] = getPlanPrice(
    $user["SubscriptionPlan"],
    $user["SubscriptionPrice"] ?? 0
);

$user["SubscriptionPaymentMethod"] = $user["SubscriptionPaymentMethod"] ?? "";
$user["SubscriptionPaymentReference"] = $user["SubscriptionPaymentReference"] ?? "";
$user["SubscriptionStartDate"] = $user["SubscriptionStartDate"] ?? "";
$user["SubscriptionEndDate"] = $user["SubscriptionEndDate"] ?? "";

$isBusiness = $user["AccountType"] === "business";
$isActive = $user["SubscriptionStatus"] === "active";
$discountPercent = getPlanDiscount($user["SubscriptionPlan"]);
$planLabel = getPlanLabel($user["SubscriptionPlan"]);

/* Update session with latest profile data */
$_SESSION["user"]["FullName"] = $user["FullName"];
$_SESSION["user"]["Email"] = $user["Email"];
$_SESSION["user"]["Role"] = $user["Role"];
$_SESSION["user"]["Phone"] = $user["Phone"];
$_SESSION["user"]["Address"] = $user["Address"];
$_SESSION["user"]["AccountType"] = $user["AccountType"];
$_SESSION["user"]["CompanyName"] = $user["CompanyName"];
$_SESSION["user"]["BusinessType"] = $user["BusinessType"];
$_SESSION["user"]["OrderVolume"] = $user["OrderVolume"];
$_SESSION["user"]["SubscriptionPlan"] = $user["SubscriptionPlan"];
$_SESSION["user"]["SubscriptionStatus"] = $user["SubscriptionStatus"];
$_SESSION["user"]["SubscriptionPrice"] = $user["SubscriptionPrice"];
$_SESSION["user"]["SubscriptionPaymentMethod"] = $user["SubscriptionPaymentMethod"];
$_SESSION["user"]["SubscriptionPaymentReference"] = $user["SubscriptionPaymentReference"];
$_SESSION["user"]["SubscriptionStartDate"] = $user["SubscriptionStartDate"];
$_SESSION["user"]["SubscriptionEndDate"] = $user["SubscriptionEndDate"];

sendJson([
    "success" => true,
    "message" => "Profile loaded successfully",
    "user" => $user,
    "subscription" => [
        "isBusiness" => $isBusiness,
        "isActive" => $isActive,
        "plan" => $user["SubscriptionPlan"],
        "planLabel" => $planLabel,
        "price" => floatval($user["SubscriptionPrice"]),
        "discountPercent" => $discountPercent,
        "paymentMethod" => $user["SubscriptionPaymentMethod"],
        "paymentReference" => $user["SubscriptionPaymentReference"],
        "startDate" => $user["SubscriptionStartDate"],
        "endDate" => $user["SubscriptionEndDate"]
    ]
]);
?>