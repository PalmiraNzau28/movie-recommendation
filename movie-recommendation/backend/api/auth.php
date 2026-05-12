<?php
// Limpar buffers e garantir JSON puro
if (ob_get_level()) ob_end_clean();
ob_start();

session_start();
require_once __DIR__ . '/../helpers/response.php';
require_once __DIR__ . '/../helpers/sanitize.php';
require_once __DIR__ . '/../services/ValidationService.php';
require_once __DIR__ . '/../repositories/UserRepository.php';

$action = $_GET['action'] ?? '';

try {
    switch ($action) {
        case 'register':
            handleRegister();
            break;
        case 'login':
            handleLogin();
            break;
        case 'logout':
            handleLogout();
            break;
        case 'me':
            handleGetMe();
            break;
        default:
            sendError("Invalid action", 400);
    }
} catch (Exception $e) {
    error_log("Auth error: " . $e->getMessage());
    sendError("Internal server error", 500);
}

function handleRegister() {
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) {
        $input = $_POST;
    }
    
    $name = trim($input['name'] ?? '');
    $email = sanitizeEmail($input['email'] ?? '');
    $password = $input['password'] ?? '';
    $confirmPassword = $input['confirm_password'] ?? '';
    
    $errors = [];
    
    if (!ValidationService::validateName($name)) {
        $errors['name'] = "Name must be 2-100 characters and contain only letters, spaces, and basic punctuation";
    }
    
    if (!ValidationService::validateEmail($email)) {
        $errors['email'] = "Valid email address is required (e.g., user@domain.com)";
    }
    
    if (!ValidationService::validateStrongPassword($password)) {
        $errors['password'] = "A senha precisa ter entre 8 e 100 caracteres, incluir pelo menos 1 letra maiúscula, 1 letra minúscula, 1 número e 1 caractere especial.";
    }
    
    if ($password !== $confirmPassword) {
        $errors['confirm_password'] = "Passwords do not match";
    }
    
    if (!empty($errors)) {
        sendValidationError($errors);
    }
    
    $passwordHash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
    
    $userRepo = new UserRepository();
    $userId = $userRepo->create($name, $email, $passwordHash);
    
    if (!$userId) {
        sendError("Email already registered", 409);
    }
    
    $_SESSION['user_id'] = $userId;
    $_SESSION['user_email'] = $email;
    $_SESSION['user_name'] = $name;
    
    sendSuccess([
        'id' => $userId,
        'name' => $name,
        'email' => $email
    ], "Registration successful", 201);
}

function handleLogin() {
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) {
        $input = $_POST;
    }
    
    $email = sanitizeEmail($input['email'] ?? '');
    $password = $input['password'] ?? '';
    
    if (empty($email) || empty($password)) {
        sendValidationError(['general' => 'Email and password are required']);
    }
    
    $userRepo = new UserRepository();
    $user = $userRepo->findByEmail($email);
    
    if (!$user || !password_verify($password, $user['password_hash'])) {
        sendError("Invalid email or password", 401);
    }
    
    session_regenerate_id(true);
    
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_email'] = $user['email'];
    $_SESSION['user_name'] = $user['name'];
    
    sendSuccess([
        'id' => $user['id'],
        'name' => $user['name'],
        'email' => $user['email']
    ], "Login successful");
}

function handleLogout() {
    $_SESSION = [];
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    session_destroy();
    sendSuccess(null, "Logged out successfully");
}

function handleGetMe() {
    if (!isset($_SESSION['user_id'])) {
        sendUnauthorized("Not authenticated");
    }
    
    sendSuccess([
        'id' => $_SESSION['user_id'],
        'name' => $_SESSION['user_name'],
        'email' => $_SESSION['user_email']
    ]);
}
?>