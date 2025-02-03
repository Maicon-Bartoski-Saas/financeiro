<?php
// Configurações do banco de dados
$host = 'localhost';
$dbname = 'seu_banco';
$user = 'seu_usuario';
$pass = 'sua_senha)V';

// Configuração do fuso horário
date_default_timezone_set('America/Sao_Paulo');

// Configuração de erro
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../logs/error.log');

try {
    $dsn = "mysql:host=$host;dbname=$dbname;charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
    ];
    
    $pdo = new PDO($dsn, $user, $pass, $options);
    
} catch(PDOException $e) {
    error_log('Erro de conexão: ' . $e->getMessage());
    die('Erro ao conectar ao banco de dados. Por favor, tente novamente mais tarde.');
}

// Cria diretório de logs se não existir
if (!file_exists(__DIR__ . '/../logs')) {
    mkdir(__DIR__ . '/../logs', 0777, true);
}
