<?php
require_once __DIR__ . '/../config/database.php';

class MovieRepository {
    private $db;
    
    public function __construct() {
        $this->db = getDB();
    }
    
    /**
     * Salva ou atualiza um filme no banco local (cache)
     * @param array $movieData Dados do TMDb
     * @return int|false ID do filme
     */
    public function saveOrUpdate($movieData) {
        try {
            $sql = "INSERT INTO movies (tmdb_id, title, overview, poster_path, release_date, genre_ids, popularity) 
                    VALUES (:tmdb_id, :title, :overview, :poster_path, :release_date, :genre_ids, :popularity)
                    ON DUPLICATE KEY UPDATE
                    title = VALUES(title),
                    overview = VALUES(overview),
                    poster_path = VALUES(poster_path),
                    release_date = VALUES(release_date),
                    genre_ids = VALUES(genre_ids),
                    popularity = VALUES(popularity)";
            
            $stmt = $this->db->prepare($sql);
            
            // Converter genre_ids array para JSON string
            $genreIds = isset($movieData['genre_ids']) ? json_encode($movieData['genre_ids']) : '[]';
            
            // Tratar release_date (pode vir vazio)
            $releaseDate = !empty($movieData['release_date']) ? $movieData['release_date'] : null;
            
            $stmt->execute([
                ':tmdb_id' => $movieData['id'],
                ':title' => $movieData['title'],
                ':overview' => $movieData['overview'] ?? '',
                ':poster_path' => $movieData['poster_path'] ?? '',
                ':release_date' => $releaseDate,
                ':genre_ids' => $genreIds,
                ':popularity' => $movieData['popularity'] ?? 0
            ]);
            
            return $this->db->lastInsertId() ?: $this->findByTmdbId($movieData['id'])['id'] ?? false;
            
        } catch (PDOException $e) {
            error_log("MovieRepository::saveOrUpdate error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Busca filme por ID do TMDb
     * @param int $tmdbId
     * @return array|false
     */
    public function findByTmdbId($tmdbId) {
        try {
            $sql = "SELECT * FROM movies WHERE tmdb_id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$tmdbId]);
            $movie = $stmt->fetch();
            
            if ($movie && $movie['genre_ids']) {
                $movie['genre_ids'] = json_decode($movie['genre_ids'], true);
            }
            
            return $movie;
        } catch (PDOException $e) {
            error_log("MovieRepository::findByTmdbId error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Busca filme por ID interno
     * @param int $id
     * @return array|false
     */
    public function findById($id) {
        try {
            $sql = "SELECT * FROM movies WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$id]);
            $movie = $stmt->fetch();
            
            if ($movie && $movie['genre_ids']) {
                $movie['genre_ids'] = json_decode($movie['genre_ids'], true);
            }
            
            return $movie;
        } catch (PDOException $e) {
            error_log("MovieRepository::findById error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Busca filmes por termo de busca (título)
     * @param string $query
     * @param int $limit
     * @return array
     */
    public function search($query, $limit = 20) {
        try {
            $sql = "SELECT * FROM movies WHERE title LIKE :query ORDER BY popularity DESC LIMIT :limit";
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':query', "%{$query}%", PDO::PARAM_STR);
            $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
            $stmt->execute();
            
            $movies = $stmt->fetchAll();
            foreach ($movies as &$movie) {
                if ($movie['genre_ids']) {
                    $movie['genre_ids'] = json_decode($movie['genre_ids'], true);
                }
            }
            
            return $movies;
        } catch (PDOException $e) {
            error_log("MovieRepository::search error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Busca filmes populares
     * @param int $limit
     * @return array
     */
    public function getPopular($limit = 20) {
        try {
            $sql = "SELECT * FROM movies ORDER BY popularity DESC LIMIT :limit";
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
            $stmt->execute();
            
            $movies = $stmt->fetchAll();
            foreach ($movies as &$movie) {
                if ($movie['genre_ids']) {
                    $movie['genre_ids'] = json_decode($movie['genre_ids'], true);
                }
            }
            
            return $movies;
        } catch (PDOException $e) {
            error_log("MovieRepository::getPopular error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Verifica se um filme existe no cache local
     * @param int $tmdbId
     * @return bool
     */
    public function exists($tmdbId) {
        try {
            $sql = "SELECT 1 FROM movies WHERE tmdb_id = ? LIMIT 1";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$tmdbId]);
            return (bool)$stmt->fetch();
        } catch (PDOException $e) {
            return false;
        }
    }
}
?>
