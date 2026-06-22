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

if ($role !== "customer") {
    sendJson([
        "success" => false,
        "message" => "Only customers can upgrade to a business account"
    ]);
}

$data = json_decode(file_get_contents("php://input"), true);

if (!$data || !is_array($data)) {
    sendJson([
        "success" => false,
        "message" => "Invalid request data"
    ]);
}

$companyName = trim($data["companyName"] ?? $data["CompanyName"] ?? "");
$businessType = trim($data["businessType"] ?? $data["BusinessType"] ?? "");
$orderVolume = trim($data["orderVolume"] ?? $data["OrderVolume"] ?? "");

$subscriptionPlan = strtolower(trim(
    $data["subscriptionPlan"] ??
    $data["SubscriptionPlan"] ??
    $data["plan"] ??
    ""
));

$paymentMethod = trim(
    $data["subscriptionPaymentMethod"] ??
    $data["SubscriptionPaymentMethod"] ??
    $data["paymentMethod"] ??
    ""
);

$paymentReference = trim(
    $data["subscriptionPaymentReference"] ??
    $data["SubscriptionPaymentReference"] ??
    $data["paymentReference"] ??
    ""
);

if (
    $companyName === "" ||
    $businessType === "" ||
    $orderVolume === "" ||
    $subscriptionPlan === "" ||
    $paymentMethod === "" ||
    $paymentReference === ""
) {
    sendJson([
        "success" => false,
        "message" => "Please fill all business upgrade and payment fields"
    ]);
}

/*
    Accept both names:
    - old backend used starter
    - frontend usually sends small
*/
if ($subscriptionPlan === "starter") {
    $subscriptionPlan = "small";
}

$allowedPlans = ["small", "growth", "premium"];

if (!in_array($subscriptionPlan, $allowedPlans, true)) {
    sendJson([
        "success" => false,
        "message" => "Invalid subscription plan"
    ]);
}

$planPrices = [
    "small" => 250,
    "growth" => 500,
    "premium" => 900
];

$subscriptionPrice = $planPrices[$subscriptionPlan];

$allowedPaymentMethods = [
    "Vodafone Cash",
    "Bank Transfer",
    "InstaPay",
    "Card"
];

$normalizedPaymentMethods = array_map("strtolower", $allowedPaymentMethods);
$paymentMethodLower = strtolower($paymentMethod);

if (!in_array($paymentMethodLower, $normalizedPaymentMethods, true)) {
    sendJson([
        "success" => false,
        "message" => "Invalid payment method"
    ]);
}

/* Return payment method in the clean official format */
foreach ($allowedPaymentMethods as $method) {
    if (strtolower($method) === $paymentMethodLower) {
        $paymentMethod = $method;
        break;
    }
}

if (strlen($paymentReference) < 3) {
    sendJson([
        "success" => false,
        "message" => "Payment reference must be at least 3 characters"
    ]);
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

function addUpdateColumn(&$setParts, &$types, &$values, $conn, $columnName, $value, $type) {
    if (columnExists($conn, "users", $columnName)) {
        $safeColumn = cleanColumnName($columnName);
        $setParts[] = "`$safeColumn` = ?";
        $types .= $type;
        $values[] = $value;
    }
}

$setParts = [];
$types = "";
$values = [];

$startDate = date("Y-m-d");
$endDate = date("Y-m-d", strtotime("+1 month"));

addUpdateColumn($setParts, $types, $values, $conn, "AccountType", "business", "s");
addUpdateColumn($setParts, $types, $values, $conn, "CompanyName", $companyName, "s");
addUpdateColumn($setParts, $types, $values, $conn, "BusinessType", $businessType, "s");
addUpdateColumn($setParts, $types, $values, $conn, "OrderVolume", $orderVolume, "s");
addUpdateColumn($setParts, $types, $values, $conn, "SubscriptionPlan", $subscriptionPlan, "s");
addUpdateColumn($setParts, $types, $values, $conn, "SubscriptionStatus", "active", "s");
addUpdateColumn($setParts, $types, $values, $conn, "SubscriptionPrice", $subscriptionPrice, "d");
addUpdateColumn($setParts, $types, $values, $conn, "SubscriptionPaymentMethod", $paymentMethod, "s");
addUpdateColumn($setParts, $types, $values, $conn, "SubscriptionPaymentReference", $paymentReference, "s");
addUpdateColumn($setParts, $types, $values, $conn, "SubscriptionStartDate", $startDate, "s");
addUpdateColumn($setParts, $types, $values, $conn, "SubscriptionEndDate", $endDate, "s");

if (count($setParts) === 0) {
    sendJson([
        "success" => false,
        "message" => "No valid user columns found to update"
    ]);
}

$sql = "
    UPDATE users
    SET " . implode(", ", $setParts) . "
    WHERE UserID = ?
";

$types .= "i";
$values[] = $userID;

$stmt = mysqli_prepare($conn, $sql);

if (!$stmt) {
    sendJson([
        "success" => false,
        "message" => "Database error: " . mysqli_error($conn)
    ]);
}

/*
    bind_param needs references.
    This keeps it safe with a dynamic number of columns.
*/
$bindParams = [];
$bindParams[] = $types;

foreach ($values as $key => $value) {
    $bindParams[] = &$values[$key];
}

call_user_func_array([$stmt, "bind_param"], $bindParams);

if (!mysqli_stmt_execute($stmt)) {
    sendJson([
        "success" => false,
        "message" => "Upgrade failed: " . mysqli_stmt_error($stmt)
    ]);
}

$_SESSION["user"]["AccountType"] = "business";
$_SESSION["user"]["CompanyName"] = $companyName;
$_SESSION["user"]["BusinessType"] = $businessType;
$_SESSION["user"]["OrderVolume"] = $orderVolume;
$_SESSION["user"]["SubscriptionPlan"] = $subscriptionPlan;
$_SESSION["user"]["SubscriptionStatus"] = "active";
$_SESSION["user"]["SubscriptionPrice"] = $subscriptionPrice;
$_SESSION["user"]["SubscriptionPaymentMethod"] = $paymentMethod;
$_SESSION["user"]["SubscriptionPaymentReference"] = $paymentReference;
$_SESSION["user"]["SubscriptionStartDate"] = $startDate;
$_SESSION["user"]["SubscriptionEndDate"] = $endDate;

sendJson([
    "success" => true,
    "message" => "Business account activated successfully",
    "user" => $_SESSION["user"],
    "subscription" => [
        "plan" => $subscriptionPlan,
        "status" => "active",
        "price" => $subscriptionPrice,
        "paymentMethod" => $paymentMethod,
        "paymentReference" => $paymentReference,
        "startDate" => $startDate,
        "endDate" => $endDate
    ]
]);
?>