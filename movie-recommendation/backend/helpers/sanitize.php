<?php
require_once __DIR__ . '/../services/ValidationService.php';

function sanitizeString($input) {
    return ValidationService::sanitizeInput($input);
}

function sanitizeEmail($email) {
    $email = trim($email);
    $email = filter_var($email, FILTER_SANITIZE_EMAIL);
    return strtolower($email);
}

function sanitizeInt($input, $default = 0) {
    $filtered = filter_var($input, FILTER_SANITIZE_NUMBER_INT);
    return $filtered !== false ? (int)$filtered : $default;
}
?>
