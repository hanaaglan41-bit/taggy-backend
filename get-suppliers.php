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

if (!tableExists($conn, "supplier_profiles")) {
    sendJson([
        "success" => false,
        "message" => "supplier_profiles table not found",
        "suppliers" => []
    ]);
}

$hasOffersTable = tableExists($conn, "supplier_option_offers");
$hasOfferID = $hasOffersTable && columnExists($conn, "supplier_option_offers", "OfferID");
$hasExtraCost = $hasOffersTable && columnExists($conn, "supplier_option_offers", "ExtraCost");
$hasIsAvailable = $hasOffersTable && columnExists($conn, "supplier_option_offers", "IsAvailable");
$hasOfferSupplierID = $hasOffersTable && columnExists($conn, "supplier_option_offers", "SupplierProfileID");

$joinSql = "";
$offersCountSelect = "0 AS OffersCount";
$minExtraCostSelect = "0 AS MinExtraCost";
$maxExtraCostSelect = "0 AS MaxExtraCost";

if ($hasOffersTable && $hasOfferID && $hasOfferSupplierID) {
    $availabilityCondition = $hasIsAvailable ? "AND soo.IsAvailable = 1" : "";

    $joinSql = "
        LEFT JOIN supplier_option_offers soo
            ON soo.SupplierProfileID = sp.SupplierProfileID
            $availabilityCondition
    ";

    $offersCountSelect = "COUNT(soo.OfferID) AS OffersCount";

    if ($hasExtraCost) {
        $minExtraCostSelect = "COALESCE(MIN(soo.ExtraCost), 0) AS MinExtraCost";
        $maxExtraCostSelect = "COALESCE(MAX(soo.ExtraCost), 0) AS MaxExtraCost";
    }
}

$statusFilter = "WHERE sp.Status = 'Active'";

if (!columnExists($conn, "supplier_profiles", "Status")) {
    $statusFilter = "";
}

$sql = "
    SELECT
        sp.SupplierProfileID,
        sp.SupplierName,
        sp.Email,
        sp.Phone,
        sp.Specialty,
        sp.PriceLevel,
        sp.ProductionTime,
        sp.Rating,
        sp.IsVerified,
        sp.IsBulkReady,
        sp.IsEcoPackaging,
        " . (columnExists($conn, "supplier_profiles", "Status") ? "sp.Status" : "'Active' AS Status") . ",

        $offersCountSelect,
        $minExtraCostSelect,
        $maxExtraCostSelect

    FROM supplier_profiles sp

    $joinSql

    $statusFilter

    GROUP BY
        sp.SupplierProfileID,
        sp.SupplierName,
        sp.Email,
        sp.Phone,
        sp.Specialty,
        sp.PriceLevel,
        sp.ProductionTime,
        sp.Rating,
        sp.IsVerified,
        sp.IsBulkReady,
        sp.IsEcoPackaging
        " . (columnExists($conn, "supplier_profiles", "Status") ? ", sp.Status" : "") . "

    ORDER BY sp.Rating DESC, sp.SupplierName ASC
";

$result = mysqli_query($conn, $sql);

if (!$result) {
    sendJson([
        "success" => false,
        "message" => "Database error: " . mysqli_error($conn),
        "suppliers" => []
    ]);
}

$suppliers = [];

while ($row = mysqli_fetch_assoc($result)) {
    $rating = floatval($row["Rating"] ?? 0);

    $suppliers[] = [
        "SupplierProfileID" => intval($row["SupplierProfileID"] ?? 0),
        "SupplierName" => $row["SupplierName"] ?? "",
        "Email" => $row["Email"] ?? "",
        "Phone" => $row["Phone"] ?? "",
        "Specialty" => $row["Specialty"] ?? "",
        "PriceLevel" => $row["PriceLevel"] ?: "Medium",
        "ProductionTime" => $row["ProductionTime"] ?: "5-7 days",
        "Rating" => round($rating, 2),
        "IsVerified" => intval($row["IsVerified"] ?? 0),
        "IsBulkReady" => intval($row["IsBulkReady"] ?? 0),
        "IsEcoPackaging" => intval($row["IsEcoPackaging"] ?? 0),
        "Status" => $row["Status"] ?? "Active",

        "OffersCount" => intval($row["OffersCount"] ?? 0),
        "MinExtraCost" => floatval($row["MinExtraCost"] ?? 0),
        "MaxExtraCost" => floatval($row["MaxExtraCost"] ?? 0)
    ];
}

sendJson([
    "success" => true,
    "suppliers" => $suppliers,
    "data" => $suppliers,
    "count" => count($suppliers)
]);
?>