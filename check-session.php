<?php
session_start();

header("Content-Type: application/json; charset=UTF-8");

function sendJson($data) {
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit();
}

if (
    isset($_SESSION["user"]) &&
    is_array($_SESSION["user"]) &&
    isset($_SESSION["user"]["UserID"])
) {
    $user = $_SESSION["user"];

    $user["UserID"] = intval($user["UserID"] ?? 0);
    $user["FullName"] = $user["FullName"] ?? "";
    $user["Email"] = $user["Email"] ?? "";
    $user["Role"] = strtolower(trim($user["Role"] ?? ""));

    $user["Phone"] = $user["Phone"] ?? "";
    $user["Address"] = $user["Address"] ?? "";

    $user["AccountType"] = strtolower(trim($user["AccountType"] ?? "individual"));
    $user["CompanyName"] = $user["CompanyName"] ?? "";
    $user["BusinessType"] = $user["BusinessType"] ?? "";
    $user["OrderVolume"] = $user["OrderVolume"] ?? "";

    $user["SubscriptionPlan"] = strtolower(trim($user["SubscriptionPlan"] ?? "none"));
    $user["SubscriptionStatus"] = strtolower(trim($user["SubscriptionStatus"] ?? "inactive"));
    $user["SubscriptionPrice"] = floatval($user["SubscriptionPrice"] ?? 0);
    $user["SubscriptionPaymentMethod"] = $user["SubscriptionPaymentMethod"] ?? "";
    $user["SubscriptionPaymentReference"] = $user["SubscriptionPaymentReference"] ?? "";
    $user["SubscriptionStartDate"] = $user["SubscriptionStartDate"] ?? null;
    $user["SubscriptionEndDate"] = $user["SubscriptionEndDate"] ?? null;

    if ($user["UserID"] <= 0 || $user["Role"] === "") {
        $_SESSION = [];
        session_destroy();

        sendJson([
            "loggedIn" => false,
            "success" => false,
            "message" => "Invalid session",
            "user" => null
        ]);
    }

    $_SESSION["user"] = $user;

    sendJson([
        "loggedIn" => true,
        "success" => true,
        "message" => "User is logged in",
        "role" => $user["Role"],
        "user" => $user
    ]);
}

sendJson([
    "loggedIn" => false,
    "success" => false,
    "message" => "User is not logged in",
    "role" => null,
    "user" => null
]);
?>