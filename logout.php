<?php
session_start();

header("Content-Type: application/json; charset=UTF-8");

function sendJson($data) {
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit();
}

/* Clear all session variables */
$_SESSION = [];

/* Delete session cookie if sessions use cookies */
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();

    setcookie(
        session_name(),
        "",
        time() - 42000,
        $params["path"] ?? "/",
        $params["domain"] ?? "",
        $params["secure"] ?? false,
        $params["httponly"] ?? true
    );
}

/* Destroy the session */
session_destroy();

sendJson([
    "success" => true,
    "message" => "Logged out successfully"
]);
?>