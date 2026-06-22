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

function tableExists($conn, $tableName) {
    $tableName = mysqli_real_escape_string($conn, $tableName);
    $result = mysqli_query($conn, "SHOW TABLES LIKE '$tableName'");
    return $result && mysqli_num_rows($result) > 0;
}

/* =========================
   Admin only protection
========================= */

if (!isset($_SESSION["user"]) || !isset($_SESSION["user"]["UserID"])) {
    sendJson([
        "success" => false,
        "message" => "Please login first"
    ]);
}

$role = strtolower(trim($_SESSION["user"]["Role"] ?? ""));

if ($role !== "admin") {
    sendJson([
        "success" => false,
        "message" => "Access denied. Admin only can delete products."
    ]);
}

/* =========================
   Table check
========================= */

if (!tableExists($conn, "productcatalog")) {
    sendJson([
        "success" => false,
        "message" => "productcatalog table not found"
    ]);
}

/* =========================
   Read request data
========================= */

$data = json_decode(file_get_contents("php://input"), true);

if (!$data || !is_array($data)) {
    sendJson([
        "success" => false,
        "message" => "Invalid request data"
    ]);
}

$id = intval($data["id"] ?? $data["CatalogProductID"] ?? 0);

if ($id <= 0) {
    sendJson([
        "success" => false,
        "message" => "Product ID is required"
    ]);
}

/* =========================
   Check product exists
========================= */

$checkStmt = mysqli_prepare($conn, "
    SELECT 
        CatalogProductID,
        ProductName
    FROM productcatalog
    WHERE CatalogProductID = ?
    LIMIT 1
");

if (!$checkStmt) {
    sendJson([
        "success" => false,
        "message" => "Database error: " . mysqli_error($conn)
    ]);
}

mysqli_stmt_bind_param($checkStmt, "i", $id);
mysqli_stmt_execute($checkStmt);

$checkResult = mysqli_stmt_get_result($checkStmt);

if (!$checkResult || mysqli_num_rows($checkResult) === 0) {
    sendJson([
        "success" => false,
        "message" => "Product not found"
    ]);
}

$product = mysqli_fetch_assoc($checkResult);
$productName = $product["ProductName"] ?? "";

/* =========================
   Delete product safely
========================= */

mysqli_begin_transaction($conn);

try {
    /*
       1) Delete supplier offers linked to this product options first.
       This prevents foreign key problems if supplier_option_offers.OptionID
       references productcatalogoption.OptionID.
    */
    if (
        tableExists($conn, "supplier_option_offers") &&
        tableExists($conn, "productcatalogoption")
    ) {
        $deleteOffersStmt = mysqli_prepare($conn, "
            DELETE FROM supplier_option_offers
            WHERE OptionID IN (
                SELECT OptionID
                FROM productcatalogoption
                WHERE CatalogProductID = ?
            )
        ");

        if (!$deleteOffersStmt) {
            throw new Exception("Supplier offers delete prepare failed: " . mysqli_error($conn));
        }

        mysqli_stmt_bind_param($deleteOffersStmt, "i", $id);

        if (!mysqli_stmt_execute($deleteOffersStmt)) {
            throw new Exception("Failed to delete supplier offers: " . mysqli_stmt_error($deleteOffersStmt));
        }
    }

    /*
       2) Delete product options.
    */
    if (tableExists($conn, "productcatalogoption")) {
        $deleteOptionsStmt = mysqli_prepare($conn, "
            DELETE FROM productcatalogoption
            WHERE CatalogProductID = ?
        ");

        if (!$deleteOptionsStmt) {
            throw new Exception("Options delete prepare failed: " . mysqli_error($conn));
        }

        mysqli_stmt_bind_param($deleteOptionsStmt, "i", $id);

        if (!mysqli_stmt_execute($deleteOptionsStmt)) {
            throw new Exception("Failed to delete product options: " . mysqli_stmt_error($deleteOptionsStmt));
        }
    }

    /*
       3) Delete product itself.
    */
    $deleteProductStmt = mysqli_prepare($conn, "
        DELETE FROM productcatalog
        WHERE CatalogProductID = ?
    ");

    if (!$deleteProductStmt) {
        throw new Exception("Product delete prepare failed: " . mysqli_error($conn));
    }

    mysqli_stmt_bind_param($deleteProductStmt, "i", $id);

    if (!mysqli_stmt_execute($deleteProductStmt)) {
        throw new Exception("Failed to delete product: " . mysqli_stmt_error($deleteProductStmt));
    }

    if (mysqli_stmt_affected_rows($deleteProductStmt) <= 0) {
        throw new Exception("Product was not deleted");
    }

    mysqli_commit($conn);

    sendJson([
        "success" => true,
        "message" => "Product deleted successfully",
        "deletedProductID" => $id,
        "deletedProductName" => $productName
    ]);

} catch (Exception $e) {
    mysqli_rollback($conn);

    sendJson([
        "success" => false,
        "message" => $e->getMessage()
    ]);
}
?>