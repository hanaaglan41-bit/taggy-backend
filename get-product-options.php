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

function getQABadge($supplier) {
    if (intval($supplier["IsVerified"] ?? 0) === 1 && floatval($supplier["Rating"] ?? 0) >= 4.7) {
        return "Verified & Top Rated";
    }

    if (intval($supplier["IsEcoPackaging"] ?? 0) === 1) {
        return "Eco Packaging";
    }

    if (intval($supplier["IsBulkReady"] ?? 0) === 1) {
        return "Bulk Order Ready";
    }

    if (intval($supplier["IsVerified"] ?? 0) === 1) {
        return "Verified Supplier";
    }

    return "QA Checked";
}

function getExtraPriceByLevel($priceLevel) {
    $priceLevel = strtolower(trim($priceLevel));

    if ($priceLevel === "low") {
        return 0;
    }

    if ($priceLevel === "medium") {
        return 25;
    }

    if ($priceLevel === "high") {
        return 70;
    }

    return 0;
}

function getOfferLabelFallback($supplier) {
    $rating = floatval($supplier["Rating"] ?? 0);
    $priceLevel = strtolower(trim($supplier["PriceLevel"] ?? "medium"));
    $productionTime = strtolower(trim($supplier["ProductionTime"] ?? ""));

    if ($priceLevel === "low") {
        return "Best Price";
    }

    if (strpos($productionTime, "2") !== false || strpos($productionTime, "3") !== false) {
        return "Fast Delivery";
    }

    if ($rating >= 4.8) {
        return "Top Rated";
    }

    if (intval($supplier["IsEcoPackaging"] ?? 0) === 1) {
        return "Eco Packaging";
    }

    if (intval($supplier["IsBulkReady"] ?? 0) === 1) {
        return "Bulk Ready";
    }

    return "Standard Offer";
}

function getOfferDescriptionFallback($supplier) {
    $label = getOfferLabelFallback($supplier);

    if ($label === "Best Price") {
        return "Affordable supplier with no extra supplier cost.";
    }

    if ($label === "Fast Delivery") {
        return "Faster production option suitable for urgent orders.";
    }

    if ($label === "Top Rated") {
        return "Highly rated supplier with premium finishing quality.";
    }

    if ($label === "Eco Packaging") {
        return "Includes eco-friendly packaging and better presentation.";
    }

    if ($label === "Bulk Ready") {
        return "Suitable for bulk and business orders.";
    }

    return "Reliable standard supplier for customized products.";
}

function isFastProduction($productionTime) {
    $productionTime = strtolower(trim($productionTime));

    return (
        strpos($productionTime, "2") !== false ||
        strpos($productionTime, "3") !== false
    ) ? 1 : 0;
}

/* =========================
   Validate Product ID
========================= */

$catalogProductID = intval(
    $_GET["id"] ??
    $_GET["productID"] ??
    $_GET["ProductID"] ??
    $_GET["CatalogProductID"] ??
    $_GET["catalogProductID"] ??
    0
);

if ($catalogProductID <= 0) {
    sendJson([
        "success" => false,
        "message" => "Product id is required",
        "product" => null,
        "options" => [],
        "offersByOption" => [],
        "suppliers" => [],
        "allSuppliers" => []
    ]);
}

if (!tableExists($conn, "productcatalog")) {
    sendJson([
        "success" => false,
        "message" => "productcatalog table not found",
        "product" => null,
        "options" => [],
        "offersByOption" => [],
        "suppliers" => [],
        "allSuppliers" => []
    ]);
}

/* =========================
   1) Get Product
========================= */

