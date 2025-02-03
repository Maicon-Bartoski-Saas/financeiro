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
$id = filter_var($_POST['id'] ?? 0, FILTER_VALIDATE_INT);
$name = sanitizeInput($_POST['name'] ?? '');
$type = sanitizeInput($_POST['type'] ?? '');

// Validações
if (!$id) {
    jsonResponse(['success' => false, 'message' => 'ID inválido']);
}

if (empty($name)) {
    jsonResponse(['success' => false, 'message' => 'Nome é obrigatório']);
}

if (!in_array($type, ['income', 'expense'])) {
    jsonResponse(['success' => false, 'message' => 'Tipo inválido']);
}

try {
    // Verifica se a categoria existe e pertence ao usuário
    $stmt = $pdo->prepare("
        SELECT id FROM categories 
        WHERE id = ? AND (user_id = ? OR user_id IS NULL)
    ");
    $stmt->execute([$id, $_SESSION['user_id']]);
    
    if (!$stmt->fetch()) {
        jsonResponse(['success' => false, 'message' => 'Categoria não encontrada']);
    }
    
    // Verifica se já existe outra categoria com o mesmo nome
    $stmt = $pdo->prepare("
        SELECT id FROM categories 
        WHERE name = ? AND type = ? AND id != ? AND (user_id = ? OR user_id IS NULL)
    ");
    $stmt->execute([$name, $type, $id, $_SESSION['user_id']]);
    
    if ($stmt->fetch()) {
        jsonResponse(['success' => false, 'message' => 'Já existe uma categoria com este nome']);
    }
    
    // Atualiza a categoria
    $stmt = $pdo->prepare("
        UPDATE categories 
        SET name = ?, type = ?
        WHERE id = ? AND (user_id = ? OR user_id IS NULL)
    ");
    
    $stmt->execute([$name, $type, $id, $_SESSION['user_id']]);
    
    jsonResponse([
        'success' => true,
        'message' => 'Categoria atualizada com sucesso'
    ]);
    
} catch(PDOException $e) {
    error_log('Erro ao atualizar categoria: ' . $e->getMessage());
    jsonResponse(['success' => false, 'message' => 'Erro ao atualizar categoria'], 500);
}
