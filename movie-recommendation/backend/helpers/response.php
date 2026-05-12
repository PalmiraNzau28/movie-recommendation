<?php
// Remover qualquer saída anterior
ob_clean();

// Configurar CORS e headers dinamicamente para localhost
$headers = function_exists('getallheaders') ? getallheaders() : [];
$origin = $headers['Origin'] ?? $headers['origin'] ?? $_SERVER['HTTP_ORIGIN'] ?? $_SERVER['ORIGIN'] ?? '';
if (!empty($origin)) {
    header("Access-Control-Allow-Origin: $origin");
} else {
    header("Access-Control-Allow-Origin: http://localhost:4200");
}
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Content-Type: application/json; charset=utf-8");

// Responder preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

function sendSuccess($data = null, $message = "Success", $statusCode = 200) {
    http_response_code($statusCode);
    echo json_encode([
        'success' => true,
        'message' => $message,
        'data' => $data,
        'timestamp' => date('c')
    ], JSON_UNESCAPED_UNICODE);
    exit();
}

function sendError($message, $statusCode = 400, $errors = null) {
    http_response_code($statusCode);
    echo json_encode([
        'success' => false,
        'message' => $message,
        'errors' => $errors,
        'timestamp' => date('c')
    ], JSON_UNESCAPED_UNICODE);
    exit();
}

function sendUnauthorized($message = "Authentication required") {
    sendError($message, 401);
}

function sendForbidden($message = "Access denied") {
    sendError($message, 403);
}

function sendNotFound($message = "Resource not found") {
    sendError($message, 404);
}

function sendValidationError($errors) {
    sendError("Validation failed", 422, $errors);
}
?>