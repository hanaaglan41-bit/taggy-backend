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

function columnExists($conn, $tableName, $columnName) {
    $tableName = cleanName($tableName);
    $columnName = mysqli_real_escape_string($conn, $columnName);

    $result = mysqli_query($conn, "SHOW COLUMNS FROM `$tableName` LIKE '$columnName'");
    return $result && mysqli_num_rows($result) > 0;
}

function selectColumnOrDefault($conn, $tableName, $columnName, $defaultValue) {
    $safeColumn = cleanName($columnName);

    if (columnExists($conn, $tableName, $safeColumn)) {
        return "`$safeColumn`";
    }

    if (is_numeric($defaultValue)) {
        return $defaultValue . " AS `$safeColumn`";
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

/* =========================
   Admin only protection
========================= */

if (!isset($_SESSION["user"]) || !isset($_SESSION["user"]["UserID"])) {
    sendJson([
        "success" => false,
        "message" => "Please login first",
        "users" => [],
        "count" => 0
    ]);
}

$role = strtolower(trim($_SESSION["user"]["Role"] ?? ""));

if ($role !== "admin") {
    sendJson([
        "success" => false,
        "message" => "Access denied. Admin only can view users.",
        "users" => [],
        "count" => 0
    ]);
}

/* =========================
   Optional filters
   Example:
   get-users.php?search=hana
   get-users.php?accountType=business
   get-users.php?subscriptionStatus=active
========================= */

$search = trim($_GET["search"] ?? "");
$accountTypeFilter = strtolower(trim($_GET["accountType"] ?? "all"));
$subscriptionStatusFilter = strtolower(trim($_GET["subscriptionStatus"] ?? "all"));

$allowedAccountFilters = ["all", "individual", "business"];
$allowedSubscriptionFilters = ["all", "active", "inactive"];

if (!in_array($accountTypeFilter, $allowedAccountFilters, true)) {
    $accountTypeFilter = "all";
}

if (!in_array($subscriptionStatusFilter, $allowedSubscriptionFilters, true)) {
    $subscriptionStatusFilter = "all";
}

/* =========================
   Select columns safely
========================= */

$selectParts = [
    selectColumnOrDefault($conn, "users", "UserID", 0),
    selectColumnOrDefault($conn, "users", "FullName", ""),
    selectColumnOrDefault($conn, "users", "Email", ""),
    selectColumnOrDefault($conn, "users", "Role", "customer"),
    selectColumnOrDefault($conn, "users", "Phone", ""),
    selectColumnOrDefault($conn, "users", "Address", ""),

    selectColumnOrDefault($conn, "users", "AccountType", "individual"),
    selectColumnOrDefault($conn, "users", "CompanyName", ""),
    selectColumnOrDefault($conn, "users", "BusinessType", ""),
    selectColumnOrDefault($conn, "users", "OrderVolume", ""),

    selectColumnOrDefault($conn, "users", "SubscriptionPlan", "none"),
    selectColumnOrDefault($conn, "users", "SubscriptionStatus", "inactive"),
    selectColumnOrDefault($conn, "users", "SubscriptionPrice", 0),
    selectColumnOrDefault($conn, "users", "SubscriptionPaymentMethod", ""),
    selectColumnOrDefault($conn, "users", "SubscriptionPaymentReference", ""),
    selectColumnOrDefault($conn, "users", "SubscriptionStartDate", ""),
    selectColumnOrDefault($conn, "users", "SubscriptionEndDate", "")
];

/* =========================
   Build query
========================= */

$whereParts = [
    "(LOWER(COALESCE(Role, 'customer')) = 'customer' OR Role IS NULL OR Role = '')"
];

$types = "";
$values = [];

if ($search !== "") {
    $whereParts[] = "(
        FullName LIKE ?
        OR Email LIKE ?
        OR CompanyName LIKE ?
        OR BusinessType LIKE ?
    )";

    $searchLike = "%" . $search . "%";

    $types .= "ssss";
    $values[] = $searchLike;
    $values[] = $searchLike;
    $values[] = $searchLike;
    $values[] = $searchLike;
}

if ($accountTypeFilter !== "all" && columnExists($conn, "users", "AccountType")) {
    $whereParts[] = "LOWER(AccountType) = ?";
    $types .= "s";
    $values[] = $accountTypeFilter;
}

if ($subscriptionStatusFilter !== "all" && columnExists($conn, "users", "SubscriptionStatus")) {
    $whereParts[] = "LOWER(SubscriptionStatus) = ?";
    $types .= "s";
    $values[] = $subscriptionStatusFilter;
}

$sql = "
    SELECT 
        " . implode(",\n        ", $selectParts) . "
    FROM users
    WHERE " . implode(" AND ", $whereParts) . "
    ORDER BY UserID DESC
";

$stmt = mysqli_prepare($conn, $sql);

if (!$stmt) {
    sendJson([
        "success" => false,
        "message" => "Database error: " . mysqli_error($conn),
        "users" => [],
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
        "users" => [],
        "count" => 0
    ]);
}

/* =========================
   Format users + summary
========================= */

$users = [];

$summary = [
    "TotalCustomers" => 0,
    "IndividualCustomers" => 0,
    "BusinessCustomers" => 0,
    "ActiveSubscriptions" => 0,
    "InactiveSubscriptions" => 0,
    "MonthlySubscriptionRevenue" => 0,
    "SmallPlanClients" => 0,
    "GrowthPlanClients" => 0,
    "PremiumPlanClients" => 0
];

while ($row = mysqli_fetch_assoc($result)) {
    $userID = intval($row["UserID"] ?? 0);

    $accountType = strtolower(trim($row["AccountType"] ?? "individual"));
    $subscriptionPlan = strtolower(trim($row["SubscriptionPlan"] ?? "none"));
    $subscriptionStatus = strtolower(trim($row["SubscriptionStatus"] ?? "inactive"));

    if ($accountType === "") {
        $accountType = "individual";
    }

    if ($subscriptionPlan === "") {
        $subscriptionPlan = "none";
    }

    if ($subscriptionStatus === "") {
        $subscriptionStatus = "inactive";
    }

    $subscriptionPrice = getPlanPrice($subscriptionPlan, $row["SubscriptionPrice"] ?? 0);
    $discountPercent = getPlanDiscount($subscriptionPlan);

    $isBusiness = $accountType === "business";
    $isActiveSubscription = $isBusiness && $subscriptionStatus === "active";

    $summary["TotalCustomers"]++;

    if ($isBusiness) {
        $summary["BusinessCustomers"]++;
    } else {
        $summary["IndividualCustomers"]++;
    }

    if ($isActiveSubscription) {
        $summary["ActiveSubscriptions"]++;
        $summary["MonthlySubscriptionRevenue"] += $subscriptionPrice;
    } else {
        $summary["InactiveSubscriptions"]++;
    }

    if ($subscriptionPlan === "small" || $subscriptionPlan === "starter") {
        $summary["SmallPlanClients"]++;
    } elseif ($subscriptionPlan === "growth") {
        $summary["GrowthPlanClients"]++;
    } elseif ($subscriptionPlan === "premium") {
        $summary["PremiumPlanClients"]++;
    }

    $users[] = [
        "UserID" => $userID,
        "FullName" => $row["FullName"] ?? "",
        "Email" => $row["Email"] ?? "",
        "Role" => strtolower($row["Role"] ?? "customer"),

        "Phone" => $row["Phone"] ?? "",
        "Address" => $row["Address"] ?? "",

        "AccountType" => $accountType,
        "CompanyName" => $row["CompanyName"] ?? "",
        "BusinessType" => $row["BusinessType"] ?? "",
        "OrderVolume" => $row["OrderVolume"] ?? "",

        "SubscriptionPlan" => $subscriptionPlan,
        "SubscriptionStatus" => $subscriptionStatus,
        "SubscriptionPrice" => floatval($subscriptionPrice),
        "SubscriptionPaymentMethod" => $row["SubscriptionPaymentMethod"] ?? "",
        "SubscriptionPaymentReference" => $row["SubscriptionPaymentReference"] ?? "",
        "SubscriptionStartDate" => $row["SubscriptionStartDate"] ?? "",
        "SubscriptionEndDate" => $row["SubscriptionEndDate"] ?? "",

        "DiscountPercent" => $discountPercent,
        "IsBusiness" => $isBusiness,
        "IsActiveSubscription" => $isActiveSubscription
    ];
}

$summary["MonthlySubscriptionRevenue"] = round(floatval($summary["MonthlySubscriptionRevenue"]), 2);

sendJson([
    "success" => true,
    "message" => "Users loaded successfully",
    "users" => $users,
    "data" => $users,
    "count" => count($users),
    "summary" => $summary
]);
?>