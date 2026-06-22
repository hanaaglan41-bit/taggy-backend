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

function addUpdateColumn(&$setParts, &$types, &$values, $conn, $columnName, $value, $type) {
    if (columnExists($conn, "users", $columnName)) {
        $safeColumn = cleanColumnName($columnName);
        $setParts[] = "`$safeColumn` = ?";
        $types .= $type;
        $values[] = $value;
    }
}

if (!isset($_SESSION["user"]) || !isset($_SESSION["user"]["UserID"])) {
    sendJson([
        "success" => false,
        "message" => "Not logged in"
    ]);
}

$userID = intval($_SESSION["user"]["UserID"] ?? 0);

if ($userID <= 0) {
    sendJson([
        "success" => false,
        "message" => "Invalid session user"
    ]);
}

$data = json_decode(file_get_contents("php://input"), true);

if (!$data || !is_array($data)) {
    sendJson([
        "success" => false,
        "message" => "Invalid request data"
    ]);
}

$name = trim($data["name"] ?? $data["FullName"] ?? "");
$email = trim($data["email"] ?? $data["Email"] ?? "");

$password = trim(
    $data["password"] ??
    $data["newPassword"] ??
    $data["NewPassword"] ??
    ""
);

$confirmPassword = trim(
    $data["confirmPassword"] ??
    $data["ConfirmPassword"] ??
    $data["confirmNewPassword"] ??
    ""
);

$phoneWasSent =
    array_key_exists("phone", $data) ||
    array_key_exists("Phone", $data);

$addressWasSent =
    array_key_exists("address", $data) ||
    array_key_exists("Address", $data);

$phone = trim($data["phone"] ?? $data["Phone"] ?? "");
$address = trim($data["address"] ?? $data["Address"] ?? "");

if ($name === "" || $email === "") {
    sendJson([
        "success" => false,
        "message" => "Name and email are required"
    ]);
}

if (mb_strlen($name, "UTF-8") < 3 || mb_strlen($name, "UTF-8") > 100) {
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

if ($phoneWasSent && $phone !== "") {
    if (!preg_match('/^01[0125][0-9]{8}$/', $phone)) {
        sendJson([
            "success" => false,
            "message" => "Please enter a valid Egyptian phone number"
        ]);
    }
}

if ($addressWasSent && mb_strlen($address, "UTF-8") > 500) {
    sendJson([
        "success" => false,
        "message" => "Address is too long"
    ]);
}

$passwordWillChange = false;
$hashedPassword = "";

if ($password !== "") {
    if ($confirmPassword !== "" && $password !== $confirmPassword) {
        sendJson([
            "success" => false,
            "message" => "Password and confirmation do not match"
        ]);
    }

    $hasMinLength = strlen($password) >= 8;
    $hasUppercase = preg_match('/[A-Z]/', $password);
    $hasLowercase = preg_match('/[a-z]/', $password);
    $hasNumber = preg_match('/[0-9]/', $password);
    $hasSpecial = preg_match('/[^A-Za-z0-9]/', $password);

    if (!$hasMinLength || !$hasUppercase || !$hasLowercase || !$hasNumber || !$hasSpecial) {
        sendJson([
            "success" => false,
            "message" => "Password must be at least 8 characters and include uppercase, lowercase, number, and special character"
        ]);
    }

    $passwordWillChange = true;
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
}

/* Check email duplicate */
$checkSql = "
    SELECT UserID
    FROM users
    WHERE Email = ?
    AND UserID != ?
    LIMIT 1
";

$checkStmt = mysqli_prepare($conn, $checkSql);

if (!$checkStmt) {
    sendJson([
        "success" => false,
        "message" => "Database error: " . mysqli_error($conn)
    ]);
}

mysqli_stmt_bind_param($checkStmt, "si", $email, $userID);
mysqli_stmt_execute($checkStmt);

$checkResult = mysqli_stmt_get_result($checkStmt);

if ($checkResult && mysqli_num_rows($checkResult) > 0) {
    sendJson([
        "success" => false,
        "message" => "This email is already used by another account"
    ]);
}

/* Build dynamic update */
$setParts = [];
$types = "";
$values = [];

addUpdateColumn($setParts, $types, $values, $conn, "FullName", $name, "s");
addUpdateColumn($setParts, $types, $values, $conn, "Email", $email, "s");

if ($phoneWasSent) {
    addUpdateColumn($setParts, $types, $values, $conn, "Phone", $phone, "s");
}

if ($addressWasSent) {
    addUpdateColumn($setParts, $types, $values, $conn, "Address", $address, "s");
}

if ($passwordWillChange) {
    addUpdateColumn($setParts, $types, $values, $conn, "Password", $hashedPassword, "s");
}

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
        "message" => "Update failed: " . mysqli_stmt_error($stmt)
    ]);
}

if ($passwordWillChange) {
    session_regenerate_id(true);
}

$_SESSION["user"]["FullName"] = $name;
$_SESSION["user"]["Email"] = $email;

if ($phoneWasSent) {
    $_SESSION["user"]["Phone"] = $phone;
}

if ($addressWasSent) {
    $_SESSION["user"]["Address"] = $address;
}

sendJson([
    "success" => true,
    "message" => "Profile updated successfully",
    "user" => $_SESSION["user"]
]);
?>