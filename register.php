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

function addInsertColumn(&$columns, &$placeholders, &$types, &$values, $conn, $columnName, $value, $type) {
    if (columnExists($conn, "users", $columnName)) {
        $safeColumn = cleanColumnName($columnName);
        $columns[] = "`$safeColumn`";
        $placeholders[] = "?";
        $types .= $type;
        $values[] = $value;
    }
}

function getSubscriptionDetails($plan) {
    if ($plan === "starter") {
        $plan = "small";
    }

    if ($plan === "small") {
        return [
            "price" => 250,
            "discount" => 5,
            "name" => "Small Plan"
        ];
    }

    if ($plan === "growth") {
        return [
            "price" => 500,
            "discount" => 10,
            "name" => "Growth Plan"
        ];
    }

    if ($plan === "premium") {
        return [
            "price" => 900,
            "discount" => 15,
            "name" => "Premium Plan"
        ];
    }

    return null;
}

$data = json_decode(file_get_contents("php://input"), true);

if (!$data || !is_array($data)) {
    sendJson([
        "success" => false,
        "message" => "No registration data received"
    ]);
}

$name = trim($data["name"] ?? $data["FullName"] ?? "");
$email = trim($data["email"] ?? $data["Email"] ?? "");
$password = trim($data["password"] ?? $data["Password"] ?? "");
$confirmPassword = trim($data["confirmPassword"] ?? $data["ConfirmPassword"] ?? "");

$accountType = strtolower(trim($data["accountType"] ?? $data["AccountType"] ?? "individual"));
$companyName = trim($data["companyName"] ?? $data["CompanyName"] ?? "");
$businessType = trim($data["businessType"] ?? $data["BusinessType"] ?? "");
$orderVolume = trim($data["orderVolume"] ?? $data["OrderVolume"] ?? "");

$subscriptionPlan = strtolower(trim(
    $data["subscriptionPlan"] ??
    $data["SubscriptionPlan"] ??
    "none"
));

$subscriptionPaymentMethod = trim(
    $data["subscriptionPaymentMethod"] ??
    $data["SubscriptionPaymentMethod"] ??
    $data["paymentMethod"] ??
    ""
);

$subscriptionPaymentReference = trim(
    $data["subscriptionPaymentReference"] ??
    $data["SubscriptionPaymentReference"] ??
    $data["paymentReference"] ??
    ""
);

$subscriptionStatus = "inactive";
$subscriptionPrice = 0;
$subscriptionStartDate = null;
$subscriptionEndDate = null;

/* Basic validation */
if ($name === "" || $email === "" || $password === "") {
    sendJson([
        "success" => false,
        "message" => "Please fill all required fields"
    ]);
}

if (function_exists("mb_strlen")) {
    $nameLength = mb_strlen($name, "UTF-8");
} else {
    $nameLength = strlen($name);
}

if ($nameLength < 3 || $nameLength > 100) {
    sendJson([
        "success" => false,
        "message" => "Name must be between 3 and 100 characters"
    ]);
}

/*
   Allows English names, Arabic names, spaces, dots, hyphens, and apostrophes.
*/
if (!preg_match("/^[\p{L}\s.'-]+$/u", $name)) {
    sendJson([
        "success" => false,
        "message" => "Name must contain letters and spaces only"
    ]);
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    sendJson([
        "success" => false,
        "message" => "Invalid email format"
    ]);
}

if (strlen($email) > 255) {
    sendJson([
        "success" => false,
        "message" => "Email is too long"
    ]);
}

if ($confirmPassword !== "" && $password !== $confirmPassword) {
    sendJson([
        "success" => false,
        "message" => "Password and confirmation do not match"
    ]);
}

if (
    strlen($password) < 8 ||
    !preg_match('/[A-Z]/', $password) ||
    !preg_match('/[a-z]/', $password) ||
    !preg_match('/[0-9]/', $password) ||
    !preg_match('/[^A-Za-z0-9]/', $password)
) {
    sendJson([
        "success" => false,
        "message" => "Password must be at least 8 characters and include uppercase, lowercase, number, and special character"
    ]);
}

