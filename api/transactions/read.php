<?php
require_once '../../includes/auth.php';

// Verifica autenticação
if (!isLoggedIn()) {
    jsonResponse(['success' => false, 'message' => 'Não autorizado'], 401);
}

// Pega os parâmetros de filtro
$month = filter_var($_GET['month'] ?? date('n'), FILTER_VALIDATE_INT);
$year = filter_var($_GET['year'] ?? date('Y'), FILTER_VALIDATE_INT);
$type = sanitizeInput($_GET['type'] ?? '');
$category_id = filter_var($_GET['category_id'] ?? 0, FILTER_VALIDATE_INT);
$start_date = sanitizeInput($_GET['start_date'] ?? '');
$end_date = sanitizeInput($_GET['end_date'] ?? '');

try {
    // Monta a query base
    $sql = "
        SELECT t.*, c.name as category_name 
        FROM transactions t
        LEFT JOIN categories c ON t.category_id = c.id
        WHERE t.user_id = ?
    ";
    $params = [$_SESSION['user_id']];
    
    // Adiciona filtros se fornecidos
    if ($month && $year) {
        $sql .= " AND MONTH(t.date) = ? AND YEAR(t.date) = ?";
        $params[] = $month;
        $params[] = $year;
    }
    
    if ($start_date && strtotime($start_date)) {
        $sql .= " AND t.date >= ?";
        $params[] = $start_date;
    }
    
    if ($end_date && strtotime($end_date)) {
        $sql .= " AND t.date <= ?";
        $params[] = $end_date;
    }
    
    if ($type && in_array($type, ['income', 'expense'])) {
        $sql .= " AND t.type = ?";
        $params[] = $type;
    }
    
    if ($category_id) {
        $sql .= " AND t.category_id = ?";
        $params[] = $category_id;
    }
    
    // Ordena por data e hora de criação
    $sql .= " ORDER BY t.date DESC, t.created_at DESC";
    
    // Executa a query
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $transactions = $stmt->fetchAll();
    
    // Calcula totais
    $totalIncome = array_reduce($transactions, function($carry, $item) {
        return $carry + ($item['type'] === 'income' ? $item['amount'] : 0);
    }, 0);
    
    $totalExpense = array_reduce($transactions, function($carry, $item) {
        return $carry + ($item['type'] === 'expense' ? $item['amount'] : 0);
    }, 0);
    
    // Retorna os dados
    jsonResponse([
        'success' => true,
        'transactions' => $transactions,
        'summary' => [
            'total_income' => $totalIncome,
            'total_expense' => $totalExpense,
            'balance' => $totalIncome - $totalExpense
        ]
    ]);
    
} catch(PDOException $e) {
    error_log('Erro ao buscar transações: ' . $e->getMessage());
    jsonResponse(['success' => false, 'message' => 'Erro ao buscar transações'], 500);
}
