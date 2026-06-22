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
        "message" => "Please login first",
        "issues" => []
    ]);
}

$userID = intval($_SESSION["user"]["UserID"] ?? 0);
$role = strtolower(trim($_SESSION["user"]["Role"] ?? ""));

if ($userID <= 0 || $role !== "customer") {
    sendJson([
        "success" => false,
        "message" => "Only customers can view their issue updates",
        "issues" => []
    ]);
}

$hasAdminResponse = columnExists($conn, "order_issues", "AdminResponse");
$hasAdminUserID = columnExists($conn, "order_issues", "AdminUserID");
$hasResolvedAt = columnExists($conn, "order_issues", "ResolvedAt");
$hasUpdatedAt = columnExists($conn, "order_issues", "UpdatedAt");

$adminResponseSelect = $hasAdminResponse
    ? "oi.AdminResponse"
    : "NULL AS AdminResponse";

$adminUserIDSelect = $hasAdminUserID
    ? "oi.AdminUserID"
    : "NULL AS AdminUserID";

$resolvedAtSelect = $hasResolvedAt
    ? "oi.ResolvedAt"
    : "NULL AS ResolvedAt";

$updatedAtSelect = $hasUpdatedAt
    ? "oi.UpdatedAt"
    : "NULL AS UpdatedAt";

$adminNameSelect = $hasAdminUserID
    ? "admin.FullName AS AdminName"
    : "NULL AS AdminName";

$adminJoin = $hasAdminUserID
    ? "LEFT JOIN users admin ON oi.AdminUserID = admin.UserID"
    : "";

$sql = "
    SELECT
        oi.IssueID,
        oi.OrderID,
        oi.UserID,
        oi.IssueType,
        oi.IssueMessage,
        oi.IssueStatus,
        oi.CreatedAt,

        $adminResponseSelect,
        $adminUserIDSelect,
        $resolvedAtSelect,
        $updatedAtSelect,

        wo.TrackingNumber,
        wo.OrderStatus,
        wo.CustomerName,

        $adminNameSelect

    FROM order_issues oi

    INNER JOIN website_orders wo
        ON oi.OrderID = wo.OrderID
        AND wo.UserID = ?

    $adminJoin

    WHERE oi.UserID = ?
    ORDER BY oi.IssueID DESC
";

$stmt = mysqli_prepare($conn, $sql);

if (!$stmt) {
    sendJson([
        "success" => false,
        "message" => "Database error: " . mysqli_error($conn),
        "issues" => []
    ]);
}

mysqli_stmt_bind_param($stmt, "ii", $userID, $userID);
mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

$issues = [];

while ($row = mysqli_fetch_assoc($result)) {
    $issues[] = [
        "IssueID" => intval($row["IssueID"]),
        "OrderID" => intval($row["OrderID"]),
        "OrderNumber" => "TAGGY-" . intval($row["OrderID"]),
        "TrackingNumber" => $row["TrackingNumber"] ?? "",

        "UserID" => intval($row["UserID"]),
        "CustomerName" => $row["CustomerName"] ?? "Customer",

        "IssueType" => $row["IssueType"] ?? "",
        "IssueMessage" => $row["IssueMessage"] ?? "",
        "IssueStatus" => $row["IssueStatus"] ?? "Open",

        "AdminResponse" => $row["AdminResponse"] ?? "",
        "AdminUserID" => $row["AdminUserID"] !== null ? intval($row["AdminUserID"]) : null,
        "AdminName" => $row["AdminName"] ?? "Admin",

        "OrderStatus" => $row["OrderStatus"] ?? "",

        "CreatedAt" => $row["CreatedAt"] ?? "",
        "ResolvedAt" => $row["ResolvedAt"] ?? "",
        "UpdatedAt" => $row["UpdatedAt"] ?? ""
    ];
}

sendJson([
    "success" => true,
    "issues" => $issues
]);
?>