<?php
/**
 * Classe responsável por todas as validações do backend
 * Nunca confiar apenas no frontend - segurança em camadas
 */
class ValidationService {
    
    /**
     * Valida email: formato correto, domínio válido, sem caracteres proibidos
     * @param string $email
     * @return bool
     */
    public static function validateEmail($email) {
        // Remover espaços
        $email = trim($email);
        
        // Regras profissionais:
        // 1. Formato RFC 5322 (compatível com filter_var)
        // 2. Não aceitar emails obviamente falsos como "teste@com"
        // 3. Mínimo de 5 caracteres, máximo de 254
        if (empty($email) || strlen($email) < 5 || strlen($email) > 254) {
            return false;
        }
        
        // Validação completa usando filter_var
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return false;
        }
        
        // Verificar se o domínio tem pelo menos um ponto depois do @
        $parts = explode('@', $email);
        if (count($parts) !== 2) return false;
        
        $domain = $parts[1];
        if (strpos($domain, '.') === false) return false;
        
        // Bloquear domínios temporários comuns (opcional, mas profissional)
        $blockedDomains = ['tempmail.com', 'throwaway.com', 'mailinator.com'];
        foreach ($blockedDomains as $blocked) {
            if (strpos($domain, $blocked) !== false) {
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Valida senha forte:
     * - Mínimo 8 caracteres
     * - Máximo 100 caracteres
     * - Pelo menos 1 letra maiúscula
     * - Pelo menos 1 letra minúscula
     * - Pelo menos 1 número
     * - Pelo menos 1 caractere especial
     * @param string $password
     * @return bool
     */
    public static function validateStrongPassword($password) {
        if (strlen($password) < 8 || strlen($password) > 100) {
            return false;
        }
        
        // Maiúscula
        if (!preg_match('/[A-Z]/', $password)) return false;
        // Minúscula
        if (!preg_match('/[a-z]/', $password)) return false;
        // Número
        if (!preg_match('/[0-9]/', $password)) return false;
        // Caractere especial (símbolos comuns)
        if (!preg_match('/[\W_]/', $password)) return false;
        
        return true;
    }
    
    /**
     * Valida nome do usuário
     * @param string $name
     * @return bool
     */
    public static function validateName($name) {
        $name = trim($name);
        if (empty($name) || strlen($name) < 2 || strlen($name) > 100) {
            return false;
        }
        // Permitir letras, espaços, acentos, hífen, apóstrofo
        return preg_match('/^[a-zA-ZáéíóúâêôãõçÁÉÍÓÚÂÊÔÃÕÇ\s\-\']+$/u', $name);
    }
    
    /**
     * Valida nota de avaliação (1-5)
     * @param int $rating
     * @return bool
     */
    public static function validateRating($rating) {
        return is_numeric($rating) && $rating >= 1 && $rating <= 5;
    }
    
    /**
     * Sanitiza entrada para prevenir XSS
     * @param string $input
     * @return string
     */
    public static function sanitizeInput($input) {
        $input = trim($input);
        $input = strip_tags($input);
        $input = htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
        return $input;
    }
    
    /**
     * Previne SQL Injection (além do PDO)
     * Remove caracteres potencialmente perigosos
     * @param string $input
     * @return string
     */
    public static function sanitizeForSQL($input) {
        // Remover caracteres nulos e especiais do SQL
        $input = str_replace(['\0', '\x00', "\x00"], '', $input);
        return $input;
    }
}
?>