/* Account type */
if (!in_array($accountType, ["individual", "business"], true)) {
    sendJson([
        "success" => false,
        "message" => "Invalid account type"
    ]);
}

$allowedOrderVolumes = [
    "1-50",
    "51-100",
    "101-500",
    "500+"
];

if ($accountType === "individual") {
    $companyName = "";
    $businessType = "";
    $orderVolume = "";
    $subscriptionPlan = "none";
    $subscriptionStatus = "inactive";
    $subscriptionPrice = 0;
    $subscriptionPaymentMethod = "";
    $subscriptionPaymentReference = "";
    $subscriptionStartDate = null;
    $subscriptionEndDate = null;
}

if ($subscriptionPlan === "starter") {
    $subscriptionPlan = "small";
}

$allowedSubscriptionPlans = [
    "none",
    "small",
    "growth",
    "premium"
];

if (!in_array($subscriptionPlan, $allowedSubscriptionPlans, true)) {
    sendJson([
        "success" => false,
        "message" => "Invalid subscription plan"
    ]);
}

$allowedSubscriptionPaymentMethods = [
    "Vodafone Cash",
    "Bank Transfer",
    "InstaPay",
    "Card"
];

if ($accountType === "business") {
    if (
        $companyName === "" ||
        $businessType === "" ||
        $orderVolume === "" ||
        $subscriptionPlan === "" ||
        $subscriptionPlan === "none" ||
        $subscriptionPaymentMethod === "" ||
        $subscriptionPaymentReference === ""
    ) {
        sendJson([
            "success" => false,
            "message" => "Please fill all business account and subscription payment fields"
        ]);
    }

    if (function_exists("mb_strlen")) {
        $companyNameLength = mb_strlen($companyName, "UTF-8");
        $businessTypeLength = mb_strlen($businessType, "UTF-8");
    } else {
        $companyNameLength = strlen($companyName);
        $businessTypeLength = strlen($businessType);
    }

    if ($companyNameLength < 2 || $companyNameLength > 150) {
        sendJson([
            "success" => false,
            "message" => "Company name must be between 2 and 150 characters"
        ]);
    }

    if ($businessTypeLength < 2 || $businessTypeLength > 100) {
        sendJson([
            "success" => false,
            "message" => "Business type must be between 2 and 100 characters"
        ]);
    }

    if (!in_array($orderVolume, $allowedOrderVolumes, true)) {
        sendJson([
            "success" => false,
            "message" => "Invalid order volume"
        ]);
    }

    $normalizedPaymentMethods = array_map("strtolower", $allowedSubscriptionPaymentMethods);
    $paymentMethodLower = strtolower($subscriptionPaymentMethod);

    if (!in_array($paymentMethodLower, $normalizedPaymentMethods, true)) {
        sendJson([
            "success" => false,
            "message" => "Invalid subscription payment method"
        ]);
    }

    foreach ($allowedSubscriptionPaymentMethods as $method) {
        if (strtolower($method) === $paymentMethodLower) {
            $subscriptionPaymentMethod = $method;
            break;
        }
    }

    if (strlen($subscriptionPaymentReference) < 3 || strlen($subscriptionPaymentReference) > 150) {
        sendJson([
            "success" => false,
            "message" => "Invalid payment reference number"
        ]);
    }

    $planDetails = getSubscriptionDetails($subscriptionPlan);

    if (!$planDetails) {
        sendJson([
            "success" => false,
            "message" => "Invalid subscription plan selected"
        ]);
    }

    $subscriptionStatus = "active";
    $subscriptionPrice = floatval($planDetails["price"]);
    $subscriptionStartDate = date("Y-m-d");
    $subscriptionEndDate = date("Y-m-d", strtotime("+1 month"));

} else {
    $companyName = "";
    $businessType = "";
    $orderVolume = "";
    $subscriptionPlan = "none";
    $subscriptionStatus = "inactive";
    $subscriptionPrice = 0;
    $subscriptionPaymentMethod = "";
    $subscriptionPaymentReference = "";
    $subscriptionStartDate = null;
    $subscriptionEndDate = null;
}

