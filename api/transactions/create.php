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
$type = sanitizeInput($_POST['type'] ?? '');
$description = sanitizeInput($_POST['description'] ?? '');
$amount = filter_var($_POST['amount'] ?? 0, FILTER_VALIDATE_FLOAT);
$date = sanitizeInput($_POST['date'] ?? '');
$category_id = filter_var($_POST['category_id'] ?? 0, FILTER_VALIDATE_INT);
$bank = sanitizeInput($_POST['bank'] ?? '');
$client_payee = sanitizeInput($_POST['client_payee'] ?? '');

// Validações
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
    
    // Insere a transação
    $stmt = $pdo->prepare("
        INSERT INTO transactions (
            user_id, type, description, amount, date, 
            category_id, bank, client_payee, created_at
        ) VALUES (
            ?, ?, ?, ?, ?, 
            ?, ?, ?, NOW()
        )
    ");
    
    $stmt->execute([
        $_SESSION['user_id'],
        $type,
        $description,
        $amount,
        $date,
        $category_id,
        $bank,
        $client_payee
    ]);
    
    jsonResponse([
        'success' => true,
        'message' => 'Transação criada com sucesso',
        'transaction_id' => $pdo->lastInsertId()
    ]);
    
} catch(PDOException $e) {
    error_log('Erro ao criar transação: ' . $e->getMessage());
    jsonResponse(['success' => false, 'message' => 'Erro ao criar transação'], 500);
}
