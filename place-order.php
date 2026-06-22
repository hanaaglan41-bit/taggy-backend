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

$role = strtolower(trim($_SESSION["user"]["Role"] ?? ""));

if ($role !== "customer") {
    sendJson([
        "success" => false,
        "message" => "Only customers can place orders"
    ]);
}

$data = json_decode(file_get_contents("php://input"), true);

if (!$data || !is_array($data)) {
    sendJson([
        "success" => false,
        "message" => "No order data received"
    ]);
}

$userID = intval($_SESSION["user"]["UserID"] ?? 0);

if ($userID <= 0) {
    sendJson([
        "success" => false,
        "message" => "Invalid session user"
    ]);
}

$customer = $data["customer"] ?? [];
$items = $data["items"] ?? [];
$summary = $data["summary"] ?? [];

if (!is_array($customer)) {
    $customer = [];
}

if (!is_array($summary)) {
    $summary = [];
}

if (!is_array($items) || count($items) === 0) {
    sendJson([
        "success" => false,
        "message" => "Cart is empty"
    ]);
}

$customerName = trim($customer["fullName"] ?? $customer["name"] ?? "");
$phone = trim($customer["phone"] ?? "");
$email = trim($customer["email"] ?? ($_SESSION["user"]["Email"] ?? ""));

$city = trim($customer["city"] ?? "");
$area = trim($customer["area"] ?? "");
$street = trim($customer["street"] ?? "");
$building = trim($customer["building"] ?? "");
$floor = trim($customer["floor"] ?? "");
$apartment = trim($customer["apartment"] ?? "");
$notes = trim($customer["notes"] ?? "");

$deliveryType = trim($data["deliveryType"] ?? $summary["deliveryType"] ?? "Standard Delivery");

$allowedDeliveryTypes = [
    "Standard Delivery",
    "Express Delivery"
];

if (!in_array($deliveryType, $allowedDeliveryTypes, true)) {
    $deliveryType = "Standard Delivery";
}

if (
    $customerName === "" ||
    $phone === "" ||
    $email === "" ||
    $city === "" ||
    $area === "" ||
    $street === "" ||
    $building === "" ||
    $apartment === ""
) {
    sendJson([
        "success" => false,
        "message" => "Please fill all delivery details"
    ]);
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    sendJson([
        "success" => false,
        "message" => "Please enter a valid email address"
    ]);
}

if (!preg_match('/^01[0125][0-9]{8}$/', $phone)) {
    sendJson([
        "success" => false,
        "message" => "Please enter a valid Egyptian phone number"
    ]);
}

$addressParts = [];

$addressParts[] = "Apartment " . $apartment;

if ($floor !== "") {
    $addressParts[] = "Floor " . $floor;
}

$addressParts[] = "Building " . $building;
$addressParts[] = $street;
$addressParts[] = $area;
$addressParts[] = $city;

$address = implode(", ", $addressParts);

if ($notes !== "") {
    $address .= " | Notes: " . $notes;
}

$paymentMethodInput = trim($data["paymentMethod"] ?? $summary["paymentMethod"] ?? "Cash on delivery");
$paymentMethodKey = strtolower($paymentMethodInput);

$allowedPaymentMethods = [
    "cash on delivery" => "Cash on delivery",
    "vodafone cash" => "Vodafone Cash",
    "bank transfer" => "Bank Transfer",
    "debit card" => "Debit Card"
];

if (!isset($allowedPaymentMethods[$paymentMethodKey])) {
    sendJson([
        "success" => false,
        "message" => "Invalid payment method"
    ]);
}

$paymentMethod = $allowedPaymentMethods[$paymentMethodKey];

/*
    Debit Card note:
    This project does not integrate a real payment gateway.
    The frontend may show card fields for simulation, but CVV/card data should not be stored in DB.
*/
$cardDetails = $data["cardDetails"] ?? $data["card"] ?? [];

