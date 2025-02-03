<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Iniciando script de criação do usuário admin...\n";

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

try {
    echo "Conectado ao banco de dados com sucesso.\n";
    
    // Adicionar coluna is_admin diretamente via SQL
    echo "Verificando e adicionando coluna is_admin...\n";
    try {
        $pdo->exec("ALTER TABLE users ADD COLUMN IF NOT EXISTS is_admin BOOLEAN DEFAULT FALSE");
        echo "Coluna is_admin verificada/adicionada com sucesso.\n";
    } catch(PDOException $e) {
        // Se a coluna já existe, ignora o erro
        if ($e->getCode() != '42S21') {
            throw $e;
        }
        echo "Coluna is_admin já existe.\n";
    }
    
    // Configurações do usuário admin
    $adminUser = [
        'username' => 'Administrador',
        'email' => 'admin@sistema.com',
        'password' => 'admin@123',
        'is_admin' => true
    ];

    echo "Verificando se já existe um usuário admin...\n";
    // Verifica se já existe um usuário admin
    $stmt = $pdo->prepare("SELECT id, email FROM users WHERE email = ? OR is_admin = TRUE");
    $stmt->execute([$adminUser['email']]);
    $existingUser = $stmt->fetch();
    
    if ($existingUser) {
        echo "AVISO: Já existe um usuário administrador no sistema.\n";
        echo "Email existente: " . $existingUser['email'] . "\n";
        exit;
    }
    
    echo "Criando novo usuário admin...\n";
    // Cria o usuário admin
    $stmt = $pdo->prepare("
        INSERT INTO users (username, email, password, is_admin)
        VALUES (?, ?, ?, ?)
    ");
    
    $result = $stmt->execute([
        $adminUser['username'],
        $adminUser['email'],
        password_hash($adminUser['password'], PASSWORD_DEFAULT),
        $adminUser['is_admin']
    ]);
    
    if ($result) {
        echo "\nUsuário administrador criado com sucesso!\n";
        echo "----------------------------------------\n";
        echo "Email: " . $adminUser['email'] . "\n";
        echo "Senha: " . $adminUser['password'] . "\n";
        echo "----------------------------------------\n";
        echo "Por favor, altere a senha após o primeiro login.\n";
    } else {
        echo "ERRO: Não foi possível criar o usuário admin.\n";
        print_r($stmt->errorInfo());
    }
    
} catch(PDOException $e) {
    echo "ERRO PDO: " . $e->getMessage() . "\n";
    echo "Código do erro: " . $e->getCode() . "\n";
} catch(Exception $e) {
    echo "ERRO: " . $e->getMessage() . "\n";
}
