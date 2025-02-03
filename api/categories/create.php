<?php
require_once '../../includes/auth.php';

// Verifica se é uma requisição POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['success' => false, 'message' => 'Método não permitido'], 405);
}

// Verifica autenticação
if (!isLoggedIn()) {
    jsonResponse(['success' => false, 'message' => 'Não autorizado'], 401);
}

// Valida os dados recebidos
$name = sanitizeInput($_POST['name'] ?? '');
$type = sanitizeInput($_POST['type'] ?? '');

// Validações
if (empty($name)) {
    jsonResponse(['success' => false, 'message' => 'Nome é obrigatório']);
}

if (!in_array($type, ['income', 'expense'])) {
    jsonResponse(['success' => false, 'message' => 'Tipo inválido']);
}

try {
    // Verifica se já existe uma categoria com o mesmo nome para o usuário
    $stmt = $pdo->prepare("
        SELECT id FROM categories 
        WHERE name = ? AND type = ? AND (user_id = ? OR user_id IS NULL)
    ");
    $stmt->execute([$name, $type, $_SESSION['user_id']]);
    
    if ($stmt->fetch()) {
        jsonResponse(['success' => false, 'message' => 'Já existe uma categoria com este nome']);
    }
    
    // Insere a nova categoria
    $stmt = $pdo->prepare("
        INSERT INTO categories (name, type, user_id)
        VALUES (?, ?, ?)
    ");
    
    $stmt->execute([$name, $type, $_SESSION['user_id']]);
    
    jsonResponse([
        'success' => true,
        'message' => 'Categoria criada com sucesso',
        'category_id' => $pdo->lastInsertId()
    ]);
    
} catch(PDOException $e) {
    error_log('Erro ao criar categoria: ' . $e->getMessage());
    jsonResponse(['success' => false, 'message' => 'Erro ao criar categoria'], 500);
}
