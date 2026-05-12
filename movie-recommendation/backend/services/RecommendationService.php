<?php
require_once __DIR__ . '/../repositories/RatingRepository.php';
require_once __DIR__ . '/../repositories/MovieRepository.php';
require_once __DIR__ . '/../repositories/RecommendationRepository.php';

class RecommendationService {
    private $ratingRepo;
    private $movieRepo;
    private $recommendationRepo;
    
    // Mapeamento de IDs de gênero para nomes (para debug)
    private $genresMap = [
        28 => 'Ação', 12 => 'Aventura', 16 => 'Animação', 35 => 'Comédia',
        80 => 'Crime', 99 => 'Documentário', 18 => 'Drama', 10751 => 'Família',
        14 => 'Fantasia', 36 => 'História', 27 => 'Terror', 10402 => 'Música',
        9648 => 'Mistério', 10749 => 'Romance', 878 => 'Ficção Científica',
        10770 => 'TV', 53 => 'Thriller', 10752 => 'Guerra', 37 => 'Faroeste'
    ];
    
    public function __construct() {
        $this->ratingRepo = new RatingRepository();
        $this->movieRepo = new MovieRepository();
        $this->recommendationRepo = new RecommendationRepository();
    }
    
    /**
     * GERADOR DE RECOMENDAÇÕES (IA REALISTA)
     * 
     * @param int $userId
     * @param int $limit
     * @return array
     */
    public function getRecommendations($userId, $limit = 20) {
        $userRatings = $this->ratingRepo->findByUser($userId, 100, 0);
        
        if (empty($userRatings)) {
            return $this->getFallbackRecommendations($limit);
        }
        
        $likedMovies = array_filter($userRatings, function($rating) {
            return $rating['rating'] >= 4;
        });
        
        if (count($likedMovies) < 2) {
            return $this->getFallbackRecommendations($limit);
        }
        
        $genreWeights = $this->calculateGenreWeights($likedMovies);
        
        if (empty($genreWeights)) {
            return $this->getFallbackRecommendations($limit);
        }
        
        $ratedMovieIds = array_column($userRatings, 'movie_id');
        $candidateMovies = $this->getUnexploredMovies($ratedMovieIds, 100);
        
        if (empty($candidateMovies)) {
            return $this->getFallbackRecommendations($limit);
        }
        
        $scoredMovies = [];
        foreach ($candidateMovies as $movie) {
            $score = $this->calculateSimilarityScore($movie, $genreWeights);
            if ($score > 0) {
                $scoredMovies[] = [
                    'movie' => $movie,
                    'score' => $score,
                    'match_reasons' => $this->getMatchReasons($movie, $genreWeights)
                ];
            }
        }
        
        usort($scoredMovies, function($a, $b) {
            if ($a['score'] == $b['score']) {
                return $b['movie']['popularity'] <=> $a['movie']['popularity'];
            }
            return $b['score'] <=> $a['score'];
        });
        
        $topRecommendations = array_slice($scoredMovies, 0, $limit);
        
        $recommendedIds = array_column(array_column($topRecommendations, 'movie'), 'id');
        $this->recommendationRepo->logRecommendation($userId, $recommendedIds, 'genre_based_v1');
        
        return $this->formatRecommendations($topRecommendations, $genreWeights);
    }
    
    private function calculateGenreWeights($likedMovies) {
        $genreCount = [];
        
        foreach ($likedMovies as $movie) {
            $genres = $movie['genre_ids'] ?? [];
            
            if (is_string($genres) && !empty($genres)) {
                $genres = json_decode($genres, true);
            }
            
            if (is_array($genres) && !empty($genres)) {
                foreach ($genres as $genreId) {
                    $genreId = (int)$genreId;
                    $genreCount[$genreId] = ($genreCount[$genreId] ?? 0) + 1;
                }
            }
        }
        
        if (empty($genreCount)) {
            return [];
        }
        
        $maxCount = max($genreCount);
        $normalizedWeights = [];
        foreach ($genreCount as $genreId => $count) {
            $normalizedWeights[$genreId] = $count / $maxCount;
        }
        
        return $normalizedWeights;
    }
    
    private function calculateSimilarityScore($movie, $genreWeights) {
        $movieGenres = $movie['genre_ids'] ?? [];
        if (is_string($movieGenres)) {
            $movieGenres = json_decode($movieGenres, true);
        }
        
        if (!is_array($movieGenres) || empty($movieGenres)) {
            return 0;
        }
        
        $totalScore = 0;
        foreach ($movieGenres as $genreId) {
            $genreId = (int)$genreId;
            if (isset($genreWeights[$genreId])) {
                $totalScore += $genreWeights[$genreId];
            }
        }
        
        return $totalScore / count($movieGenres);
    }
    
    private function getMatchReasons($movie, $genreWeights) {
        $movieGenres = $movie['genre_ids'] ?? [];
        if (is_string($movieGenres)) {
            $movieGenres = json_decode($movieGenres, true);
        }
        
        $matchedGenres = [];
        if (is_array($movieGenres)) {
            foreach ($movieGenres as $genreId) {
                $genreId = (int)$genreId;
                if (isset($genreWeights[$genreId])) {
                    $genreName = $this->genresMap[$genreId] ?? "Gênero $genreId";
                    $matchedGenres[] = [
                        'id' => $genreId,
                        'name' => $genreName,
                        'weight' => round($genreWeights[$genreId], 2)
                    ];
                }
            }
        }
        
        return $matchedGenres;
    }
    
