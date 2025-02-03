<?php
require_once '../includes/header.php';

// Buscar todas as categorias do usuário
try {
    $stmt = $pdo->prepare("
        SELECT * FROM categories 
        WHERE user_id = ? OR user_id IS NULL 
        ORDER BY type, name
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $categories = $stmt->fetchAll();
} catch(PDOException $e) {
    error_log('Erro ao buscar categorias: ' . $e->getMessage());
    $_SESSION['flash_message'] = 'Erro ao carregar categorias';
    $_SESSION['flash_type'] = 'danger';
}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Gerenciar Categorias</h2>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#categoryModal">
        <i class="fas fa-plus me-2"></i>Nova Categoria
    </button>
</div>

<div class="row">
    <!-- Categorias de Receita -->
    <div class="col-md-6 mb-4">
        <div class="card">
            <div class="card-header bg-success text-white">
                <h5 class="card-title mb-0">
                    <i class="fas fa-arrow-up me-2"></i>Categorias de Receita
                </h5>
            </div>
            <div class="card-body">
                <?php
                $incomeCategories = array_filter($categories, function($cat) {
                    return $cat['type'] === 'income';
                });
                
                if (empty($incomeCategories)): ?>
                    <p class="text-muted">Nenhuma categoria de receita encontrada.</p>
                <?php else: ?>
                    <div class="list-group">
                        <?php foreach ($incomeCategories as $category): ?>
                            <div class="list-group-item d-flex justify-content-between align-items-center">
                                <span><?php echo htmlspecialchars($category['name']); ?></span>
                                <div class="btn-group">
                                    <button class="btn btn-sm btn-outline-primary edit-category" 
                                            data-id="<?php echo $category['id']; ?>"
                                            data-name="<?php echo htmlspecialchars($category['name']); ?>"
                                            data-type="<?php echo $category['type']; ?>">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="btn btn-sm btn-outline-danger delete-category"
                                            data-id="<?php echo $category['id']; ?>"
                                            data-name="<?php echo htmlspecialchars($category['name']); ?>">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Categorias de Despesa -->
    <div class="col-md-6 mb-4">
        <div class="card">
            <div class="card-header bg-danger text-white">
                <h5 class="card-title mb-0">
                    <i class="fas fa-arrow-down me-2"></i>Categorias de Despesa
                </h5>
            </div>
            <div class="card-body">
                <?php
                $expenseCategories = array_filter($categories, function($cat) {
                    return $cat['type'] === 'expense';
                });
                
                if (empty($expenseCategories)): ?>
                    <p class="text-muted">Nenhuma categoria de despesa encontrada.</p>
                <?php else: ?>
                    <div class="list-group">
                        <?php foreach ($expenseCategories as $category): ?>
                            <div class="list-group-item d-flex justify-content-between align-items-center">
                                <span><?php echo htmlspecialchars($category['name']); ?></span>
                                <div class="btn-group">
                                    <button class="btn btn-sm btn-outline-primary edit-category" 
                                            data-id="<?php echo $category['id']; ?>"
                                            data-name="<?php echo htmlspecialchars($category['name']); ?>"
                                            data-type="<?php echo $category['type']; ?>">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="btn btn-sm btn-outline-danger delete-category"
                                            data-id="<?php echo $category['id']; ?>"
                                            data-name="<?php echo htmlspecialchars($category['name']); ?>">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Modal de Categoria -->
<div class="modal fade" id="categoryModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Nova Categoria</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="categoryForm">
                    <input type="hidden" name="id" id="categoryId">
                    
                    <div class="mb-3">
                        <label class="form-label">Nome</label>
                        <input type="text" class="form-control" name="name" id="categoryName" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Tipo</label>
                        <div class="btn-group w-100" role="group">
                            <input type="radio" class="btn-check" name="type" id="typeIncome" value="income" checked>
                            <label class="btn btn-outline-success" for="typeIncome">Receita</label>
                            
                            <input type="radio" class="btn-check" name="type" id="typeExpense" value="expense">
                            <label class="btn btn-outline-danger" for="typeExpense">Despesa</label>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" id="saveCategory">Salvar</button>
            </div>
        </div>
    </div>
</div>

<script>
// Salvar categoria
document.getElementById('saveCategory').addEventListener('click', function() {
    const form = document.getElementById('categoryForm');
    const formData = new FormData(form);
    const id = formData.get('id');
    const url = id ? '/api/categories/update.php' : '/api/categories/create.php';
    
    fetch(url, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert(data.message || 'Erro ao salvar categoria');
        }
    });
});

// Editar categoria
document.querySelectorAll('.edit-category').forEach(button => {
    button.addEventListener('click', function() {
        const id = this.dataset.id;
        const name = this.dataset.name;
        const type = this.dataset.type;
        
        document.getElementById('categoryId').value = id;
        document.getElementById('categoryName').value = name;
        document.querySelector(`input[name="type"][value="${type}"]`).checked = true;
        
        const modal = new bootstrap.Modal(document.getElementById('categoryModal'));
        modal.show();
    });
});

// Excluir categoria
document.querySelectorAll('.delete-category').forEach(button => {
    button.addEventListener('click', function() {
        const id = this.dataset.id;
        const name = this.dataset.name;
        
        if (confirm(`Deseja realmente excluir a categoria "${name}"?`)) {
            fetch('/api/categories/delete.php', {
                method: 'POST',
                body: JSON.stringify({ id: id })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert(data.message || 'Erro ao excluir categoria');
                }
            });
        }
    });
});
</script>

<?php require_once '../includes/footer.php'; ?>
