<?php
require_once __DIR__ . '/../repositories/RatingRepository.php';
require_once __DIR__ . '/ValidationService.php';

class RatingService {
    private $ratingRepo;
    
    public function __construct() {
        $this->ratingRepo = new RatingRepository();
    }
    
    /**
     * Validar e salvar avaliação
     * @param int $userId
     * @param int $movieId
     * @param int $rating
     * @return array {success, message, errors}
     */
    public function saveRating($userId, $movieId, $rating) {
        $errors = [];
        
        // Validação do rating
        if (!ValidationService::validateRating($rating)) {
            $errors['rating'] = 'Rating must be between 1 and 5';
        }
        
        if ($movieId <= 0) {
            $errors['movie_id'] = 'Invalid movie ID';
        }
        
        if ($userId <= 0) {
            $errors['user_id'] = 'Invalid user ID';
        }
        
        if (!empty($errors)) {
            return ['success' => false, 'message' => 'Validation failed', 'errors' => $errors];
        }
        
        $result = $this->ratingRepo->upsert($userId, $movieId, $rating);
        
        if (!$result) {
            return ['success' => false, 'message' => 'Failed to save rating', 'errors' => null];
        }
        
        // Buscar média atualizada do filme
        $average = $this->ratingRepo->getMovieAverage($movieId);
        
        return [
            'success' => true, 
            'message' => 'Rating saved successfully',
            'data' => [
                'user_id' => $userId,
                'movie_id' => $movieId,
                'rating' => $rating,
                'movie_average' => $average['average'],
                'total_ratings' => $average['total_count']
            ]
        ];
    }
    
    /**
     * Buscar avaliação do usuário para um filme específico
     * @param int $userId
     * @param int $movieId
     * @return array|null
     */
    public function getUserRatingForMovie($userId, $movieId) {
        $rating = $this->ratingRepo->findByUserAndMovie($userId, $movieId);
        if (!$rating) {
            return null;
        }
        
        $average = $this->ratingRepo->getMovieAverage($movieId);
        
        return [
            'rating' => $rating['rating'],
            'rated_at' => $rating['created_at'],
            'movie_average' => $average['average'],
            'total_ratings' => $average['total_count']
        ];
    }
    
    /**
     * Listar todas avaliações do usuário com detalhes dos filmes
     * @param int $userId
     * @param int $page
     * @param int $perPage
     * @return array
     */
    public function getUserRatings($userId, $page = 1, $perPage = 20) {
        $offset = ($page - 1) * $perPage;
        $ratings = $this->ratingRepo->findByUser($userId, $perPage, $offset);
        $total = $this->ratingRepo->countByUser($userId);
        
        return [
            'data' => $ratings,
            'pagination' => [
                'current_page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'total_pages' => ceil($total / $perPage)
            ]
        ];
    }
    
    /**
     * Deletar avaliação
     * @param int $userId
     * @param int $movieId
     * @return array
     */
    public function deleteRating($userId, $movieId) {
        if (!$this->ratingRepo->hasRated($userId, $movieId)) {
            return ['success' => false, 'message' => 'Rating not found'];
        }
        
        $result = $this->ratingRepo->delete($userId, $movieId);
        
        if (!$result) {
            return ['success' => false, 'message' => 'Failed to delete rating'];
        }
        
        return ['success' => true, 'message' => 'Rating deleted successfully'];
    }
    
    /**
     * Buscar média e estatísticas de um filme
     * @param int $movieId
     * @return array
     */
    public function getMovieStats($movieId) {
        $average = $this->ratingRepo->getMovieAverage($movieId);
        $distribution = $this->getRatingDistribution($movieId);
        
        return [
            'average' => $average['average'],
            'total_ratings' => $average['total_count'],
            'distribution' => $distribution
        ];
    }
    
    /**
     * Distribuição de notas de um filme (quantos deram 1, 2, 3, 4, 5)
     * @param int $movieId
     * @return array
     */
    private function getRatingDistribution($movieId) {
        try {
            $db = getDB();
            $sql = "SELECT rating, COUNT(*) as count 
                    FROM ratings 
                    WHERE movie_id = ? 
                    GROUP BY rating 
                    ORDER BY rating";
            $stmt = $db->prepare($sql);
            $stmt->execute([$movieId]);
            $results = $stmt->fetchAll();
            
            $distribution = [1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0];
            foreach ($results as $row) {
                $distribution[(int)$row['rating']] = (int)$row['count'];
            }
            
            return $distribution;
        } catch (PDOException $e) {
            return [1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0];
        }
    }
}
?>