if ($paymentMethod === "Debit Card" && is_array($cardDetails) && count($cardDetails) > 0) {
    $cardHolder = trim($cardDetails["cardHolder"] ?? $cardDetails["holder"] ?? $cardDetails["name"] ?? "");
    $cardNumber = preg_replace('/\D/', '', strval($cardDetails["cardNumber"] ?? $cardDetails["number"] ?? ""));
    $cardExpiry = trim($cardDetails["expiry"] ?? $cardDetails["cardExpiry"] ?? "");
    $cardCvv = preg_replace('/\D/', '', strval($cardDetails["cvv"] ?? $cardDetails["cvc"] ?? ""));

    if ($cardHolder === "" || $cardNumber === "" || $cardExpiry === "" || $cardCvv === "") {
        sendJson([
            "success" => false,
            "message" => "Please fill all debit card details"
        ]);
    }

    if (strlen($cardNumber) < 13 || strlen($cardNumber) > 19) {
        sendJson([
            "success" => false,
            "message" => "Invalid debit card number"
        ]);
    }

    if (!preg_match('/^(0[1-9]|1[0-2])\/(\d{2}|\d{4})$/', $cardExpiry)) {
        sendJson([
            "success" => false,
            "message" => "Invalid card expiry format. Use MM/YY"
        ]);
    }

    if (strlen($cardCvv) < 3 || strlen($cardCvv) > 4) {
        sendJson([
            "success" => false,
            "message" => "Invalid CVV"
        ]);
    }
}

$paymentStatus = (stripos($paymentMethod, "cash") !== false) ? "Pending" : "Paid";

$supplierProductionTime = trim($summary["supplierBaseProduction"] ?? "5-7 days");
$finalProductionTime = trim($summary["finalProductionTime"] ?? "5-7 days");
$estimatedArrival = trim($summary["estimatedArrival"] ?? "6-9 days");

/*
    If you want admin approval first, change this to Pending.
    Current flow: customer places order -> supplier sees it as Processing.
*/
$orderStatus = "Processing";

function parseMoneyValue($value) {
    if (is_numeric($value)) {
        return floatval($value);
    }

    $clean = preg_replace('/[^0-9.]/', '', strval($value));

    if ($clean === "") {
        return 0;
    }

    return floatval($clean);
}

function calculateTotalQuantity($items) {
    $totalQuantity = 0;

    foreach ($items as $item) {
        if (!is_array($item)) {
            continue;
        }

        $quantity = intval($item["quantity"] ?? $item["Quantity"] ?? 1);

        if ($quantity <= 0) {
            $quantity = 1;
        }

        $totalQuantity += $quantity;
    }

    return $totalQuantity;
}

function calculateProductsTotal($items) {
    $productsTotal = 0;

    foreach ($items as $item) {
        if (!is_array($item)) {
            continue;
        }

        $quantity = intval($item["quantity"] ?? $item["Quantity"] ?? 1);

        if ($quantity <= 0) {
            $quantity = 1;
        }

        $lineTotal = parseMoneyValue(
            $item["totalPrice"] ??
            $item["TotalPrice"] ??
            $item["lineTotal"] ??
            $item["LineTotal"] ??
            0
        );

        if ($lineTotal <= 0) {
            $unitPrice = parseMoneyValue(
                $item["unitPrice"] ??
                $item["UnitPrice"] ??
                $item["pricePerUnit"] ??
                $item["price"] ??
                $item["Price"] ??
                0
            );

            $lineTotal = $unitPrice * $quantity;
        }

        $productsTotal += $lineTotal;
    }

    return $productsTotal;
}

function getDeliveryRuleByQuantity($totalQuantity) {
    if ($totalQuantity >= 101) {
        return [
            "email" => "bulk.delivery@taggy.com",
            "fee" => 250,
            "company" => "Bulk Delivery Company"
        ];
    }

    if ($totalQuantity >= 11) {
        return [
            "email" => "medium.delivery@taggy.com",
            "fee" => 150,
            "company" => "Medium Delivery Company"
        ];
    }

    return [
        "email" => "small.delivery@taggy.com",
        "fee" => 100,
        "company" => "Small Delivery Company"
    ];
}

