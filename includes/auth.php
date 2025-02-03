<?php
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/functions.php';

function loginUser($email, $password) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        
        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['csrf_token'] = generateToken();
            return true;
        }
        
        return false;
    } catch(PDOException $e) {
        error_log('Erro no login: ' . $e->getMessage());
        return false;
    }
}

function registerUser($username, $email, $password) {
    global $pdo;
    
    try {
        // Verifica se o email já existe
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            return ['success' => false, 'message' => 'Email já cadastrado'];
        }
        
        // Cria o novo usuário
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
        $stmt->execute([$username, $email, $hashedPassword]);
        
        return ['success' => true, 'message' => 'Usuário registrado com sucesso'];
    } catch(PDOException $e) {
        error_log('Erro no registro: ' . $e->getMessage());
        return ['success' => false, 'message' => 'Erro ao registrar usuário'];
    }
}

function updatePassword($userId, $currentPassword, $newPassword) {
    global $pdo;
    
    try {
        // Verifica a senha atual
        $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch();
        
        if (!$user || !password_verify($currentPassword, $user['password'])) {
            return ['success' => false, 'message' => 'Senha atual incorreta'];
        }
        
        // Atualiza a senha
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
        $stmt->execute([$hashedPassword, $userId]);
        
        return ['success' => true, 'message' => 'Senha atualizada com sucesso'];
    } catch(PDOException $e) {
        error_log('Erro na atualização de senha: ' . $e->getMessage());
        return ['success' => false, 'message' => 'Erro ao atualizar senha'];
    }
}

function logout() {
    session_destroy();
    session_start();
    redirect('/login.php');
}
