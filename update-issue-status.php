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

function columnExists($conn, $tableName, $columnName) {
    $tableName = preg_replace('/[^A-Za-z0-9_]/', '', $tableName);
    $columnName = mysqli_real_escape_string($conn, $columnName);

    $result = mysqli_query($conn, "SHOW COLUMNS FROM `$tableName` LIKE '$columnName'");
    return $result && mysqli_num_rows($result) > 0;
}

if (!isset($_SESSION["user"]) || !isset($_SESSION["user"]["UserID"])) {
    sendJson([
        "success" => false,
        "message" => "Please login first"
    ]);
}

$adminID = intval($_SESSION["user"]["UserID"] ?? 0);
$role = strtolower(trim($_SESSION["user"]["Role"] ?? ""));

if ($adminID <= 0 || $role !== "admin") {
    sendJson([
        "success" => false,
        "message" => "Access denied. Admin only can update issues."
    ]);
}

if (!columnExists($conn, "order_issues", "AdminResponse") ||
    !columnExists($conn, "order_issues", "AdminUserID") ||
    !columnExists($conn, "order_issues", "ResolvedAt") ||
    !columnExists($conn, "order_issues", "UpdatedAt")) {
    sendJson([
        "success" => false,
        "message" => "Missing issue handling columns. Please run the SQL ALTER TABLE first."
    ]);
}

$data = json_decode(file_get_contents("php://input"), true);

if (!$data || !is_array($data)) {
    sendJson([
        "success" => false,
        "message" => "Invalid request data"
    ]);
}

$issueID = intval($data["issueID"] ?? $data["IssueID"] ?? 0);
$issueStatus = trim($data["issueStatus"] ?? $data["IssueStatus"] ?? "");
$adminResponse = trim($data["adminResponse"] ?? $data["AdminResponse"] ?? "");

$allowedStatuses = [
    "Open",
    "In Review",
    "Resolved",
    "Rejected"
];

if ($issueID <= 0) {
    sendJson([
        "success" => false,
        "message" => "Invalid issue ID"
    ]);
}

if (!in_array($issueStatus, $allowedStatuses, true)) {
    sendJson([
        "success" => false,
        "message" => "Invalid issue status"
    ]);
}

if (($issueStatus === "Resolved" || $issueStatus === "Rejected") && strlen($adminResponse) < 5) {
    sendJson([
        "success" => false,
        "message" => "Admin response is required for resolved or rejected issues"
    ]);
}

$checkStmt = mysqli_prepare($conn, "
    SELECT IssueID, IssueStatus
    FROM order_issues
    WHERE IssueID = ?
    LIMIT 1
");

if (!$checkStmt) {
    sendJson([
        "success" => false,
        "message" => "Database error: " . mysqli_error($conn)
    ]);
}

mysqli_stmt_bind_param($checkStmt, "i", $issueID);
mysqli_stmt_execute($checkStmt);

$checkResult = mysqli_stmt_get_result($checkStmt);
$issue = mysqli_fetch_assoc($checkResult);

if (!$issue) {
    sendJson([
        "success" => false,
        "message" => "Issue not found"
    ]);
}

$resolvedAtSql = ($issueStatus === "Resolved" || $issueStatus === "Rejected")
    ? "NOW()"
    : "NULL";

$sql = "
    UPDATE order_issues
    SET
        IssueStatus = ?,
        AdminResponse = ?,
        AdminUserID = ?,
        ResolvedAt = $resolvedAtSql,
        UpdatedAt = NOW()
    WHERE IssueID = ?
";

$stmt = mysqli_prepare($conn, $sql);

if (!$stmt) {
    sendJson([
        "success" => false,
        "message" => "Database error: " . mysqli_error($conn)
    ]);
}

mysqli_stmt_bind_param(
    $stmt,
    "ssii",
    $issueStatus,
    $adminResponse,
    $adminID,
    $issueID
);

if (!mysqli_stmt_execute($stmt)) {
    sendJson([
        "success" => false,
        "message" => "Failed to update issue: " . mysqli_stmt_error($stmt)
    ]);
}

sendJson([
    "success" => true,
    "message" => "Issue updated successfully",
    "issue" => [
        "IssueID" => $issueID,
        "IssueStatus" => $issueStatus,
        "AdminResponse" => $adminResponse,
        "AdminUserID" => $adminID
    ]
]);
?>