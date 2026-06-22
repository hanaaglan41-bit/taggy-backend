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

$role = strtolower(trim($_SESSION["user"]["Role"] ?? ""));

if ($role !== "admin") {
    sendJson([
        "success" => false,
        "message" => "Access denied. Admin only can view issues.",
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

        wo.CustomerName,
        wo.Phone,
        wo.OrderStatus,
        wo.TotalAmount,
        wo.TrackingNumber,

        u.Email,

        $adminNameSelect

    FROM order_issues oi

    LEFT JOIN website_orders wo
        ON oi.OrderID = wo.OrderID

    LEFT JOIN users u
        ON oi.UserID = u.UserID

    $adminJoin

    ORDER BY oi.IssueID DESC
";

$result = mysqli_query($conn, $sql);

if (!$result) {
    sendJson([
        "success" => false,
        "message" => mysqli_error($conn),
        "issues" => []
    ]);
}

$issues = [];

while ($row = mysqli_fetch_assoc($result)) {
    $issues[] = [
        "IssueID" => intval($row["IssueID"]),
        "OrderID" => intval($row["OrderID"]),
        "OrderNumber" => "TAGGY-" . intval($row["OrderID"]),
        "TrackingNumber" => $row["TrackingNumber"] ?? "",

        "UserID" => intval($row["UserID"]),
        "CustomerName" => $row["CustomerName"] ?? "Customer",
        "Phone" => $row["Phone"] ?? "-",
        "Email" => $row["Email"] ?? "",

        "IssueType" => $row["IssueType"] ?? "",
        "IssueMessage" => $row["IssueMessage"] ?? "",
        "IssueStatus" => $row["IssueStatus"] ?? "Open",

        "AdminResponse" => $row["AdminResponse"] ?? "",
        "AdminUserID" => $row["AdminUserID"] !== null ? intval($row["AdminUserID"]) : null,
        "AdminName" => $row["AdminName"] ?? "",

        "OrderStatus" => $row["OrderStatus"] ?? "",
        "TotalAmount" => floatval($row["TotalAmount"] ?? 0),

        "CreatedAt" => $row["CreatedAt"] ?? "",
        "ResolvedAt" => $row["ResolvedAt"] ?? "",
        "UpdatedAt" => $row["UpdatedAt"] ?? ""
    ];
}

$openIssues = 0;
$inReviewIssues = 0;
$resolvedIssues = 0;
$rejectedIssues = 0;

foreach ($issues as $issue) {
    $status = strtolower(trim($issue["IssueStatus"] ?? ""));

    if ($status === "open") {
        $openIssues++;
    } elseif ($status === "in review") {
        $inReviewIssues++;
    } elseif ($status === "resolved") {
        $resolvedIssues++;
    } elseif ($status === "rejected") {
        $rejectedIssues++;
    }
}

sendJson([
    "success" => true,
    "issues" => $issues,
    "summary" => [
        "TotalIssues" => count($issues),
        "OpenIssues" => $openIssues,
        "InReviewIssues" => $inReviewIssues,
        "ResolvedIssues" => $resolvedIssues,
        "RejectedIssues" => $rejectedIssues
    ]
]);
?>