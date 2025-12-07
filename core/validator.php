<?php
/**
 * Класс для валидации входных данных
 * Защита от XSS, SQL Injection, Command Injection
 */

class Validator {
    private $errors = [];
    
    /**
     * Очистка строки от HTML тегов и специальных символов (защита от XSS)
     * @param string $data Данные для очистки
     * @return string Очищенные данные
     */
    public static function sanitize($data) {
        if (is_array($data)) {
            return array_map([self::class, 'sanitize'], $data);
        }
        
        // Удаляем пробелы по краям
        $data = trim($data);
        // Удаляем слэши
        $data = stripslashes($data);
        // Преобразуем специальные символы в HTML сущности
        $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
        
        return $data;
    }
    
    /**
     * Валидация email
     * @param string $email Email адрес
     * @return bool Валиден ли email
     */
    public function email($email) {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->errors[] = 'Неверный формат email';
            return false;
        }
        return true;
    }
    
    /**
     * Валидация имени пользователя
     * @param string $username Имя пользователя
     * @return bool Валидно ли имя
     */
    public function username($username) {
        // Только буквы, цифры и подчеркивание, длина 3-20 символов
        if (!preg_match('/^[a-zA-Z0-9_]{3,20}$/', $username)) {
            $this->errors[] = 'Имя пользователя должно содержать 3-20 символов (буквы, цифры, _)';
            return false;
        }
        return true;
    }
    
    /**
     * Валидация пароля согласно политике безопасности
     * @param string $password Пароль
     * @return bool Валиден ли пароль
     */
    public function password($password) {
        if (strlen($password) < PASSWORD_MIN_LENGTH) {
            $this->errors[] = 'Пароль должен содержать минимум ' . PASSWORD_MIN_LENGTH . ' символов';
            return false;
        }
        
        if (PASSWORD_REQUIRE_UPPERCASE && !preg_match('/[A-Z]/', $password)) {
            $this->errors[] = 'Пароль должен содержать заглавную букву';
            return false;
        }
        
        if (PASSWORD_REQUIRE_LOWERCASE && !preg_match('/[a-z]/', $password)) {
            $this->errors[] = 'Пароль должен содержать строчную букву';
            return false;
        }
        
        if (PASSWORD_REQUIRE_DIGIT && !preg_match('/[0-9]/', $password)) {
            $this->errors[] = 'Пароль должен содержать цифру';
            return false;
        }
        
        if (PASSWORD_REQUIRE_SPECIAL && !preg_match('/[^a-zA-Z0-9]/', $password)) {
            $this->errors[] = 'Пароль должен содержать специальный символ';
            return false;
        }
        
        return true;
    }
    
    /**
     * Валидация числа
     * @param mixed $value Значение
     * @param float $min Минимальное значение
     * @param float $max Максимальное значение
     * @return bool Валидно ли число
     */
    public function number($value, $min = null, $max = null) {
        if (!is_numeric($value)) {
            $this->errors[] = 'Значение должно быть числом';
            return false;
        }
        
        if ($min !== null && $value < $min) {
            $this->errors[] = "Значение должно быть не менее $min";
            return false;
        }
        
        if ($max !== null && $value > $max) {
            $this->errors[] = "Значение должно быть не более $max";
            return false;
        }
        
        return true;
    }
    
    /**
     * Валидация обязательного поля
     * @param mixed $value Значение
     * @param string $fieldName Название поля
     * @return bool Заполнено ли поле
     */
    public function required($value, $fieldName = 'Поле') {
        if (empty($value) && $value !== '0') {
            $this->errors[] = "$fieldName обязательно для заполнения";
            return false;
        }
        return true;
    }
    
    /**
     * Валидация длины строки
     * @param string $value Строка
     * @param int $min Минимальная длина
     * @param int $max Максимальная длина
     * @return bool Валидна ли длина
     */
    public function length($value, $min = 0, $max = PHP_INT_MAX) {
        $length = mb_strlen($value, 'UTF-8');
        
        if ($length < $min) {
            $this->errors[] = "Минимальная длина: $min символов";
            return false;
        }
        
        if ($length > $max) {
            $this->errors[] = "Максимальная длина: $max символов";
            return false;
        }
        
        return true;
    }
    
    /**
     * Получение списка ошибок
     * @return array Массив ошибок валидации
     */
    public function getErrors() {
        return $this->errors;
    }
    
    /**
     * Проверка наличия ошибок
     * @return bool Есть ли ошибки
     */
    public function hasErrors() {
        return !empty($this->errors);
    }
}
?>