function getDeliveryUserIDByEmail($conn, $email) {
    $stmt = mysqli_prepare($conn, "
        SELECT UserID
        FROM users
        WHERE Email = ?
        AND LOWER(Role) = 'delivery'
        LIMIT 1
    ");

    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt);

        $result = mysqli_stmt_get_result($stmt);

        if ($result && mysqli_num_rows($result) > 0) {
            $row = mysqli_fetch_assoc($result);
            return intval($row["UserID"]);
        }
    }

    /*
        Fallback:
        If the exact demo delivery email does not exist, assign the first delivery user.
        This prevents checkout from failing while still keeping the delivery dashboard flow.
    */
    $fallbackResult = mysqli_query($conn, "
        SELECT UserID
        FROM users
        WHERE LOWER(Role) = 'delivery'
        ORDER BY UserID ASC
        LIMIT 1
    ");

    if ($fallbackResult && mysqli_num_rows($fallbackResult) > 0) {
        $row = mysqli_fetch_assoc($fallbackResult);
        return intval($row["UserID"]);
    }

    return 0;
}

function getFirstActiveSupplierID($conn) {
    $result = mysqli_query($conn, "
        SELECT SupplierProfileID
        FROM supplier_profiles
        WHERE Status = 'Active'
        ORDER BY SupplierProfileID
        LIMIT 1
    ");

    if ($result && mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        return intval($row["SupplierProfileID"]);
    }

    return 0;
}

function getUserSubscriptionData($conn, $userID) {
    $stmt = mysqli_prepare($conn, "
        SELECT
            AccountType,
            SubscriptionPlan,
            SubscriptionStatus
        FROM users
        WHERE UserID = ?
        LIMIT 1
    ");

    if (!$stmt) {
        return [
            "accountType" => "individual",
            "subscriptionPlan" => "none",
            "subscriptionStatus" => "inactive"
        ];
    }

    mysqli_stmt_bind_param($stmt, "i", $userID);
    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);

    if ($result && mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);

        return [
            "accountType" => strtolower(trim($row["AccountType"] ?? "individual")),
            "subscriptionPlan" => strtolower(trim($row["SubscriptionPlan"] ?? "none")),
            "subscriptionStatus" => strtolower(trim($row["SubscriptionStatus"] ?? "inactive"))
        ];
    }

    return [
        "accountType" => "individual",
        "subscriptionPlan" => "none",
        "subscriptionStatus" => "inactive"
    ];
}

function getDiscountPercent($accountType, $subscriptionPlan, $subscriptionStatus) {
    if ($accountType !== "business" || $subscriptionStatus !== "active") {
        return 0;
    }

    if ($subscriptionPlan === "small" || $subscriptionPlan === "starter") {
        return 5;
    }

    if ($subscriptionPlan === "growth") {
        return 10;
    }

    if ($subscriptionPlan === "premium") {
        return 15;
    }

    return 0;
}

$supplierProfileID = 0;

foreach ($items as $item) {
    if (!is_array($item)) {
        continue;
    }

    $itemSupplierID = intval($item["SupplierID"] ?? $item["SupplierProfileID"] ?? 0);

    if ($itemSupplierID > 0) {
        $supplierProfileID = $itemSupplierID;
        break;
    }
}

