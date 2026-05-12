<?php
/**
 * Serviço de integração com The Movie Database API v3
 * Documentação: https://developers.themoviedb.org/3
 */
class TmdbService {
    private $apiKey;
    private $baseUrl = 'https://api.themoviedb.org/3';
    private $imageBaseUrl = 'https://image.tmdb.org/t/p';
    
    /**
     * @param string $apiKey Sua chave da API TMDb
     */
    public function __construct($apiKey) {
        $this->apiKey = $apiKey;
    }
    
    /**
     * Busca filmes por termo de pesquisa
     * @param string $query
     * @param int $page
     * @return array
     */
    public function searchMovies($query, $page = 1) {
        $url = $this->baseUrl . '/search/movie';
        $params = [
            'api_key' => $this->apiKey,
            'query' => urlencode($query),
            'page' => $page,
            'language' => 'pt-BR'
        ];
        
        $response = $this->makeRequest($url, $params);
        
        if (!$response || !isset($response['results'])) {
            return ['results' => [], 'total_pages' => 0];
        }
        
        return [
            'results' => $response['results'],
            'total_pages' => $response['total_pages'],
            'total_results' => $response['total_results']
        ];
    }
    
    /**
     * Busca filmes populares
     * @param int $page
     * @return array
     */
    public function getPopularMovies($page = 1) {
        $url = $this->baseUrl . '/movie/popular';
        $params = [
            'api_key' => $this->apiKey,
            'page' => $page,
            'language' => 'pt-BR'
        ];
        
        $response = $this->makeRequest($url, $params);
        
        if (!$response || !isset($response['results'])) {
            return ['results' => [], 'total_pages' => 0];
        }
        
        return [
            'results' => $response['results'],
            'total_pages' => $response['total_pages']
        ];
    }
    
    /**
     * Busca detalhes de um filme específico
     * @param int $movieId (ID do TMDb)
     * @return array|false
     */
    public function getMovieDetails($movieId) {
        $url = $this->baseUrl . "/movie/{$movieId}";
        $params = [
            'api_key' => $this->apiKey,
            'language' => 'pt-BR',
            'append_to_response' => 'credits,videos'
        ];
        
        $response = $this->makeRequest($url, $params);
        
        if (!$response || isset($response['status_code'])) {
            return false;
        }
        
        return $response;
    }
    
    /**
     * Faz requisição HTTP para a API
     * @param string $url
     * @param array $params
     * @return array|false
     */
    private function makeRequest($url, $params = []) {
        $fullUrl = $url . '?' . http_build_query($params);
        
        // Usar cURL para a requisição
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $fullUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_USERAGENT, 'MovieRecommendationSystem/1.0');
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode !== 200) {
            error_log("TMDb API error: HTTP {$httpCode} for {$fullUrl}");
            return false;
        }
        
        $data = json_decode($response, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log("TMDb API JSON error: " . json_last_error_msg());
            return false;
        }
        
        return $data;
    }
    
    /**
     * Retorna URL completa do pôster
     * @param string $posterPath
     * @param string $size (w92, w154, w185, w342, w500, w780, original)
     * @return string
     */
    public function getPosterUrl($posterPath, $size = 'w500') {
        if (empty($posterPath)) {
            return '';
        }
        return $this->imageBaseUrl . "/{$size}{$posterPath}";
    }
}
?>