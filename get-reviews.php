<?php
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

function tableExists($conn, $tableName) {
    $tableName = mysqli_real_escape_string($conn, $tableName);
    $result = mysqli_query($conn, "SHOW TABLES LIKE '$tableName'");
    return $result && mysqli_num_rows($result) > 0;
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

/* Main reviews table */
$reviewsTable = null;

if (tableExists($conn, "website_reviews")) {
    $reviewsTable = "website_reviews";
} elseif (tableExists($conn, "reviews")) {
    $reviewsTable = "reviews";
}

if (!$reviewsTable) {
    sendJson([
        "success" => true,
        "message" => "No reviews table found",
        "reviews" => [],
        "data" => [],
        "count" => 0,
        "averageRating" => 0
    ]);
}

/* Optional limit: get-reviews.php?limit=6 */
$limit = intval($_GET["limit"] ?? 6);

if ($limit <= 0) {
    $limit = 6;
}

if ($limit > 50) {
    $limit = 50;
}

/* Safe columns */
$reviewIDSelect = selectColumnOrDefault($conn, $reviewsTable, "ReviewID", 0);
$orderIDSelect = selectColumnOrDefault($conn, $reviewsTable, "OrderID", 0);
$customerNameSelect = selectColumnOrDefault($conn, $reviewsTable, "CustomerName", "Customer");
$ratingSelect = selectColumnOrDefault($conn, $reviewsTable, "Rating", 0);
$commentSelect = selectColumnOrDefault($conn, $reviewsTable, "Comment", "");
$createdAtSelect = selectColumnOrDefault($conn, $reviewsTable, "CreatedAt", "");

$orderColumn = columnExists($conn, $reviewsTable, "ReviewID") ? "ReviewID" : "CreatedAt";

$sql = "
    SELECT 
        $reviewIDSelect,
        $orderIDSelect,
        $customerNameSelect,
        $ratingSelect,
        $commentSelect,
        $createdAtSelect
    FROM `$reviewsTable`
    ORDER BY `$orderColumn` DESC
    LIMIT ?
";

$stmt = mysqli_prepare($conn, $sql);

if (!$stmt) {
    sendJson([
        "success" => false,
        "message" => "Database error: " . mysqli_error($conn),
        "reviews" => [],
        "data" => [],
        "count" => 0,
        "averageRating" => 0
    ]);
}

mysqli_stmt_bind_param($stmt, "i", $limit);
mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

if (!$result) {
    sendJson([
        "success" => false,
        "message" => "Query failed: " . mysqli_stmt_error($stmt),
        "reviews" => [],
        "data" => [],
        "count" => 0,
        "averageRating" => 0
    ]);
}

$reviews = [];
$totalRating = 0;
$ratingCount = 0;

while ($row = mysqli_fetch_assoc($result)) {
    $rating = intval($row["Rating"] ?? 0);

    if ($rating < 0) {
        $rating = 0;
    }

    if ($rating > 5) {
        $rating = 5;
    }

    if ($rating > 0) {
        $totalRating += $rating;
        $ratingCount++;
    }

    $reviews[] = [
        "ReviewID" => intval($row["ReviewID"] ?? 0),
        "OrderID" => intval($row["OrderID"] ?? 0),
        "CustomerName" => $row["CustomerName"] ?: "Customer",
        "Rating" => $rating,
        "Comment" => $row["Comment"] ?? "",
        "CreatedAt" => $row["CreatedAt"] ?? ""
    ];
}

$averageRating = $ratingCount > 0 ? round($totalRating / $ratingCount, 1) : 0;

sendJson([
    "success" => true,
    "message" => "Reviews loaded successfully",
    "reviews" => $reviews,
    "data" => $reviews,
    "count" => count($reviews),
    "averageRating" => $averageRating
]);
?>