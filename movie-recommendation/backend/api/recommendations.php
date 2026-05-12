<?php
// CORS - Colocar ANTES de tudo
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header("Access-Control-Allow-Origin: http://localhost:4200");
    header("Access-Control-Allow-Credentials: true");
    header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
    header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
    http_response_code(200);
    exit();
}

header("Access-Control-Allow-Origin: http://localhost:4200");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Content-Type: application/json; charset=utf-8");

session_start();
require_once __DIR__ . '/../helpers/response.php';
require_once __DIR__ . '/../services/RecommendationService.php';
require_once __DIR__ . '/../repositories/RecommendationRepository.php';

if (!isset($_SESSION['user_id'])) {
    sendUnauthorized("Authentication required");
}

$userId = $_SESSION['user_id'];
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';
$recommendationService = new RecommendationService();
$recommendationRepo = new RecommendationRepository();

try {
    switch ($action) {
        case 'get':
            handleGetRecommendations($userId, $recommendationService);
            break;
        case 'explain':
            handleGetExplanation($userId, $recommendationService);
            break;
        case 'history':
            handleGetHistory($userId, $recommendationRepo);
            break;
        default:
            sendError("Invalid action. Use: get, explain, or history", 400);
    }
} catch (Exception $e) {
    error_log("Recommendations API error: " . $e->getMessage());
    sendError("Internal server error", 500);
}

function handleGetRecommendations($userId, $recommendationService) {
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 20;
    $limit = min($limit, 50);
    
    $recommendations = $recommendationService->getRecommendations($userId, $limit);
    
    sendSuccess([
        'recommendations' => $recommendations,
        'total' => count($recommendations),
        'user_id' => $userId
    ], "AI recommendations generated successfully");
}

function handleGetExplanation($userId, $recommendationService) {
    $explanation = $recommendationService->getRecommendationExplanation($userId);
    sendSuccess($explanation, "AI recommendation explanation");
}

function handleGetHistory($userId, $recommendationRepo) {
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
    $history = $recommendationRepo->getRecommendationHistory($userId, $limit);
    sendSuccess($history, "Recommendation history retrieved");
}
?>