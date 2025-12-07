<?php
/**
 * Класс для журналирования событий безопасности и ошибок
 */

class Logger {
    /**
     * Логирование событий безопасности
     * @param string $message Сообщение
     * @param array $context Дополнительный контекст
     */
    public static function security($message, $context = []) {
        $logFile = LOG_PATH . 'security.log';
        self::writeLog($logFile, 'SECURITY', $message, $context);
    }
    
    /**
     * Логирование ошибок
     * @param string $message Сообщение об ошибке
     * @param array $context Дополнительный контекст
     */
    public static function error($message, $context = []) {
        $logFile = LOG_PATH . 'errors.log';
        self::writeLog($logFile, 'ERROR', $message, $context);
    }
    
    /**
     * Логирование информационных сообщений
     */
    public static function info($message, $context = []) {
        $logFile = LOG_PATH . 'security.log';
        self::writeLog($logFile, 'INFO', $message, $context);
    }
    
    /**
     * Общий метод записи в лог
     * @param string $file Файл лога
     * @param string $level Уровень (INFO, ERROR, SECURITY)
     * @param string $message Сообщение
     * @param array $context Контекст
     */
    private static function writeLog($file, $level, $message, $context = []) {
        // Создаем директорию логов если не существует
        if (!file_exists(LOG_PATH)) {
            mkdir(LOG_PATH, 0750, true);
        }
        
        $timestamp = date('Y-m-d H:i:s');
        $ip = self::getClientIp();
        $userId = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 'guest';
        
        // Формируем строку лога без конфиденциальных данных
        $contextStr = !empty($context) ? json_encode(self::sanitizeContext($context)) : '';
        $logEntry = sprintf(
            "[%s] [%s] [User: %s] [IP: %s] %s %s\n",
            $timestamp,
            $level,
            $userId,
            $ip,
            $message,
            $contextStr
        );
        
        // Записываем в файл
        file_put_contents($file, $logEntry, FILE_APPEND | LOCK_EX);
    }
    
    /**
     * Очистка контекста от конфиденциальных данных
     */
    private static function sanitizeContext($context) {
        $sensitive = ['password', 'password_hash', 'token', 'secret', 'api_key'];
        
        foreach ($context as $key => $value) {
            if (in_array(strtolower($key), $sensitive)) {
                $context[$key] = '[REDACTED]';
            }
        }
        
        return $context;
    }
    
    /**
     * Получение IP адреса клиента
     */
    private static function getClientIp() {
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        
        // Валидация IP адреса
        if (filter_var($ip, FILTER_VALIDATE_IP)) {
            return $ip;
        }
        
        return 'unknown';
    }
}
?>