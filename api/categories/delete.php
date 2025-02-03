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

// Pega o ID da categoria do corpo da requisição
$data = json_decode(file_get_contents('php://input'), true);
$id = filter_var($data['id'] ?? 0, FILTER_VALIDATE_INT);

if (!$id) {
    jsonResponse(['success' => false, 'message' => 'ID inválido']);
}

try {
    // Verifica se a categoria existe e pertence ao usuário
    $stmt = $pdo->prepare("
        SELECT id FROM categories 
        WHERE id = ? AND user_id = ?
    ");
    $stmt->execute([$id, $_SESSION['user_id']]);
    
    if (!$stmt->fetch()) {
        jsonResponse(['success' => false, 'message' => 'Categoria não encontrada ou não pode ser excluída']);
    }
    
    // Verifica se existem transações usando esta categoria
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as count 
        FROM transactions 
        WHERE category_id = ?
    ");
    $stmt->execute([$id]);
    $result = $stmt->fetch();
    
    if ($result['count'] > 0) {
        jsonResponse([
            'success' => false, 
            'message' => 'Esta categoria não pode ser excluída pois existem transações vinculadas a ela'
        ]);
    }
    
    // Exclui a categoria
    $stmt = $pdo->prepare("DELETE FROM categories WHERE id = ? AND user_id = ?");
    $stmt->execute([$id, $_SESSION['user_id']]);
    
    jsonResponse([
        'success' => true,
        'message' => 'Categoria excluída com sucesso'
    ]);
    
} catch(PDOException $e) {
    error_log('Erro ao excluir categoria: ' . $e->getMessage());
    jsonResponse(['success' => false, 'message' => 'Erro ao excluir categoria'], 500);
}
