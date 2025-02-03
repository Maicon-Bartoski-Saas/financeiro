<?php
require_once '../includes/header.php';
require_once '../includes/functions.php';

// Verifica se o usuário está logado
if (!isLoggedIn()) {
    redirect('/login.php');
}

// Buscar todas as categorias do usuário
try {
    $stmt = $pdo->prepare("
        SELECT * FROM categories 
        WHERE user_id = ? OR user_id IS NULL 
        ORDER BY type, name
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $categories = $stmt->fetchAll();
    
    // Separar categorias por tipo
    $incomeCategories = array_filter($categories, fn($cat) => $cat['type'] === 'income');
    $expenseCategories = array_filter($categories, fn($cat) => $cat['type'] === 'expense');
} catch(PDOException $e) {
    logError('Erro ao buscar categorias: ' . $e->getMessage());
    $categories = [];
    $incomeCategories = [];
    $expenseCategories = [];
}

// Filtros
$month = isset($_GET['month']) ? (int)$_GET['month'] : date('n');
$year = isset($_GET['year']) ? (int)$_GET['year'] : date('Y');
$type = isset($_GET['type']) ? sanitizeInput($_GET['type']) : '';
$category = isset($_GET['category']) ? (int)$_GET['category'] : 0;

// Buscar transações
try {
    $sql = "
        SELECT t.*, c.name as category_name, c.type as category_type 
        FROM transactions t
        LEFT JOIN categories c ON t.category_id = c.id
        WHERE t.user_id = ? 
        AND MONTH(t.date) = ? 
        AND YEAR(t.date) = ?
    ";
    $params = [$_SESSION['user_id'], $month, $year];
    
    if ($type && in_array($type, ['income', 'expense'])) {
        $sql .= " AND t.type = ?";
        $params[] = $type;
    }
    
    if ($category) {
        $sql .= " AND t.category_id = ?";
        $params[] = $category;
    }
    
    $sql .= " ORDER BY t.date DESC, t.created_at DESC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $transactions = $stmt->fetchAll();
    
    // Calcular totais
    $totalIncome = array_reduce($transactions, function($carry, $item) {
        return $carry + ($item['type'] === 'income' ? $item['amount'] : 0);
    }, 0);
    
    $totalExpense = array_reduce($transactions, function($carry, $item) {
        return $carry + ($item['type'] === 'expense' ? $item['amount'] : 0);
    }, 0);
    
    $balance = $totalIncome - $totalExpense;
    
} catch(PDOException $e) {
    logError('Erro ao buscar transações: ' . $e->getMessage());
    $transactions = [];
    $totalIncome = $totalExpense = $balance = 0;
}

function getMonthName($month) {
    $months = [
        1 => 'Janeiro',
        2 => 'Fevereiro',
        3 => 'Março',
        4 => 'Abril',
        5 => 'Maio',
        6 => 'Junho',
        7 => 'Julho',
        8 => 'Agosto',
        9 => 'Setembro',
        10 => 'Outubro',
        11 => 'Novembro',
        12 => 'Dezembro'
    ];
    return $months[$month] ?? '';
}

function formatMoney($value) {
    return 'R$ ' . number_format($value, 2, ',', '.');
}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Transações</h2>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#transactionModal">
        <i class="fas fa-plus me-2"></i>Nova Transação
    </button>
</div>

<!-- Filtros -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-2">
                <label class="form-label">Mês</label>
                <select name="month" class="form-select">
                    <?php for($i = 1; $i <= 12; $i++): ?>
                        <option value="<?php echo $i; ?>" <?php echo $i === $month ? 'selected' : ''; ?>>
                            <?php echo getMonthName($i); ?>
                        </option>
                    <?php endfor; ?>
                </select>
            </div>
            
            <div class="col-md-2">
                <label class="form-label">Ano</label>
                <select name="year" class="form-select">
                    <?php 
                    $currentYear = date('Y');
                    for($i = $currentYear - 2; $i <= $currentYear + 1; $i++): 
                    ?>
                        <option value="<?php echo $i; ?>" <?php echo $i === $year ? 'selected' : ''; ?>>
                            <?php echo $i; ?>
                        </option>
                    <?php endfor; ?>
                </select>
            </div>
            
            <div class="col-md-3">
                <label class="form-label">Tipo</label>
                <select name="type" class="form-select">
                    <option value="">Todos</option>
                    <option value="income" <?php echo $type === 'income' ? 'selected' : ''; ?>>Receitas</option>
                    <option value="expense" <?php echo $type === 'expense' ? 'selected' : ''; ?>>Despesas</option>
                </select>
            </div>
            
            <div class="col-md-3">
                <label class="form-label">Categoria</label>
                <select name="category" class="form-select">
                    <option value="">Todas</option>
                    <optgroup label="Receitas">
                        <?php foreach($incomeCategories as $cat): ?>
                            <option value="<?php echo $cat['id']; ?>" <?php echo $category === (int)$cat['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($cat['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </optgroup>
                    <optgroup label="Despesas">
                        <?php foreach($expenseCategories as $cat): ?>
                            <option value="<?php echo $cat['id']; ?>" <?php echo $category === (int)$cat['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($cat['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </optgroup>
                </select>
            </div>
            
            <div class="col-md-2 d-flex align-items-end">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="fas fa-filter me-2"></i>Filtrar
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Resumo -->
<div class="row mb-4">
    <div class="col-md-4">
        <div class="card bg-success text-white">
            <div class="card-body">
                <h6 class="card-subtitle mb-2">Total de Receitas</h6>
                <h4 class="card-title mb-0"><?php echo formatMoney($totalIncome); ?></h4>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card bg-danger text-white">
            <div class="card-body">
                <h6 class="card-subtitle mb-2">Total de Despesas</h6>
                <h4 class="card-title mb-0"><?php echo formatMoney($totalExpense); ?></h4>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card <?php echo $balance >= 0 ? 'bg-primary' : 'bg-warning text-dark'; ?>">
            <div class="card-body">
                <h6 class="card-subtitle mb-2">Saldo</h6>
                <h4 class="card-title mb-0"><?php echo formatMoney($balance); ?></h4>
            </div>
        </div>
    </div>
</div>

<!-- Lista de Transações -->
<div class="card">
    <div class="card-body">
        <?php if (empty($transactions)): ?>
            <p class="text-muted text-center py-4">Nenhuma transação encontrada para o período.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Data</th>
                            <th>Tipo</th>
                            <th>Descrição</th>
                            <th>Categoria</th>
                            <th>Valor</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($transactions as $transaction): ?>
                            <tr>
                                <td><?php echo date('d/m/Y', strtotime($transaction['date'])); ?></td>
                                <td>
                                    <span class="badge bg-<?php echo $transaction['type'] === 'income' ? 'success' : 'danger'; ?>">
                                        <?php echo $transaction['type'] === 'income' ? 'Receita' : 'Despesa'; ?>
                                    </span>
                                </td>
                                <td><?php echo htmlspecialchars($transaction['description']); ?></td>
                                <td><?php echo htmlspecialchars($transaction['category_name'] ?? 'Sem categoria'); ?></td>
                                <td class="text-<?php echo $transaction['type'] === 'income' ? 'success' : 'danger'; ?>">
                                    <?php echo formatMoney($transaction['amount']); ?>
                                </td>
                                <td>
                                    <div class="btn-group">
                                        <button class="btn btn-sm btn-outline-primary edit-transaction" 
                                                data-id="<?php echo $transaction['id']; ?>"
                                                data-type="<?php echo $transaction['type']; ?>"
                                                data-description="<?php echo htmlspecialchars($transaction['description']); ?>"
                                                data-amount="<?php echo $transaction['amount']; ?>"
                                                data-date="<?php echo $transaction['date']; ?>"
                                                data-category="<?php echo $transaction['category_id']; ?>"
                                                data-bank="<?php echo htmlspecialchars($transaction['bank'] ?? ''); ?>"
                                                data-client-payee="<?php echo htmlspecialchars($transaction['client_payee'] ?? ''); ?>">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="btn btn-sm btn-outline-danger delete-transaction"
                                                data-id="<?php echo $transaction['id']; ?>"
                                                data-description="<?php echo htmlspecialchars($transaction['description']); ?>">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Modal de Transação -->
<div class="modal fade" id="transactionModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Nova Transação</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="transactionForm">
                    <input type="hidden" name="id" id="transactionId">
                    
                    <div class="mb-3">
                        <label class="form-label">Tipo</label>
                        <div class="btn-group w-100" role="group">
                            <input type="radio" class="btn-check" name="type" id="typeIncome" value="income" checked>
                            <label class="btn btn-outline-success" for="typeIncome">Receita</label>
                            
                            <input type="radio" class="btn-check" name="type" id="typeExpense" value="expense">
                            <label class="btn btn-outline-danger" for="typeExpense">Despesa</label>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Descrição</label>
                        <input type="text" class="form-control" name="description" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Valor</label>
                        <div class="input-group">
                            <span class="input-group-text">R$</span>
                            <input type="number" class="form-control" name="amount" step="0.01" min="0.01" required>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Data</label>
                        <input type="date" class="form-control" name="date" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Categoria</label>
                        <select class="form-select" name="category_id" required>
                            <option value="">Selecione...</option>
                            <optgroup label="Receitas" id="incomeCategoriesGroup">
                                <?php foreach($incomeCategories as $category): ?>
                                    <option value="<?php echo $category['id']; ?>" data-type="income">
                                        <?php echo htmlspecialchars($category['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </optgroup>
                            <optgroup label="Despesas" id="expenseCategoriesGroup">
                                <?php foreach($expenseCategories as $category): ?>
                                    <option value="<?php echo $category['id']; ?>" data-type="expense">
                                        <?php echo htmlspecialchars($category['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </optgroup>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Banco/Conta</label>
                        <input type="text" class="form-control" name="bank">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Cliente/Fornecedor</label>
                        <input type="text" class="form-control" name="client_payee">
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" id="saveTransaction">Salvar</button>
            </div>
        </div>
    </div>
</div>

<script>
// Filtrar categorias baseado no tipo selecionado
document.querySelectorAll('input[name="type"]').forEach(radio => {
    radio.addEventListener('change', function() {
        const type = this.value;
        document.querySelectorAll('#transactionForm select[name="category_id"] option').forEach(option => {
            if (option.value === '') return; // Ignora a opção "Selecione..."
            option.style.display = option.dataset.type === type ? '' : 'none';
        });
        document.querySelector('#transactionForm select[name="category_id"]').value = '';
    });
});

// Salvar transação
document.getElementById('saveTransaction').addEventListener('click', function() {
    const form = document.getElementById('transactionForm');
    const formData = new FormData(form);
    const id = formData.get('id');
    const url = id ? '/api/transactions/update.php' : '/api/transactions/create.php';
    
    fetch(url, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert(data.message || 'Erro ao salvar transação');
        }
    })
    .catch(error => {
        console.error('Erro:', error);
        alert('Erro ao salvar transação');
    });
});

// Editar transação
document.querySelectorAll('.edit-transaction').forEach(button => {
    button.addEventListener('click', function() {
        const data = this.dataset;
        const form = document.getElementById('transactionForm');
        
        // Atualiza o título do modal
        document.querySelector('#transactionModal .modal-title').textContent = 'Editar Transação';
        
        // Preenche o formulário
        form.querySelector('input[name="id"]').value = data.id;
        form.querySelector(`input[name="type"][value="${data.type}"]`).click();
        form.querySelector('input[name="description"]').value = data.description;
        form.querySelector('input[name="amount"]').value = data.amount;
        form.querySelector('input[name="date"]').value = data.date;
        form.querySelector('select[name="category_id"]').value = data.category;
        form.querySelector('input[name="bank"]').value = data.bank;
        form.querySelector('input[name="client_payee"]').value = data.clientPayee;
        
        // Abre o modal
        const modal = new bootstrap.Modal(document.getElementById('transactionModal'));
        modal.show();
    });
});

// Excluir transação
document.querySelectorAll('.delete-transaction').forEach(button => {
    button.addEventListener('click', function() {
        const id = this.dataset.id;
        const description = this.dataset.description;
        
        if (confirm(`Deseja realmente excluir a transação "${description}"?`)) {
            fetch('/api/transactions/delete.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ id: id })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert(data.message || 'Erro ao excluir transação');
                }
            })
            .catch(error => {
                console.error('Erro:', error);
                alert('Erro ao excluir transação');
            });
        }
    });
});

// Reseta o formulário e título ao abrir o modal para nova transação
document.querySelector('[data-bs-target="#transactionModal"]').addEventListener('click', function() {
    document.querySelector('#transactionModal .modal-title').textContent = 'Nova Transação';
    document.getElementById('transactionForm').reset();
    document.getElementById('transactionId').value = '';
    document.querySelector('input[name="date"]').value = new Date().toISOString().split('T')[0];
});
</script>

<?php require_once '../includes/footer.php'; ?>
