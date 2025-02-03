<?php
require_once '../../includes/auth.php';

// Verifica autenticação
if (!isLoggedIn()) {
    jsonResponse(['success' => false, 'message' => 'Não autorizado'], 401);
}

// Pega o tipo de categoria (opcional)
$type = $_GET['type'] ?? null;

try {
    // Prepara a query base
    $sql = "SELECT * FROM categories WHERE (user_id = ? OR user_id IS NULL)";
    $params = [$_SESSION['user_id']];
    
    // Adiciona filtro por tipo se especificado
    if ($type && in_array($type, ['income', 'expense'])) {
        $sql .= " AND type = ?";
        $params[] = $type;
    }
    
    $sql .= " ORDER BY type, name";
    
    // Executa a query
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $categories = $stmt->fetchAll();
    
    // Retorna as categorias
    header('Content-Type: application/json');
    echo json_encode($categories);
    
} catch(PDOException $e) {
    error_log('Erro ao buscar categorias: ' . $e->getMessage());
    jsonResponse(['success' => false, 'message' => 'Erro ao buscar categorias'], 500);
}
