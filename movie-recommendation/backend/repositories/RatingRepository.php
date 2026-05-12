<?php
require_once __DIR__ . '/../config/database.php';

class RatingRepository {
    private $db;
    
    public function __construct() {
        $this->db = getDB();
    }
    
    /**
     * Criar ou atualizar avaliação (UPSERT)
     * @param int $userId
     * @param int $movieId
     * @param int $rating (1-5)
     * @return bool
     */
    public function upsert($userId, $movieId, $rating) {
        try {
            $sql = "INSERT INTO ratings (user_id, movie_id, rating) 
                    VALUES (:user_id, :movie_id, :rating)
                    ON DUPLICATE KEY UPDATE 
                    rating = VALUES(rating), 
                    created_at = CURRENT_TIMESTAMP";
            
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([
                ':user_id' => $userId,
                ':movie_id' => $movieId,
                ':rating' => $rating
            ]);
        } catch (PDOException $e) {
            error_log("RatingRepository::upsert error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Buscar avaliação específica de um usuário para um filme
     * @param int $userId
     * @param int $movieId
     * @return array|false
     */
    public function findByUserAndMovie($userId, $movieId) {
        try {
            $sql = "SELECT r.*, m.title, m.poster_path, m.tmdb_id, m.genre_ids, m.popularity
                    FROM ratings r
                    JOIN movies m ON r.movie_id = m.id
                    WHERE r.user_id = ? AND r.movie_id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$userId, $movieId]);
            $rating = $stmt->fetch();
            
            if ($rating && !empty($rating['genre_ids'])) {
                $rating['genre_ids'] = json_decode($rating['genre_ids'], true);
            }
            
            return $rating;
        } catch (PDOException $e) {
            error_log("RatingRepository::findByUserAndMovie error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Listar todas avaliações de um usuário (com dados do filme)
     * @param int $userId
     * @param int $limit
     * @param int $offset
     * @return array
     */
    public function findByUser($userId, $limit = 50, $offset = 0) {
        try {
            $sql = "SELECT r.*, m.title, m.poster_path, m.tmdb_id, m.genre_ids, m.popularity
                    FROM ratings r
                    JOIN movies m ON r.movie_id = m.id
                    WHERE r.user_id = ?
                    ORDER BY r.created_at DESC
                    LIMIT ? OFFSET ?";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(1, $userId, PDO::PARAM_INT);
            $stmt->bindValue(2, $limit, PDO::PARAM_INT);
            $stmt->bindValue(3, $offset, PDO::PARAM_INT);
            $stmt->execute();
            
            $ratings = $stmt->fetchAll();
            
            // Decodificar genre_ids para cada filme
            foreach ($ratings as &$rating) {
                if (!empty($rating['genre_ids'])) {
                    $rating['genre_ids'] = json_decode($rating['genre_ids'], true);
                }
            }
            
            return $ratings;
        } catch (PDOException $e) {
            error_log("RatingRepository::findByUser error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Buscar média de avaliações de um filme
     * @param int $movieId
     * @return array {average, total_count}
     */
    public function getMovieAverage($movieId) {
        try {
            $sql = "SELECT 
                    AVG(rating) as average,
                    COUNT(*) as total_count
                    FROM ratings 
                    WHERE movie_id = ?";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$movieId]);
            $result = $stmt->fetch();
            
            return [
                'average' => round($result['average'] ?? 0, 1),
                'total_count' => (int)($result['total_count'] ?? 0)
            ];
        } catch (PDOException $e) {
            error_log("RatingRepository::getMovieAverage error: " . $e->getMessage());
            return ['average' => 0, 'total_count' => 0];
        }
    }
    
    /**
     * Deletar avaliação
     * @param int $userId
     * @param int $movieId
     * @return bool
     */
    public function delete($userId, $movieId) {
        try {
            $sql = "DELETE FROM ratings WHERE user_id = ? AND movie_id = ?";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([$userId, $movieId]);
        } catch (PDOException $e) {
            error_log("RatingRepository::delete error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Verificar se usuário já avaliou um filme
     * @param int $userId
     * @param int $movieId
     * @return bool
     */
    public function hasRated($userId, $movieId) {
        try {
            $sql = "SELECT 1 FROM ratings WHERE user_id = ? AND movie_id = ? LIMIT 1";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$userId, $movieId]);
            return (bool)$stmt->fetch();
        } catch (PDOException $e) {
            return false;
        }
    }
    
    /**
     * Contar quantas avaliações um usuário fez
     * @param int $userId
     * @return int
     */
    public function countByUser($userId) {
        try {
            $sql = "SELECT COUNT(*) as total FROM ratings WHERE user_id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$userId]);
            $result = $stmt->fetch();
            return (int)($result['total'] ?? 0);
        } catch (PDOException $e) {
            return 0;
        }
    }
    
    /**
     * Buscar filmes mais bem avaliados (para recomendações futuras)
     * @param int $limit
     * @return array
     */
    public function getTopRatedMovies($limit = 20) {
        try {
            $sql = "SELECT 
                    m.id, m.title, m.tmdb_id, m.poster_path, m.genre_ids,
                    AVG(r.rating) as average_rating,
                    COUNT(r.id) as rating_count
                    FROM movies m
                    JOIN ratings r ON m.id = r.movie_id
                    GROUP BY m.id
                    HAVING rating_count >= 3
                    ORDER BY average_rating DESC
                    LIMIT ?";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(1, $limit, PDO::PARAM_INT);
            $stmt->execute();
            $movies = $stmt->fetchAll();
            
            foreach ($movies as &$movie) {
                if (!empty($movie['genre_ids'])) {
                    $movie['genre_ids'] = json_decode($movie['genre_ids'], true);
                }
            }
            
            return $movies;
        } catch (PDOException $e) {
            error_log("RatingRepository::getTopRatedMovies error: " . $e->getMessage());
            return [];
        }
    }
}
?>
