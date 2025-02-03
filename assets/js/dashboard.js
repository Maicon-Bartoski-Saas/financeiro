document.addEventListener('DOMContentLoaded', function() {
    // Inicializa os gráficos e carrega os dados
    initializeCharts();
    loadDashboardData();
});

// Função para carregar os dados do dashboard
function loadDashboardData() {
    fetch('pages/api/dashboard_data.php')
        .then(response => response.json())
        .then(data => {
            updateSummary(data.summary);
            updateRecentTransactions(data.transactions);
            updateCharts(data.chartData);
        })
        .catch(error => console.error('Erro ao carregar dados:', error));
}

// Atualiza o resumo financeiro
function updateSummary(summary) {
    document.getElementById('currentBalance').textContent = formatCurrency(summary.balance);
    document.getElementById('monthlyIncome').textContent = formatCurrency(summary.income);
    document.getElementById('monthlyExpenses').textContent = formatCurrency(summary.expenses);
}

// Inicializa os gráficos
function initializeCharts() {
    // Gráfico de Evolução Financeira
    const balanceCtx = document.getElementById('balanceChart').getContext('2d');
    window.balanceChart = new Chart(balanceCtx, {
        type: 'line',
        data: {
            labels: [],
            datasets: [{
                label: 'Saldo',
                data: [],
                borderColor: '#007bff',
                tension: 0.1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });

    // Gráfico de Despesas por Categoria
    const expensesCtx = document.getElementById('expensesChart').getContext('2d');
    window.expensesChart = new Chart(expensesCtx, {
        type: 'doughnut',
        data: {
            labels: [],
            datasets: [{
                data: [],
                backgroundColor: [
                    '#ff6384',
                    '#36a2eb',
                    '#ffce56',
                    '#4bc0c0',
                    '#9966ff'
                ]
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false
        }
    });
}

// Atualiza os gráficos com novos dados
function updateCharts(chartData) {
    // Atualiza gráfico de evolução
    window.balanceChart.data.labels = chartData.balance.labels;
    window.balanceChart.data.datasets[0].data = chartData.balance.data;
    window.balanceChart.update();

    // Atualiza gráfico de despesas
    window.expensesChart.data.labels = chartData.expenses.labels;
    window.expensesChart.data.datasets[0].data = chartData.expenses.data;
    window.expensesChart.update();
}

// Atualiza a tabela de transações recentes
function updateRecentTransactions(transactions) {
    const tbody = document.querySelector('#recentTransactions tbody');
    tbody.innerHTML = '';
    
    transactions.forEach(transaction => {
        const row = document.createElement('tr');
        row.innerHTML = `
            <td>${formatDate(transaction.date)}</td>
            <td>${transaction.description}</td>
            <td>${transaction.category}</td>
            <td class="${transaction.type === 'income' ? 'text-success' : 'text-danger'}">
                ${formatCurrency(transaction.amount)}
            </td>
        `;
        tbody.appendChild(row);
    });
}

// Funções auxiliares
function formatCurrency(value) {
    return new Intl.NumberFormat('pt-BR', {
        style: 'currency',
        currency: 'BRL'
    }).format(value);
}

function formatDate(dateString) {
    return new Date(dateString).toLocaleDateString('pt-BR');
}