if ($supplierProfileID > 0) {
    $supplierCheck = mysqli_prepare($conn, "
        SELECT SupplierProfileID
        FROM supplier_profiles
        WHERE SupplierProfileID = ?
        AND Status = 'Active'
        LIMIT 1
    ");

    if ($supplierCheck) {
        mysqli_stmt_bind_param($supplierCheck, "i", $supplierProfileID);
        mysqli_stmt_execute($supplierCheck);

        $supplierResult = mysqli_stmt_get_result($supplierCheck);

        if (!$supplierResult || mysqli_num_rows($supplierResult) === 0) {
            $supplierProfileID = getFirstActiveSupplierID($conn);
        }
    }
} else {
    $supplierProfileID = getFirstActiveSupplierID($conn);
}

if ($supplierProfileID <= 0) {
    sendJson([
        "success" => false,
        "message" => "No active supplier is available"
    ]);
}

$totalQuantity = calculateTotalQuantity($items);
$productsTotal = calculateProductsTotal($items);

if ($productsTotal <= 0) {
    $productsTotal = parseMoneyValue($summary["subtotal"] ?? $summary["productsTotal"] ?? 0);
}

if ($productsTotal <= 0) {
    sendJson([
        "success" => false,
        "message" => "Invalid cart total"
    ]);
}

$deliveryRule = getDeliveryRuleByQuantity($totalQuantity);
$deliveryFees = floatval($deliveryRule["fee"]);

if ($deliveryType === "Express Delivery") {
    $deliveryFees += 50;
}

$deliveryUserID = getDeliveryUserIDByEmail($conn, $deliveryRule["email"]);

if ($deliveryUserID <= 0) {
    sendJson([
        "success" => false,
        "message" => "No delivery user account found. Please create at least one user with Role = delivery, then try again."
    ]);
}

$subscriptionData = getUserSubscriptionData($conn, $userID);

$discountPercent = getDiscountPercent(
    $subscriptionData["accountType"],
    $subscriptionData["subscriptionPlan"],
    $subscriptionData["subscriptionStatus"]
);

$discountAmount = round(($productsTotal * $discountPercent) / 100, 2);

$subscriptionPlan = $subscriptionData["subscriptionPlan"];

if ($subscriptionPlan === "" || $subscriptionPlan === "none") {
    $subscriptionPlan = "No Business Offer";
}

$totalAmount = $productsTotal + $deliveryFees - $discountAmount;

if ($totalAmount < 0) {
    $totalAmount = 0;
}

$temporaryTrackingNumber = "PENDING-" . time() . "-" . random_int(1000, 9999);

mysqli_begin_transaction($conn);

$orderSql = "
    INSERT INTO website_orders
    (
        UserID,
        SupplierProfileID,
        DeliveryUserID,
        TrackingNumber,
        CustomerName,
        Phone,
        Email,
        Address,
        DeliveryType,
        PaymentMethod,
        PaymentStatus,
        ProductsTotal,
        DeliveryFees,
        DiscountAmount,
        DiscountPercent,
        TotalAmount,
        SupplierProductionTime,
        FinalProductionTime,
        EstimatedArrival,
        OrderStatus,
        SubscriptionPlan,
        CreatedAt
    )
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
";

$orderStmt = mysqli_prepare($conn, $orderSql);

if (!$orderStmt) {
    mysqli_rollback($conn);

    sendJson([
        "success" => false,
        "message" => "Order prepare failed: " . mysqli_error($conn)
    ]);
}

mysqli_stmt_bind_param(
    $orderStmt,
    "iiissssssssdddddsssss",
    $userID,
    $supplierProfileID,
    $deliveryUserID,
    $temporaryTrackingNumber,
    $customerName,
    $phone,
    $email,
    $address,
    $deliveryType,
    $paymentMethod,
    $paymentStatus,
    $productsTotal,
    $deliveryFees,
    $discountAmount,
    $discountPercent,
    $totalAmount,
    $supplierProductionTime,
    $finalProductionTime,
    $estimatedArrival,
    $orderStatus,
    $subscriptionPlan
);

if (!mysqli_stmt_execute($orderStmt)) {
    mysqli_rollback($conn);

    sendJson([
        "success" => false,
        "message" => "Order insert failed: " . mysqli_stmt_error($orderStmt)
    ]);
}

$orderID = mysqli_insert_id($conn);

$generatedTrackingNumber = "TAGGY-" . str_pad((string)$orderID, 5, "0", STR_PAD_LEFT);

$trackingStmt = mysqli_prepare($conn, "
    UPDATE website_orders
    SET TrackingNumber = ?
    WHERE OrderID = ?
");

if (!$trackingStmt) {
    mysqli_rollback($conn);

    sendJson([
        "success" => false,
        "message" => "Tracking prepare failed: " . mysqli_error($conn)
    ]);
}

mysqli_stmt_bind_param($trackingStmt, "si", $generatedTrackingNumber, $orderID);

if (!mysqli_stmt_execute($trackingStmt)) {
    mysqli_rollback($conn);

    sendJson([
        "success" => false,
        "message" => "Tracking update failed: " . mysqli_stmt_error($trackingStmt)
    ]);
}

/*
    Order Status History:
    This records the first status created when the customer places the order.
    If the history table is not imported yet, the order will still be placed.
*/
if (tableExists($conn, "order_status_history")) {
    $historySql = "
        INSERT INTO order_status_history
        (
            OrderID,
            ChangedByUserID,
            OldStatus,
            NewStatus,
            Source,
            Note
        )
        VALUES (?, ?, ?, ?, ?, ?)
    ";

    $historyStmt = mysqli_prepare($conn, $historySql);

    if ($historyStmt) {
        $oldStatus = "Order Created";
        $newStatus = $orderStatus;
        $historySource = "customer";
        $historyNote = "Order placed by customer";

        mysqli_stmt_bind_param(
            $historyStmt,
            "iissss",
            $orderID,
            $userID,
            $oldStatus,
            $newStatus,
            $historySource,
            $historyNote
        );

        if (!mysqli_stmt_execute($historyStmt)) {
            mysqli_rollback($conn);

            sendJson([
                "success" => false,
                "message" => "Status history insert failed: " . mysqli_stmt_error($historyStmt)
            ]);
        }
    }
}

$itemSql = "
    INSERT INTO website_order_items
    (
        OrderID,
        ProductName,
        OptionName,
        Quantity,
        UnitPrice,
        TotalPrice,
        ProductImage,
        SupplierName,
        DesignText,
        DesignColor,
        Notes
    )
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
";

$itemStmt = mysqli_prepare($conn, $itemSql);

if (!$itemStmt) {
    mysqli_rollback($conn);

    sendJson([
        "success" => false,
        "message" => "Items prepare failed: " . mysqli_error($conn)
    ]);
}

foreach ($items as $item) {
    if (!is_array($item)) {
        continue;
    }

    $productName = trim(
        $item["product"] ??
        $item["ProductName"] ??
        $item["productName"] ??
        $item["name"] ??
        "Product"
    );

    $optionName = trim(
        $item["OptionName"] ??
        $item["optionName"] ??
        $item["option"] ??
        "Standard Option"
    );

    $quantity = intval($item["quantity"] ?? $item["Quantity"] ?? 1);

    if ($quantity <= 0) {
        $quantity = 1;
    }

    $lineTotal = parseMoneyValue(
        $item["totalPrice"] ??
        $item["TotalPrice"] ??
        $item["lineTotal"] ??
        $item["LineTotal"] ??
        0
    );

    $unitPriceFromItem = parseMoneyValue(
        $item["unitPrice"] ??
        $item["UnitPrice"] ??
        $item["pricePerUnit"] ??
        $item["price"] ??
        $item["Price"] ??
        0
    );

    if ($lineTotal <= 0) {
        $lineTotal = $unitPriceFromItem * $quantity;
    }

    $unitPrice = $quantity > 0 ? ($lineTotal / $quantity) : $unitPriceFromItem;

    $productImage = trim(
        $item["image"] ??
        $item["ProductImage"] ??
        $item["productImage"] ??
        "hero-placeholder.svg"
    );

    $supplierName = trim(
        $item["SupplierName"] ??
        $item["supplierName"] ??
        "Selected Supplier"
    );

    $designText = trim($item["designText"] ?? $item["DesignText"] ?? "");
    $designColor = trim($item["designColor"] ?? $item["DesignColor"] ?? "");
    $itemNotes = trim($item["notes"] ?? $item["Notes"] ?? "");

    mysqli_stmt_bind_param(
        $itemStmt,
        "issiddsssss",
        $orderID,
        $productName,
        $optionName,
        $quantity,
        $unitPrice,
        $lineTotal,
        $productImage,
        $supplierName,
        $designText,
        $designColor,
        $itemNotes
    );

    if (!mysqli_stmt_execute($itemStmt)) {
        mysqli_rollback($conn);

        sendJson([
            "success" => false,
            "message" => "Item insert failed: " . mysqli_stmt_error($itemStmt)
        ]);
    }
}

mysqli_commit($conn);

sendJson([
    "success" => true,
    "message" => "Order placed successfully",
    "OrderID" => $orderID,
    "OrderNumber" => "TAGGY-" . $orderID,
    "TrackingNumber" => $generatedTrackingNumber,
    "TotalQuantity" => $totalQuantity,
    "DeliveryCompany" => $deliveryRule["company"],
    "DeliveryCompanyEmail" => $deliveryRule["email"],
    "DeliveryUserID" => $deliveryUserID,
    "DeliveryType" => $deliveryType,
    "PaymentMethod" => $paymentMethod,
    "PaymentStatus" => $paymentStatus,
    "DeliveryFees" => round($deliveryFees, 2),
    "ProductsTotal" => round($productsTotal, 2),
    "DiscountPercent" => round($discountPercent, 2),
    "DiscountAmount" => round($discountAmount, 2),
    "TotalAmount" => round($totalAmount, 2),
    "OrderStatus" => $orderStatus,
    "EstimatedArrival" => $estimatedArrival
]);
?>