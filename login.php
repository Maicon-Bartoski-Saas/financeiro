<?php
require_once 'includes/auth.php';

// Se já estiver logado, redireciona para o dashboard
if (isLoggedIn()) {
    redirect('/');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitizeInput($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($email) || empty($password)) {
        $error = 'Por favor, preencha todos os campos.';
    } else {
        if (loginUser($email, $password)) {
            redirect('/');
        } else {
            $error = 'Email ou senha incorretos.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | Financeiro</title>
    
    <!-- CSS -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="/assets/css/login.css" rel="stylesheet">
</head>
<body>
    <div class="login-container">
        <div class="login-logo">
            <i class="fas fa-wallet fa-3x"></i>
        </div>
        <h1 class="login-title">Financeiro</h1>
        
        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="form-group">
                <label for="email">E-mail</label>
                <input type="email" class="form-control" id="email" name="email" 
                       placeholder="Digite seu e-mail" required 
                       value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
            </div>
            
            <div class="form-group">
                <label for="password">Senha</label>
                <input type="password" class="form-control" id="password" name="password" 
                       placeholder="Digite sua senha" required>
            </div>
            
            <div class="remember-me">
                <input type="checkbox" id="remember" name="remember">
                <label for="remember">Lembrar-me</label>
            </div>
            
            <button type="submit" class="btn-login">
                Entrar
            </button>
        </form>
        
        <div class="forgot-password">
            <a href="#">Esqueceu sua senha?</a>
        </div>
    </div>

    <div class="developer-info">
        Desenvolvido por Maicon Bartoski
    </div>
</body>
</html>
