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
require_once __DIR__ . '/../services/RatingService.php';
require_once __DIR__ . '/../repositories/MovieRepository.php';
require_once __DIR__ . '/../services/ValidationService.php';

$method = $_SERVER['REQUEST_METHOD'];
$ratingService = new RatingService();
$movieRepo = new MovieRepository();

$publicRoutes = ['stats'];
$action = $_GET['action'] ?? '';

$requiresAuth = !in_array($action, $publicRoutes);
if ($requiresAuth && !isset($_SESSION['user_id'])) {
    sendUnauthorized("Authentication required");
}

$userId = $_SESSION['user_id'] ?? null;

try {
    switch ($method) {
        case 'POST':
            handlePost($userId, $ratingService, $movieRepo);
            break;
        case 'GET':
            handleGet($userId, $ratingService, $movieRepo);
            break;
        case 'DELETE':
            handleDelete($userId, $ratingService);
            break;
        default:
            sendError("Method not allowed", 405);
    }
} catch (Exception $e) {
    error_log("Ratings API error: " . $e->getMessage());
    sendError("Internal server error", 500);
}

function handlePost($userId, $ratingService, $movieRepo) {
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) {
        $input = $_POST;
    }
    
    $movieId = isset($input['movie_id']) ? (int)$input['movie_id'] : 0;
    $rating = isset($input['rating']) ? (int)$input['rating'] : 0;
    
    if ($movieId > 0) {
        $movie = $movieRepo->findById($movieId);
        if (!$movie) {
            sendNotFound("Movie not found. Please search for the movie first.");
        }
    }
    
    $result = $ratingService->saveRating($userId, $movieId, $rating);
    
    if (!$result['success']) {
        sendValidationError($result['errors']);
    }
    
    sendSuccess($result['data'], $result['message'], 201);
}

function handleGet($userId, $ratingService, $movieRepo) {
    $action = $_GET['action'] ?? '';
    $movieId = isset($_GET['movie_id']) ? (int)$_GET['movie_id'] : 0;
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    
    switch ($action) {
        case 'my-ratings':
            $perPage = isset($_GET['per_page']) ? (int)$_GET['per_page'] : 20;
            $result = $ratingService->getUserRatings($userId, $page, $perPage);
            sendSuccess($result['data'], "User ratings retrieved");
            break;
            
        case 'user-rating':
            if (!$movieId) {
                sendValidationError(['movie_id' => 'Movie ID is required']);
            }
            $rating = $ratingService->getUserRatingForMovie($userId, $movieId);
            sendSuccess($rating, $rating ? "Rating found" : "No rating found for this movie");
            break;
            
        case 'stats':
            if (!$movieId) {
                sendValidationError(['movie_id' => 'Movie ID is required']);
            }
            $stats = $ratingService->getMovieStats($movieId);
            sendSuccess($stats, "Movie statistics retrieved");
            break;
            
        default:
            sendError("Invalid action. Use: my-ratings, user-rating, or stats", 400);
    }
}

function handleDelete($userId, $ratingService) {
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) {
        $input = $_GET;
    }
    
    $movieId = isset($input['movie_id']) ? (int)$input['movie_id'] : 0;
    
    if (!$movieId) {
        sendValidationError(['movie_id' => 'Movie ID is required']);
    }
    
    $result = $ratingService->deleteRating($userId, $movieId);
    
    if (!$result['success']) {
        sendNotFound($result['message']);
    }
    
    sendSuccess(null, $result['message']);
}
?>