/* Check duplicate email */
$checkSql = "
    SELECT UserID
    FROM users
    WHERE Email = ?
    LIMIT 1
";

$checkStmt = mysqli_prepare($conn, $checkSql);

if (!$checkStmt) {
    sendJson([
        "success" => false,
        "message" => "Database error: " . mysqli_error($conn)
    ]);
}

mysqli_stmt_bind_param($checkStmt, "s", $email);
mysqli_stmt_execute($checkStmt);

$checkResult = mysqli_stmt_get_result($checkStmt);

if ($checkResult && mysqli_num_rows($checkResult) > 0) {
    sendJson([
        "success" => false,
        "message" => "Email already exists"
    ]);
}

$hashedPassword = password_hash($password, PASSWORD_DEFAULT);

/* Build insert dynamically */
$columns = [];
$placeholders = [];
$types = "";
$values = [];

addInsertColumn($columns, $placeholders, $types, $values, $conn, "FullName", $name, "s");
addInsertColumn($columns, $placeholders, $types, $values, $conn, "Email", $email, "s");
addInsertColumn($columns, $placeholders, $types, $values, $conn, "Password", $hashedPassword, "s");
addInsertColumn($columns, $placeholders, $types, $values, $conn, "Role", "customer", "s");
addInsertColumn($columns, $placeholders, $types, $values, $conn, "AccountType", $accountType, "s");
addInsertColumn($columns, $placeholders, $types, $values, $conn, "CompanyName", $companyName, "s");
addInsertColumn($columns, $placeholders, $types, $values, $conn, "BusinessType", $businessType, "s");
addInsertColumn($columns, $placeholders, $types, $values, $conn, "OrderVolume", $orderVolume, "s");
addInsertColumn($columns, $placeholders, $types, $values, $conn, "SubscriptionPlan", $subscriptionPlan, "s");
addInsertColumn($columns, $placeholders, $types, $values, $conn, "SubscriptionStatus", $subscriptionStatus, "s");
addInsertColumn($columns, $placeholders, $types, $values, $conn, "SubscriptionPrice", $subscriptionPrice, "d");
addInsertColumn($columns, $placeholders, $types, $values, $conn, "SubscriptionPaymentMethod", $subscriptionPaymentMethod, "s");
addInsertColumn($columns, $placeholders, $types, $values, $conn, "SubscriptionPaymentReference", $subscriptionPaymentReference, "s");
addInsertColumn($columns, $placeholders, $types, $values, $conn, "SubscriptionStartDate", $subscriptionStartDate, "s");
addInsertColumn($columns, $placeholders, $types, $values, $conn, "SubscriptionEndDate", $subscriptionEndDate, "s");

if (
    !in_array("`FullName`", $columns, true) ||
    !in_array("`Email`", $columns, true) ||
    !in_array("`Password`", $columns, true)
) {
    sendJson([
        "success" => false,
        "message" => "Required user columns are missing from database"
    ]);
}

$sql = "
    INSERT INTO users
    (" . implode(", ", $columns) . ")
    VALUES (" . implode(", ", $placeholders) . ")
";

$stmt = mysqli_prepare($conn, $sql);

if (!$stmt) {
    sendJson([
        "success" => false,
        "message" => "Database error: " . mysqli_error($conn)
    ]);
}

/*
   Dynamic bind_param needs references.
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
        "message" => "Registration failed: " . mysqli_stmt_error($stmt)
    ]);
}

$newUserID = mysqli_insert_id($conn);

sendJson([
    "success" => true,
    "message" => "Account created successfully",
    "UserID" => $newUserID,
    "user" => [
        "UserID" => $newUserID,
        "FullName" => $name,
        "Email" => $email,
        "Role" => "customer",
        "AccountType" => $accountType,
        "SubscriptionPlan" => $subscriptionPlan,
        "SubscriptionStatus" => $subscriptionStatus
    ]
]);
?>