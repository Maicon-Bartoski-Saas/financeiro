// Funções Utilitárias
function formatMoney(value) {
    return new Intl.NumberFormat('pt-BR', {
        style: 'currency',
        currency: 'BRL'
    }).format(value);
}

function formatDate(date) {
    return new Intl.DateTimeFormat('pt-BR').format(new Date(date));
}

// Configuração do DataTables
$.extend($.fn.dataTable.defaults, {
    language: {
        url: '//cdn.datatables.net/plug-ins/1.11.5/i18n/pt-BR.json'
    },
    pageLength: 10,
    responsive: true
});

// Inicialização de Tooltips
var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
    return new bootstrap.Tooltip(tooltipTriggerEl);
});

// Máscara para campos monetários
document.querySelectorAll('input[data-type="money"]').forEach(input => {
    input.addEventListener('input', function(e) {
        let value = e.target.value.replace(/\D/g, '');
        value = (value / 100).toFixed(2);
        e.target.value = value;
    });
});

// Confirmação de exclusão
document.querySelectorAll('[data-confirm]').forEach(element => {
    element.addEventListener('click', function(e) {
        if (!confirm(this.dataset.confirm)) {
            e.preventDefault();
        }
    });
});

// Atualização automática de campos select dependentes
document.querySelectorAll('select[data-dependent]').forEach(select => {
    select.addEventListener('change', function() {
        const target = document.querySelector(this.dataset.dependent);
        if (target) {
            fetch(`/api/${target.dataset.url}?${this.dataset.param}=${this.value}`)
                .then(response => response.json())
                .then(data => {
                    target.innerHTML = '<option value="">Selecione...</option>';
                    data.forEach(item => {
                        target.innerHTML += `<option value="${item.id}">${item.name}</option>`;
                    });
                });
        }
    });
});

// Handler para formulários AJAX
document.querySelectorAll('form[data-ajax]').forEach(form => {
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const submitButton = form.querySelector('[type="submit"]');
        const originalText = submitButton.innerHTML;
        submitButton.disabled = true;
        submitButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processando...';
        
        fetch(form.action, {
            method: form.method,
            body: new FormData(form)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                if (data.redirect) {
                    window.location.href = data.redirect;
                } else if (data.reload) {
                    window.location.reload();
                }
            } else {
                alert(data.message || 'Ocorreu um erro ao processar sua solicitação.');
            }
        })
        .catch(error => {
            alert('Ocorreu um erro ao processar sua solicitação.');
        })
        .finally(() => {
            submitButton.disabled = false;
            submitButton.innerHTML = originalText;
        });
    });
});

// Atualização de gráficos em tempo real
function updateCharts() {
    document.querySelectorAll('[data-chart]').forEach(canvas => {
        const chart = Chart.getChart(canvas);
        if (chart) {
            fetch(canvas.dataset.url)
                .then(response => response.json())
                .then(data => {
                    chart.data = data;
                    chart.update();
                });
        }
    });
}

// Atualizar gráficos a cada 5 minutos
setInterval(updateCharts, 300000);

// Notificações Toast
function showToast(message, type = 'success') {
    const toast = document.createElement('div');
    toast.className = `toast align-items-center text-white bg-${type} border-0`;
    toast.setAttribute('role', 'alert');
    toast.setAttribute('aria-live', 'assertive');
    toast.setAttribute('aria-atomic', 'true');
    
    toast.innerHTML = `
        <div class="d-flex">
            <div class="toast-body">${message}</div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
        </div>
    `;
    
    document.querySelector('.toast-container').appendChild(toast);
    const bsToast = new bootstrap.Toast(toast);
    bsToast.show();
    
    toast.addEventListener('hidden.bs.toast', () => toast.remove());
}

// Exportação de dados
document.querySelectorAll('[data-export]').forEach(button => {
    button.addEventListener('click', function() {
        const format = this.dataset.export;
        const url = this.dataset.url;
        
        fetch(`${url}?format=${format}`)
            .then(response => response.blob())
            .then(blob => {
                const url = window.URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = `export.${format}`;
                document.body.appendChild(a);
                a.click();
                window.URL.revokeObjectURL(url);
                a.remove();
            });
    });
});
