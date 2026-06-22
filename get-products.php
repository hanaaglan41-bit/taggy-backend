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

function columnExists($conn, $tableName, $columnName) {
    $tableName = preg_replace('/[^A-Za-z0-9_]/', '', $tableName);
    $columnName = mysqli_real_escape_string($conn, $columnName);

    $result = mysqli_query($conn, "SHOW COLUMNS FROM `$tableName` LIKE '$columnName'");
    return $result && mysqli_num_rows($result) > 0;
}

$statusCondition = "";

if (columnExists($conn, "productcatalog", "Status")) {
    $statusCondition = "WHERE pc.Status = 'Active'";
}

$query = "
    SELECT 
        pc.CatalogProductID,
        pc.ProductName,
        pc.Category,
        pc.Description,
        pc.ProductImage,
        COALESCE(MIN(pco.Price), 0) AS MinPrice,
        COUNT(pco.OptionID) AS OptionsCount
    FROM productcatalog pc

    LEFT JOIN productcatalogoption pco 
        ON pc.CatalogProductID = pco.CatalogProductID

    $statusCondition

    GROUP BY 
        pc.CatalogProductID,
        pc.ProductName,
        pc.Category,
        pc.Description,
        pc.ProductImage

    ORDER BY pc.CatalogProductID ASC
";

$result = mysqli_query($conn, $query);

if (!$result) {
    sendJson([
        "success" => false,
        "message" => "Products query failed: " . mysqli_error($conn),
        "products" => []
    ]);
}

$products = [];

while ($row = mysqli_fetch_assoc($result)) {
    $products[] = [
        "CatalogProductID" => intval($row["CatalogProductID"]),
        "ProductName" => $row["ProductName"] ?? "",
        "Category" => $row["Category"] ?? "",
        "Description" => $row["Description"] ?? "",
        "ProductImage" => $row["ProductImage"] ?? "hero-placeholder.svg",
        "MinPrice" => floatval($row["MinPrice"] ?? 0),
        "OptionsCount" => intval($row["OptionsCount"] ?? 0)
    ];
}

sendJson([
    "success" => true,
    "products" => $products,
    "data" => $products,
    "count" => count($products)
]);
?>