    private function getUnexploredMovies($ratedMovieIds, $limit = 100) {
        try {
            $db = getDB();
            
            if (empty($ratedMovieIds)) {
                $sql = "SELECT * FROM movies ORDER BY popularity DESC LIMIT ?";
                $stmt = $db->prepare($sql);
                $stmt->bindValue(1, $limit, PDO::PARAM_INT);
            } else {
                $placeholders = implode(',', array_fill(0, count($ratedMovieIds), '?'));
                $sql = "SELECT * FROM movies WHERE id NOT IN ({$placeholders}) ORDER BY popularity DESC LIMIT ?";
                $stmt = $db->prepare($sql);
                
                $idx = 1;
                foreach ($ratedMovieIds as $id) {
                    $stmt->bindValue($idx++, $id, PDO::PARAM_INT);
                }
                $stmt->bindValue($idx, $limit, PDO::PARAM_INT);
            }
            
            $stmt->execute();
            $movies = $stmt->fetchAll();
            
            foreach ($movies as &$movie) {
                if ($movie['genre_ids']) {
                    $movie['genre_ids'] = json_decode($movie['genre_ids'], true);
                }
            }
            
            return $movies;
        } catch (PDOException $e) {
            error_log("RecommendationService::getUnexploredMovies error: " . $e->getMessage());
            return [];
        }
    }
    
    private function getFallbackRecommendations($limit = 20) {
        $popularMovies = $this->movieRepo->getPopular($limit);
        
        $formatted = [];
        foreach ($popularMovies as $movie) {
            $formatted[] = [
                'movie' => $movie,
                'score' => 0,
                'match_reasons' => [],
                'recommendation_reason' => 'Popular movie (fallback - rate more movies for personalized recommendations)'
            ];
        }
        
        return $this->formatRecommendations($formatted, []);
    }
    
    private function formatRecommendations($scoredMovies, $genreWeights) {
        $recommendations = [];
        
        foreach ($scoredMovies as $item) {
            $movie = $item['movie'];
            $stats = $this->ratingRepo->getMovieAverage($movie['id']);
            
            $recommendations[] = [
                'id' => $movie['id'],
                'tmdb_id' => $movie['tmdb_id'],
                'title' => $movie['title'],
                'overview' => $movie['overview'],
                'poster_path' => $movie['poster_path'],
                'poster_url' => "https://image.tmdb.org/t/p/w500" . $movie['poster_path'],
                'genre_ids' => $movie['genre_ids'],
                'popularity' => $movie['popularity'],
                'similarity_score' => round($item['score'] * 100, 1),
                'movie_average_rating' => $stats['average'],
                'total_ratings' => $stats['total_count'],
                'why_recommended' => $item['match_reasons']
            ];
        }
        
        return $recommendations;
    }
    
    public function getRecommendationExplanation($userId) {
        $userRatings = $this->ratingRepo->findByUser($userId, 100, 0);
        
        $likedMovies = array_filter($userRatings, function($rating) {
            if ($rating['rating'] < 4) return false;
            
            $genres = $rating['genre_ids'] ?? [];
            if (is_string($genres)) {
                $genres = json_decode($genres, true);
            }
            
            return is_array($genres) && !empty($genres);
        });
        
        if (count($likedMovies) < 2) {
            return [
                'has_enough_data' => false,
                'total_liked_movies' => count($likedMovies),
                'message' => 'Rate at least 2 movies with 4+ stars that have genre information to get personalized AI recommendations',
                'genre_preferences' => []
            ];
        }
        
        $genreWeights = $this->calculateGenreWeights($likedMovies);
        
        if (empty($genreWeights)) {
            return [
                'has_enough_data' => false,
                'total_liked_movies' => count($likedMovies),
                'message' => 'Your rated movies lack genre information. Try searching for and rating more movies from TMDb.',
                'genre_preferences' => []
            ];
        }
        
        $preferences = [];
        foreach ($genreWeights as $genreId => $weight) {
            $genreName = $this->genresMap[$genreId] ?? "Gênero $genreId";
            $preferences[] = [
                'genre_id' => $genreId,
                'genre_name' => $genreName,
                'weight' => round($weight * 100, 1),
                'confidence' => $weight > 0.7 ? 'high' : ($weight > 0.3 ? 'medium' : 'low')
            ];
        }
        
        usort($preferences, function($a, $b) {
            return $b['weight'] <=> $a['weight'];
        });
        
        return [
            'has_enough_data' => true,
            'total_liked_movies' => count($likedMovies),
            'genre_preferences' => array_slice($preferences, 0, 5),
            'algorithm' => 'Genre-based content filtering with weighted scoring',
            'explanation' => 'The system analyzes which genres appear most in movies you rated 4+ stars, then recommends unseen movies that share those genres.'
        ];
    }
}
?>