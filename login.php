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

$data = json_decode(file_get_contents("php://input"), true);

if (!$data || !is_array($data)) {
    sendJson([
        "success" => false,
        "message" => "No login data received"
    ]);
}

$email = trim($data["email"] ?? $data["Email"] ?? "");
$password = trim($data["password"] ?? $data["Password"] ?? "");
$role = strtolower(trim($data["role"] ?? $data["Role"] ?? ""));

if ($email === "" || $password === "" || $role === "") {
    sendJson([
        "success" => false,
        "message" => "Please fill all fields"
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

$allowedRoles = ["customer", "admin", "supplier", "delivery"];

if (!in_array($role, $allowedRoles, true)) {
    sendJson([
        "success" => false,
        "message" => "Invalid role"
    ]);
}

/*
    Required columns:
    UserID, FullName, Email, Password, Role

    Optional columns:
    AccountType, CompanyName, BusinessType, OrderVolume,
    SubscriptionPlan, SubscriptionStatus, SubscriptionPrice,
    SubscriptionPaymentMethod, SubscriptionPaymentReference,
    SubscriptionStartDate, SubscriptionEndDate, Phone, Address
*/

$selectColumns = [
    "`UserID`",
    "`FullName`",
    "`Email`",
    "`Password`",
    "`Role`",
    selectColumnOrNull($conn, "users", "Phone"),
    selectColumnOrNull($conn, "users", "Address"),
    selectColumnOrNull($conn, "users", "AccountType"),
    selectColumnOrNull($conn, "users", "CompanyName"),
    selectColumnOrNull($conn, "users", "BusinessType"),
    selectColumnOrNull($conn, "users", "OrderVolume"),
    selectColumnOrNull($conn, "users", "SubscriptionPlan"),
    selectColumnOrNull($conn, "users", "SubscriptionStatus"),
    selectColumnOrNull($conn, "users", "SubscriptionPrice"),
    selectColumnOrNull($conn, "users", "SubscriptionPaymentMethod"),
    selectColumnOrNull($conn, "users", "SubscriptionPaymentReference"),
    selectColumnOrNull($conn, "users", "SubscriptionStartDate"),
    selectColumnOrNull($conn, "users", "SubscriptionEndDate")
];

$sql = "
    SELECT 
        " . implode(",\n        ", $selectColumns) . "
    FROM users
    WHERE Email = ?
    AND LOWER(Role) = ?
    LIMIT 1
";

$stmt = mysqli_prepare($conn, $sql);

if (!$stmt) {
    sendJson([
        "success" => false,
        "message" => "Database error: " . mysqli_error($conn)
    ]);
}

mysqli_stmt_bind_param($stmt, "ss", $email, $role);
mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

if (!$result || mysqli_num_rows($result) === 0) {
    sendJson([
        "success" => false,
        "message" => "Invalid email, password, or role"
    ]);
}

$user = mysqli_fetch_assoc($result);
$dbPassword = $user["Password"] ?? "";

if (!password_verify($password, $dbPassword)) {
    sendJson([
        "success" => false,
        "message" => "Invalid email, password, or role"
    ]);
}

/* Secure session after successful login */
session_regenerate_id(true);

$sessionUser = [
    "UserID" => intval($user["UserID"] ?? 0),
    "FullName" => $user["FullName"] ?? "",
    "Email" => $user["Email"] ?? "",
    "Role" => strtolower($user["Role"] ?? "customer"),

    "Phone" => $user["Phone"] ?? "",
    "Address" => $user["Address"] ?? "",

    "AccountType" => $user["AccountType"] ?: "individual",
    "CompanyName" => $user["CompanyName"] ?? "",
    "BusinessType" => $user["BusinessType"] ?? "",
    "OrderVolume" => $user["OrderVolume"] ?? "",

    "SubscriptionPlan" => $user["SubscriptionPlan"] ?: "none",
    "SubscriptionStatus" => $user["SubscriptionStatus"] ?: "inactive",
    "SubscriptionPrice" => floatval($user["SubscriptionPrice"] ?? 0),
    "SubscriptionPaymentMethod" => $user["SubscriptionPaymentMethod"] ?? "",
    "SubscriptionPaymentReference" => $user["SubscriptionPaymentReference"] ?? "",
    "SubscriptionStartDate" => $user["SubscriptionStartDate"] ?? null,
    "SubscriptionEndDate" => $user["SubscriptionEndDate"] ?? null
];

$_SESSION["user"] = $sessionUser;

sendJson([
    "success" => true,
    "message" => "Login successful",
    "role" => $sessionUser["Role"],
    "user" => $sessionUser
]);
?>