<?php
// Não inicia a sessão aqui, pois já é iniciada no header.php
if (!function_exists('dd')) {
    function dd($data) {
        echo '<pre>';
        var_dump($data);
        echo '</pre>';
        die();
    }
}

if (!function_exists('formatMoney')) {
    function formatMoney($value) {
        return 'R$ ' . number_format($value, 2, ',', '.');
    }
}

if (!function_exists('getMonthName')) {
    function getMonthName($month) {
        $months = [
            1 => 'Janeiro',
            2 => 'Fevereiro',
            3 => 'Março',
            4 => 'Abril',
            5 => 'Maio',
            6 => 'Junho',
            7 => 'Julho',
            8 => 'Agosto',
            9 => 'Setembro',
            10 => 'Outubro',
            11 => 'Novembro',
            12 => 'Dezembro'
        ];
        return $months[$month] ?? '';
    }
}

if (!function_exists('sanitizeInput')) {
    function sanitizeInput($data) {
        if (is_array($data)) {
            return array_map('sanitizeInput', $data);
        }
        $data = trim($data);
        $data = stripslashes($data);
        $data = htmlspecialchars($data);
        return $data;
    }
}

if (!function_exists('generateToken')) {
    function generateToken() {
        return bin2hex(random_bytes(32));
    }
}

if (!function_exists('validateToken')) {
    function validateToken($token) {
        return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }
}

if (!function_exists('getCurrentMonthYear')) {
    function getCurrentMonthYear() {
        return [
            'month' => date('n'),
            'year' => date('Y')
        ];
    }
}

if (!function_exists('getTransactionIcon')) {
    function getTransactionIcon($type) {
        return $type === 'income' ? 'fa-arrow-up text-success' : 'fa-arrow-down text-danger';
    }
}

if (!function_exists('getTransactionClass')) {
    function getTransactionClass($type) {
        return $type === 'income' ? 'success' : 'danger';
    }
}

if (!function_exists('isAjaxRequest')) {
    function isAjaxRequest() {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
               strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
    }
}

if (!function_exists('jsonResponse')) {
    function jsonResponse($data, $statusCode = 200) {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }
}

if (!function_exists('redirect')) {
    function redirect($path) {
        header("Location: $path");
        exit;
    }
}

if (!function_exists('isLoggedIn')) {
    function isLoggedIn() {
        return isset($_SESSION['user_id']);
    }
}

if (!function_exists('isAdmin')) {
    function isAdmin() {
        $user = getUser();
        return $user && $user['is_admin'];
    }
}

if (!function_exists('getUserId')) {
    function getUserId() {
        return $_SESSION['user_id'] ?? null;
    }
}

if (!function_exists('getUser')) {
    function getUser() {
        global $pdo;
        $userId = getUserId();
        if (!$userId) return null;
        
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        return $stmt->fetch();
    }
}

if (!function_exists('requireLogin')) {
    function requireLogin() {
        if (!isLoggedIn()) {
            $_SESSION['flash_message'] = 'Você precisa estar logado para acessar esta página.';
            $_SESSION['flash_type'] = 'warning';
            redirect('/login.php');
        }
    }
}

if (!function_exists('requireAdmin')) {
    function requireAdmin() {
        requireLogin();
        if (!isAdmin()) {
            $_SESSION['flash_message'] = 'Você não tem permissão para acessar esta página.';
            $_SESSION['flash_type'] = 'danger';
            redirect('/');
        }
    }
}

if (!function_exists('isValidDate')) {
    function isValidDate($date) {
        if (empty($date)) return false;
        
        try {
            $d = new DateTime($date);
            return true;
        } catch (Exception $e) {
            return false;
        }
    }
}

if (!function_exists('dateToMysql')) {
    function dateToMysql($date) {
        if (empty($date)) return null;
        $d = DateTime::createFromFormat('d/m/Y', $date);
        return $d ? $d->format('Y-m-d') : null;
    }
}

if (!function_exists('dateFromMysql')) {
    function dateFromMysql($date) {
        if (empty($date)) return '';
        $d = DateTime::createFromFormat('Y-m-d', $date);
        return $d ? $d->format('d/m/Y') : '';
    }
}

if (!function_exists('validateMoney')) {
    function validateMoney($value) {
        return is_numeric($value) && $value > 0;
    }
}

if (!function_exists('logError')) {
    function logError($message, $context = []) {
        error_log(date('Y-m-d H:i:s') . " - " . $message . " - Contexto: " . json_encode($context));
    }
}
