<?php
// CORS - Colocar ANTES de tudo
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Content-Type: application/json; charset=utf-8");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

session_start();
require_once __DIR__ . '/../helpers/response.php';
require_once __DIR__ . '/../repositories/MovieRepository.php';
require_once __DIR__ . '/../services/TmdbService.php';

define('TMDB_API_KEY', '0f16caa18c71cc60f5d668fea95144b8');

$action = $_GET['action'] ?? '';

// Ações públicas que NÃO precisam de autenticação
$publicActions = ['popular', 'search', 'details'];

// Verificar autenticação apenas para ações privadas
if (!in_array($action, $publicActions) && !isset($_SESSION['user_id'])) {
    sendUnauthorized("Authentication required");
}

$movieRepo = new MovieRepository();
$tmdbService = new TmdbService(TMDB_API_KEY);

try {
    switch ($action) {
        case 'search':
            handleSearch($movieRepo, $tmdbService);
            break;
        case 'popular':
            handlePopular($movieRepo, $tmdbService);
            break;
        case 'details':
            handleDetails($movieRepo, $tmdbService);
            break;
        default:
            sendError("Invalid action. Use: search, popular, or details", 400);
    }
} catch (Exception $e) {
    error_log("Movies API error: " . $e->getMessage());
    sendError("Internal server error: " . $e->getMessage(), 500);
}

function handleSearch($movieRepo, $tmdbService) {
    $query = $_GET['q'] ?? $_GET['query'] ?? '';
    
    if (empty($query) || strlen($query) < 2) {
        sendValidationError(['query' => 'Search query must be at least 2 characters']);
    }
    
    $result = $tmdbService->searchMovies($query);
    
    if (empty($result['results'])) {
        sendSuccess([], "No movies found");
    }
    
    $savedMovies = [];
    foreach ($result['results'] as $tmdbMovie) {
        $movieId = $movieRepo->saveOrUpdate($tmdbMovie);
        if ($movieId) {
            $movie = $movieRepo->findById($movieId);
            if ($movie) {
                $movie['poster_url'] = $tmdbService->getPosterUrl($movie['poster_path']);
                $savedMovies[] = $movie;
            }
        }
    }
    
    sendSuccess($savedMovies, "Movies fetched from TMDb API");
}

function handlePopular($movieRepo, $tmdbService) {
    $randomPage = rand(1, 30);
    
    $result = $tmdbService->getPopularMovies($randomPage);
    
    if (!isset($result['results']) || empty($result['results'])) {
        $result = $tmdbService->getPopularMovies(1);
        if (!isset($result['results']) || empty($result['results'])) {
            sendSuccess([], "No popular movies found from TMDb API");
            return;
        }
    }
    
    $savedMovies = [];
    foreach ($result['results'] as $tmdbMovie) {
        $movieId = $movieRepo->saveOrUpdate($tmdbMovie);
        if ($movieId) {
            $movie = $movieRepo->findById($movieId);
            if ($movie) {
                $movie['poster_url'] = $tmdbService->getPosterUrl($movie['poster_path']);
                $savedMovies[] = $movie;
            }
        }
    }
    
    if (empty($savedMovies)) {
        sendSuccess([], "No movies could be saved from TMDb API");
    } else {
        sendSuccess($savedMovies, "Popular movies from TMDb API (page $randomPage)");
    }
}

function handleDetails($movieRepo, $tmdbService) {
    $tmdbId = isset($_GET['tmdb_id']) ? (int)$_GET['tmdb_id'] : 0;
    $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    
    if (!$tmdbId && !$id) {
        sendValidationError(['id' => 'Either tmdb_id or id is required']);
    }
    
    $movie = null;
    
    if ($id) {
        $movie = $movieRepo->findById($id);
    }
    
    if (!$movie && $tmdbId) {
        $movie = $movieRepo->findByTmdbId($tmdbId);
    }
    
    if (!$movie && $tmdbId) {
        $tmdbMovie = $tmdbService->getMovieDetails($tmdbId);
        if ($tmdbMovie) {
            $movieId = $movieRepo->saveOrUpdate($tmdbMovie);
            if ($movieId) {
                $movie = $movieRepo->findById($movieId);
            }
        }
    }
    
    if (!$movie) {
        sendNotFound("Movie not found");
    }
    
    $movie['poster_url'] = $tmdbService->getPosterUrl($movie['poster_path']);
    sendSuccess($movie, "Movie details retrieved");
}
?>