$productStmt = mysqli_prepare($conn, "
    SELECT 
        CatalogProductID,
        ProductName,
        Category,
        Description,
        ProductImage
    FROM productcatalog
    WHERE CatalogProductID = ?
    LIMIT 1
");

if (!$productStmt) {
    sendJson([
        "success" => false,
        "message" => "Product query failed: " . mysqli_error($conn),
        "product" => null,
        "options" => [],
        "offersByOption" => [],
        "suppliers" => [],
        "allSuppliers" => []
    ]);
}

mysqli_stmt_bind_param($productStmt, "i", $catalogProductID);
mysqli_stmt_execute($productStmt);

$productResult = mysqli_stmt_get_result($productStmt);

if (!$productResult || mysqli_num_rows($productResult) === 0) {
    sendJson([
        "success" => false,
        "message" => "Product not found",
        "product" => null,
        "options" => [],
        "offersByOption" => [],
        "suppliers" => [],
        "allSuppliers" => []
    ]);
}

$product = mysqli_fetch_assoc($productResult);

$productData = [
    "CatalogProductID" => intval($product["CatalogProductID"] ?? 0),
    "ProductName" => $product["ProductName"] ?? "",
    "Category" => $product["Category"] ?? "",
    "Description" => $product["Description"] ?? "",
    "ProductImage" => $product["ProductImage"] ?? "hero-placeholder.svg"
];

/* =========================
   2) Get Product Options
========================= */

$options = [];

if (tableExists($conn, "productcatalogoption")) {
    $optionStmt = mysqli_prepare($conn, "
        SELECT 
            OptionID,
            CatalogProductID,
            OptionName,
            Price
        FROM productcatalogoption
        WHERE CatalogProductID = ?
        ORDER BY Price ASC
    ");

    if (!$optionStmt) {
        sendJson([
            "success" => false,
            "message" => "Options query failed: " . mysqli_error($conn),
            "product" => $productData,
            "options" => [],
            "offersByOption" => [],
            "suppliers" => [],
            "allSuppliers" => []
        ]);
    }

    mysqli_stmt_bind_param($optionStmt, "i", $catalogProductID);
    mysqli_stmt_execute($optionStmt);

    $optionResult = mysqli_stmt_get_result($optionStmt);

    if ($optionResult) {
        while ($row = mysqli_fetch_assoc($optionResult)) {
            $options[] = [
                "OptionID" => intval($row["OptionID"] ?? 0),
                "CatalogProductID" => intval($row["CatalogProductID"] ?? $catalogProductID),
                "OptionName" => $row["OptionName"] ?? "Standard",
                "Price" => floatval($row["Price"] ?? 0),
                "Suppliers" => []
            ];
        }
    }
}

/* Fallback option if product has no saved options */
if (count($options) === 0) {
    $options[] = [
        "OptionID" => 0,
        "CatalogProductID" => $catalogProductID,
        "OptionName" => "Standard",
        "Price" => 100,
        "Suppliers" => []
    ];
}

/* =========================
   3) Get Supplier Offers
========================= */

$offersByOption = [];
$allSuppliersFlat = [];

$hasOffersTable = tableExists($conn, "supplier_option_offers");
$hasSuppliersTable = tableExists($conn, "supplier_profiles");

if ($hasOffersTable && $hasSuppliersTable && tableExists($conn, "productcatalogoption")) {
    $offersStmt = mysqli_prepare($conn, "
        SELECT
            o.OptionID,
            o.OptionName,
            o.Price AS BasePrice,

            soo.OfferID,
            soo.ExtraCost,
            soo.ProductionTime AS OfferProductionTime,
            soo.OfferLabel,
            soo.OfferDescription,
            soo.IsAvailable,

            sp.SupplierProfileID,
            sp.SupplierName,
            sp.Email,
            sp.Phone,
            sp.Specialty,
            sp.PriceLevel,
            sp.ProductionTime AS SupplierProductionTime,
            sp.Rating,
            sp.IsVerified,
            sp.IsBulkReady,
            sp.IsEcoPackaging,
            sp.Status

        FROM productcatalogoption o

        INNER JOIN supplier_option_offers soo
            ON soo.OptionID = o.OptionID
            AND soo.IsAvailable = 1

        INNER JOIN supplier_profiles sp
            ON sp.SupplierProfileID = soo.SupplierProfileID
            AND sp.Status = 'Active'

        WHERE o.CatalogProductID = ?

        ORDER BY
            o.Price ASC,
            soo.ExtraCost ASC,
            sp.Rating DESC,
            sp.SupplierName ASC
    ");

    if ($offersStmt) {
        mysqli_stmt_bind_param($offersStmt, "i", $catalogProductID);
        mysqli_stmt_execute($offersStmt);

        $offersResult = mysqli_stmt_get_result($offersStmt);

        if ($offersResult) {
            while ($row = mysqli_fetch_assoc($offersResult)) {
                $optionID = intval($row["OptionID"] ?? 0);
                $basePrice = floatval($row["BasePrice"] ?? 0);
                $extraCost = floatval($row["ExtraCost"] ?? 0);
                $finalPrice = $basePrice + $extraCost;

                $productionTime = $row["OfferProductionTime"] ?: ($row["SupplierProductionTime"] ?? "5-7 days");
                $contactInfo = trim(($row["Email"] ?? "") . " " . ($row["Phone"] ?? ""));

                $supplier = [
                    "OptionID" => $optionID,
                    "OptionName" => $row["OptionName"] ?? "",
                    "BasePrice" => $basePrice,

                    "OfferID" => intval($row["OfferID"] ?? 0),

                    "SupplierID" => intval($row["SupplierProfileID"] ?? 0),
                    "SupplierProfileID" => intval($row["SupplierProfileID"] ?? 0),
                    "SupplierName" => $row["SupplierName"] ?? "Supplier",

                    "ContactInfo" => $contactInfo !== "" ? $contactInfo : "N/A",
                    "AvailabilityStatus" => $row["Status"] ?? "Active",

                    "Specialty" => $row["Specialty"] ?? "General customized gifts",
                    "PriceLevel" => $row["PriceLevel"] ?? "Medium",

                    "ExtraPrice" => $extraCost,
                    "ExtraCost" => $extraCost,
                    "FinalPrice" => $finalPrice,

                    "ProductionTime" => $productionTime,
                    "Rating" => round(floatval($row["Rating"] ?? 4.0), 1),

                    "OfferLabel" => $row["OfferLabel"] ?: "Standard Offer",
                    "OfferDescription" => $row["OfferDescription"] ?: "Reliable supplier for customized products.",

                    "QABadge" => getQABadge($row),
                    "Verified" => intval($row["IsVerified"] ?? 0),
                    "FastDelivery" => isFastProduction($productionTime),
                    "BulkReady" => intval($row["IsBulkReady"] ?? 0),
                    "EcoPackaging" => intval($row["IsEcoPackaging"] ?? 0)
                ];

                if (!isset($offersByOption[$optionID])) {
                    $offersByOption[$optionID] = [];
                }

                $offersByOption[$optionID][] = $supplier;
                $allSuppliersFlat[] = $supplier;
            }
        }
    }
}

/* =========================
   4) Fallback if no supplier offers
========================= */

if (count($allSuppliersFlat) === 0 && $hasSuppliersTable) {
    $statusCondition = columnExists($conn, "supplier_profiles", "Status")
        ? "WHERE Status = 'Active'"
        : "";

    $supplierResult = mysqli_query($conn, "
        SELECT 
            SupplierProfileID,
            SupplierName,
            Email,
            Phone,
            Specialty,
            PriceLevel,
            ProductionTime,
            Rating,
            IsVerified,
            IsBulkReady,
            IsEcoPackaging,
            " . (columnExists($conn, "supplier_profiles", "Status") ? "Status" : "'Active' AS Status") . "
        FROM supplier_profiles
        $statusCondition
        ORDER BY Rating DESC, SupplierName ASC
    ");

    if ($supplierResult) {
        foreach ($options as $option) {
            $optionID = intval($option["OptionID"]);
            $basePrice = floatval($option["Price"]);

            mysqli_data_seek($supplierResult, 0);

            while ($row = mysqli_fetch_assoc($supplierResult)) {
                $extraCost = getExtraPriceByLevel($row["PriceLevel"] ?? "Medium");
                $finalPrice = $basePrice + $extraCost;

                $productionTime = $row["ProductionTime"] ?? "5-7 days";
                $contactInfo = trim(($row["Email"] ?? "") . " " . ($row["Phone"] ?? ""));

                $supplier = [
                    "OptionID" => $optionID,
                    "OptionName" => $option["OptionName"],
                    "BasePrice" => $basePrice,

                    "OfferID" => 0,

                    "SupplierID" => intval($row["SupplierProfileID"] ?? 0),
                    "SupplierProfileID" => intval($row["SupplierProfileID"] ?? 0),
                    "SupplierName" => $row["SupplierName"] ?? "Supplier",

                    "ContactInfo" => $contactInfo !== "" ? $contactInfo : "N/A",
                    "AvailabilityStatus" => $row["Status"] ?? "Active",

                    "Specialty" => $row["Specialty"] ?? "General customized gifts",
                    "PriceLevel" => $row["PriceLevel"] ?? "Medium",

                    "ExtraPrice" => $extraCost,
                    "ExtraCost" => $extraCost,
                    "FinalPrice" => $finalPrice,

                    "ProductionTime" => $productionTime,
                    "Rating" => round(floatval($row["Rating"] ?? 4.0), 1),

                    "OfferLabel" => getOfferLabelFallback($row),
                    "OfferDescription" => getOfferDescriptionFallback($row),

                    "QABadge" => getQABadge($row),
                    "Verified" => intval($row["IsVerified"] ?? 0),
                    "FastDelivery" => isFastProduction($productionTime),
                    "BulkReady" => intval($row["IsBulkReady"] ?? 0),
                    "EcoPackaging" => intval($row["IsEcoPackaging"] ?? 0)
                ];

                if (!isset($offersByOption[$optionID])) {
                    $offersByOption[$optionID] = [];
                }

                $offersByOption[$optionID][] = $supplier;
                $allSuppliersFlat[] = $supplier;
            }
        }
    }
}

/* =========================
   5) Attach suppliers to options
========================= */

foreach ($options as $index => $option) {
    $optionID = intval($option["OptionID"]);
    $options[$index]["Suppliers"] = $offersByOption[$optionID] ?? [];
}

/*
   Backward compatibility:
   Old product pages may read "suppliers" only.
   So we return suppliers of first option as default.
*/
$defaultOptionID = intval($options[0]["OptionID"] ?? 0);
$defaultSuppliers = $offersByOption[$defaultOptionID] ?? [];

sendJson([
    "success" => true,
    "message" => "Product options loaded successfully",

    "product" => $productData,

    "options" => $options,
    "offersByOption" => $offersByOption,

    "suppliers" => $defaultSuppliers,
    "allSuppliers" => $allSuppliersFlat,

    "countOptions" => count($options),
    "countSuppliers" => count($allSuppliersFlat)
]);
?>