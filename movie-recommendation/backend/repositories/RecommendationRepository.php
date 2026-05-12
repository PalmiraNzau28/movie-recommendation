<?php
require_once __DIR__ . '/../config/database.php';

class RecommendationRepository {
    private $db;
    
    public function __construct() {
        $this->db = getDB();
    }
    
    /**
     * Registrar log de recomendação gerada
     * @param int $userId
     * @param array $recommendedMovieIds
     * @param string $algorithm
     * @return bool
     */
    public function logRecommendation($userId, $recommendedMovieIds, $algorithm = 'genre_based') {
        try {
            $sql = "INSERT INTO recommendations_log (user_id, movie_ids, algorithm) VALUES (?, ?, ?)";
            $stmt = $this->db->prepare($sql);
            $movieIdsJson = json_encode($recommendedMovieIds);
            return $stmt->execute([$userId, $movieIdsJson, $algorithm]);
        } catch (PDOException $e) {
            error_log("RecommendationRepository::logRecommendation error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Buscar histórico de recomendações do usuário
     * @param int $userId
     * @param int $limit
     * @return array
     */
    public function getRecommendationHistory($userId, $limit = 10) {
        try {
            $sql = "SELECT * FROM recommendations_log WHERE user_id = ? ORDER BY created_at DESC LIMIT ?";
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(1, $userId, PDO::PARAM_INT);
            $stmt->bindValue(2, $limit, PDO::PARAM_INT);
            $stmt->execute();
            
            $logs = $stmt->fetchAll();
            foreach ($logs as &$log) {
                $log['movie_ids'] = json_decode($log['movie_ids'], true);
            }
            
            return $logs;
        } catch (PDOException $e) {
            error_log("RecommendationRepository::getRecommendationHistory error: " . $e->getMessage());
            return [];
        }
    }
}
?>