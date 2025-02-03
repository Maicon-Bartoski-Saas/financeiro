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
$type = sanitizeInput($_POST['type'] ?? '');
$description = sanitizeInput($_POST['description'] ?? '');
$amount = filter_var($_POST['amount'] ?? 0, FILTER_VALIDATE_FLOAT);
$date = sanitizeInput($_POST['date'] ?? '');
$category_id = filter_var($_POST['category_id'] ?? 0, FILTER_VALIDATE_INT);
$bank = sanitizeInput($_POST['bank'] ?? '');
$client_payee = sanitizeInput($_POST['client_payee'] ?? '');

// Validações
if (!$id) {
    jsonResponse(['success' => false, 'message' => 'ID inválido']);
}

if (!in_array($type, ['income', 'expense'])) {
    jsonResponse(['success' => false, 'message' => 'Tipo inválido']);
}

if (empty($description)) {
    jsonResponse(['success' => false, 'message' => 'Descrição é obrigatória']);
}

if ($amount <= 0) {
    jsonResponse(['success' => false, 'message' => 'Valor deve ser maior que zero']);
}

if (!strtotime($date)) {
    jsonResponse(['success' => false, 'message' => 'Data inválida']);
}

if (!$category_id) {
    jsonResponse(['success' => false, 'message' => 'Categoria é obrigatória']);
}

try {
    // Verifica se a transação existe e pertence ao usuário
    $stmt = $pdo->prepare("SELECT id FROM transactions WHERE id = ? AND user_id = ?");
    $stmt->execute([$id, $_SESSION['user_id']]);
    
    if (!$stmt->fetch()) {
        jsonResponse(['success' => false, 'message' => 'Transação não encontrada']);
    }
    
    // Verifica se a categoria existe e é do tipo correto
    $stmt = $pdo->prepare("
        SELECT type FROM categories 
        WHERE id = ? AND (user_id = ? OR user_id IS NULL)
    ");
    $stmt->execute([$category_id, $_SESSION['user_id']]);
    $category = $stmt->fetch();
    
    if (!$category) {
        jsonResponse(['success' => false, 'message' => 'Categoria não encontrada']);
    }
    
    if ($category['type'] !== $type) {
        jsonResponse(['success' => false, 'message' => 'Categoria incompatível com o tipo da transação']);
    }
    
    // Atualiza a transação
    $stmt = $pdo->prepare("
        UPDATE transactions 
        SET type = ?,
            description = ?,
            amount = ?,
            date = ?,
            category_id = ?,
            bank = ?,
            client_payee = ?,
            updated_at = NOW()
        WHERE id = ? AND user_id = ?
    ");
    
    $stmt->execute([
        $type,
        $description,
        $amount,
        $date,
        $category_id,
        $bank,
        $client_payee,
        $id,
        $_SESSION['user_id']
    ]);
    
    jsonResponse([
        'success' => true,
        'message' => 'Transação atualizada com sucesso'
    ]);
    
} catch(PDOException $e) {
    error_log('Erro ao atualizar transação: ' . $e->getMessage());
    jsonResponse(['success' => false, 'message' => 'Erro ao atualizar transação'], 500);
}
