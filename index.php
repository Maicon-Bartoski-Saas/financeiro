<?php
require_once 'includes/header.php';
requireLogin();

// Obtém o mês e ano atual
$currentDate = getCurrentMonthYear();
$month = isset($_GET['month']) ? (int)$_GET['month'] : $currentDate['month'];
$year = isset($_GET['year']) ? (int)$_GET['year'] : $currentDate['year'];

try {
    // Buscar categorias do usuário
    $stmt = $pdo->prepare("SELECT * FROM categories WHERE user_id = ? OR user_id IS NULL ORDER BY type, name");
    $stmt->execute([$_SESSION['user_id']]);
    $categories = $stmt->fetchAll();
    
    $incomeCategories = array_filter($categories, fn($cat) => $cat['type'] === 'income');
    $expenseCategories = array_filter($categories, fn($cat) => $cat['type'] === 'expense');

    // Buscar totais do mês
    $stmt = $pdo->prepare("
        SELECT type, SUM(amount) as total 
        FROM transactions 
        WHERE user_id = ? 
        AND MONTH(date) = ? 
        AND YEAR(date) = ?
        GROUP BY type
    ");
    $stmt->execute([$_SESSION['user_id'], $month, $year]);
    $totals = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    
    $totalIncome = $totals['income'] ?? 0;
    $totalExpense = $totals['expense'] ?? 0;
    $balance = $totalIncome - $totalExpense;

    // Buscar dados do gráfico
    $stmt = $pdo->prepare("
        SELECT DATE(date) as date, type, SUM(amount) as total 
        FROM transactions 
        WHERE user_id = ? 
        AND MONTH(date) = ? 
        AND YEAR(date) = ?
        GROUP BY DATE(date), type
        ORDER BY date
    ");
    $stmt->execute([$_SESSION['user_id'], $month, $year]);
    $chartData = $stmt->fetchAll();

    // Preparar dados para o gráfico
    $dates = [];
    $incomeData = [];
    $expenseData = [];
    
    // Inicializar arrays
    $currentDate = new DateTime("$year-$month-01");
    $lastDay = new DateTime("$year-$month-" . date('t', strtotime("$year-$month-01")));
    
    while ($currentDate <= $lastDay) {
        $dateStr = $currentDate->format('Y-m-d');
        $dates[] = $currentDate->format('d/m');
        $incomeData[$dateStr] = 0;
        $expenseData[$dateStr] = 0;
        $currentDate->modify('+1 day');
    }

    // Preencher com dados reais
    foreach ($chartData as $data) {
        if ($data['type'] === 'income') {
            $incomeData[$data['date']] = floatval($data['total']);
        } else {
            $expenseData[$data['date']] = floatval($data['total']);
        }
    }

    // Converter para arrays finais
    $incomeValues = array_values($incomeData);
    $expenseValues = array_values($expenseData);

    // Buscar últimas transações
    $stmt = $pdo->prepare("
        SELECT t.*, c.name as category_name 
        FROM transactions t
        LEFT JOIN categories c ON t.category_id = c.id
        WHERE t.user_id = ?
        ORDER BY t.date DESC, t.created_at DESC
        LIMIT 5
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $recentTransactions = $stmt->fetchAll();

} catch(PDOException $e) {
    error_log('Erro ao buscar dados do dashboard: ' . $e->getMessage());
    $dates = $incomeValues = $expenseValues = [];
    $totalIncome = $totalExpense = $balance = 0;
    $recentTransactions = [];
}
?>

<!-- Link para o CSS do Dashboard -->
<link href="/assets/css/dashboard.css" rel="stylesheet">

<div class="dashboard-container">
    <div class="container">
        <!-- Header do Dashboard -->
        <div class="dashboard-header d-flex justify-content-between align-items-center">
            <h2><i class="fas fa-chart-line me-2"></i>Dashboard</h2>
            <div class="date-selector">
                <form action="" method="GET" class="d-flex gap-2">
                    <select name="month" class="form-select">
                        <?php
                        for ($i = 1; $i <= 12; $i++) {
                            $selected = $i == $month ? 'selected' : '';
                            echo "<option value=\"$i\" $selected>" . strftime('%B', mktime(0, 0, 0, $i, 1)) . "</option>";
                        }
                        ?>
                    </select>
                    <select name="year" class="form-select">
                        <?php
                        $currentYear = date('Y');
                        for ($i = $currentYear - 2; $i <= $currentYear; $i++) {
                            $selected = $i == $year ? 'selected' : '';
                            echo "<option value=\"$i\" $selected>$i</option>";
                        }
                        ?>
                    </select>
                    <button type="submit" class="btn btn-outline-light">
                        <i class="fas fa-sync-alt"></i>
                    </button>
                </form>
            </div>
        </div>

        <!-- Cards de Resumo -->
        <div class="row g-4">
            <div class="col-md-4">
                <div class="summary-card income">
                    <div class="card-icon">
                        <i class="fas fa-arrow-up"></i>
                    </div>
                    <h3>Entradas</h3>
                    <div class="amount">R$ <?php echo number_format($totalIncome, 2, ',', '.'); ?></div>
                    <small class="text-muted">Total de receitas no mês</small>
                </div>
            </div>
            <div class="col-md-4">
                <div class="summary-card expense">
                    <div class="card-icon">
                        <i class="fas fa-arrow-down"></i>
                    </div>
                    <h3>Saídas</h3>
                    <div class="amount">R$ <?php echo number_format($totalExpense, 2, ',', '.'); ?></div>
                    <small class="text-muted">Total de despesas no mês</small>
                </div>
            </div>
            <div class="col-md-4">
                <div class="summary-card balance">
                    <div class="card-icon">
                        <i class="fas fa-wallet"></i>
                    </div>
                    <h3>Saldo</h3>
                    <div class="amount">R$ <?php echo number_format($balance, 2, ',', '.'); ?></div>
                    <small class="text-muted">Saldo atual do mês</small>
                </div>
            </div>
        </div>

        <!-- Gráfico -->
        <div class="chart-container">
            <div class="chart-header">
                <h3><i class="fas fa-chart-area me-2"></i>Fluxo Financeiro</h3>
            </div>
            <canvas id="financialChart"></canvas>
        </div>

        <!-- Últimas Transações -->
        <div class="transactions-container">
            <div class="transactions-header">
                <h3><i class="fas fa-history me-2"></i>Últimas Transações</h3>
                <a href="/pages/transactions.php" class="btn btn-sm btn-outline-light">
                    Ver todas <i class="fas fa-arrow-right ms-1"></i>
                </a>
            </div>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Data</th>
                            <th>Descrição</th>
                            <th>Categoria</th>
                            <th>Valor</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($recentTransactions)): ?>
                            <tr>
                                <td colspan="4" class="text-center text-muted">
                                    <i class="fas fa-info-circle me-1"></i>Nenhuma transação encontrada
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($recentTransactions as $transaction): ?>
                                <tr>
                                    <td><?php echo date('d/m/Y', strtotime($transaction['date'])); ?></td>
                                    <td><?php echo htmlspecialchars($transaction['description']); ?></td>
                                    <td>
                                        <span class="badge bg-<?php echo $transaction['type'] === 'income' ? 'info' : 'danger' ?>">
                                            <?php echo htmlspecialchars($transaction['category_name']); ?>
                                        </span>
                                    </td>
                                    <td class="transaction-amount <?php echo $transaction['type']; ?>">
                                        R$ <?php echo number_format($transaction['amount'], 2, ',', '.'); ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Configuração do gráfico
const ctx = document.getElementById('financialChart').getContext('2d');
new Chart(ctx, {
    type: 'line',
    data: {
        labels: <?php echo json_encode($dates); ?>,
        datasets: [{
            label: 'Entradas',
            data: <?php echo json_encode($incomeValues); ?>,
            borderColor: '#00F0FF',
            backgroundColor: 'rgba(0, 240, 255, 0.1)',
            tension: 0.4,
            fill: true
        }, {
            label: 'Saídas',
            data: <?php echo json_encode($expenseValues); ?>,
            borderColor: '#FF007A',
            backgroundColor: 'rgba(255, 0, 122, 0.1)',
            tension: 0.4,
            fill: true
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: true,
        plugins: {
            legend: {
                position: 'top',
                labels: {
                    color: '#ffffff',
                    font: {
                        family: 'Inter'
                    },
                    padding: 20
                }
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                grid: {
                    color: 'rgba(255, 255, 255, 0.1)'
                },
                ticks: {
                    color: '#ffffff',
                    font: {
                        family: 'Inter'
                    },
                    callback: function(value) {
                        return 'R$ ' + value.toLocaleString('pt-BR');
                    },
                    padding: 10
                }
            },
            x: {
                grid: {
                    color: 'rgba(255, 255, 255, 0.1)'
                },
                ticks: {
                    color: '#ffffff',
                    font: {
                        family: 'Inter'
                    },
                    padding: 10
                }
            }
        },
        interaction: {
            intersect: false,
            mode: 'index'
        },
        hover: {
            mode: 'index',
            intersect: false
        },
        layout: {
            padding: {
                top: 20,
                right: 20,
                bottom: 20,
                left: 20
            }
        }
    }
});
</script>

<?php require_once 'includes/footer.php'; ?>

<?php require_once 'includes/footer.php'; ?>
