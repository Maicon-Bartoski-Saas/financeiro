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

// Pega o ID da transação do corpo da requisição
$data = json_decode(file_get_contents('php://input'), true);
$id = filter_var($data['id'] ?? 0, FILTER_VALIDATE_INT);

if (!$id) {
    jsonResponse(['success' => false, 'message' => 'ID inválido']);
}

try {
    // Verifica se a transação existe e pertence ao usuário
    $stmt = $pdo->prepare("SELECT id FROM transactions WHERE id = ? AND user_id = ?");
    $stmt->execute([$id, $_SESSION['user_id']]);
    
    if (!$stmt->fetch()) {
        jsonResponse(['success' => false, 'message' => 'Transação não encontrada']);
    }
    
    // Exclui a transação
    $stmt = $pdo->prepare("DELETE FROM transactions WHERE id = ? AND user_id = ?");
    $stmt->execute([$id, $_SESSION['user_id']]);
    
    jsonResponse([
        'success' => true,
        'message' => 'Transação excluída com sucesso'
    ]);
    
} catch(PDOException $e) {
    error_log('Erro ao excluir transação: ' . $e->getMessage());
    jsonResponse(['success' => false, 'message' => 'Erro ao excluir transação'], 500);
}
