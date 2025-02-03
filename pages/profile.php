<?php
require_once '../includes/header.php';
requireLogin();

$user = getUser();
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (isset($_POST['update_email'])) {
            $newEmail = sanitizeInput($_POST['email']);
            
            // Validar e-mail
            if (!filter_var($newEmail, FILTER_VALIDATE_EMAIL)) {
                throw new Exception('E-mail inválido');
            }
            
            // Verificar se e-mail já existe
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
            $stmt->execute([$newEmail, $user['id']]);
            if ($stmt->fetch()) {
                throw new Exception('Este e-mail já está em uso');
            }
            
            // Atualizar e-mail
            $stmt = $pdo->prepare("UPDATE users SET email = ? WHERE id = ?");
            $stmt->execute([$newEmail, $user['id']]);
            
            $message = 'E-mail atualizado com sucesso!';
            $messageType = 'success';
            $user['email'] = $newEmail;
            
        } elseif (isset($_POST['update_password'])) {
            $currentPassword = $_POST['current_password'];
            $newPassword = $_POST['new_password'];
            $confirmPassword = $_POST['confirm_password'];
            
            // Validar senha atual
            if (!password_verify($currentPassword, $user['password'])) {
                throw new Exception('Senha atual incorreta');
            }
            
            // Validar nova senha
            if (strlen($newPassword) < 6) {
                throw new Exception('A nova senha deve ter pelo menos 6 caracteres');
            }
            
            if ($newPassword !== $confirmPassword) {
                throw new Exception('As senhas não conferem');
            }
            
            // Atualizar senha
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt->execute([$hashedPassword, $user['id']]);
            
            $message = 'Senha atualizada com sucesso!';
            $messageType = 'success';
        }
    } catch (Exception $e) {
        $message = $e->getMessage();
        $messageType = 'danger';
    }
}
?>

<div class="container py-4">
    <h2 class="mb-4">Meu Perfil</h2>
    
    <?php if ($message): ?>
    <div class="alert alert-<?= $messageType ?> alert-dismissible fade show" role="alert">
        <?= $message ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php endif; ?>

    <div class="row">
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Alterar E-mail</h5>
                </div>
                <div class="card-body">
                    <form method="post">
                        <div class="mb-3">
                            <label for="email" class="form-label">Novo E-mail</label>
                            <input type="email" class="form-control" id="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" required>
                        </div>
                        <button type="submit" name="update_email" class="btn btn-primary">Atualizar E-mail</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Alterar Senha</h5>
                </div>
                <div class="card-body">
                    <form method="post">
                        <div class="mb-3">
                            <label for="current_password" class="form-label">Senha Atual</label>
                            <input type="password" class="form-control" id="current_password" name="current_password" required>
                        </div>
                        <div class="mb-3">
                            <label for="new_password" class="form-label">Nova Senha</label>
                            <input type="password" class="form-control" id="new_password" name="new_password" required>
                        </div>
                        <div class="mb-3">
                            <label for="confirm_password" class="form-label">Confirmar Nova Senha</label>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                        </div>
                        <button type="submit" name="update_password" class="btn btn-primary">Atualizar Senha</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
