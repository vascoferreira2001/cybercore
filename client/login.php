<?php
// Login Page
$page_title = 'Login - Área de Cliente | CyberCore';
$page_description = 'Aceda à sua área de cliente CyberCore';
$extra_css = ['/assets/css/auth.css'];

require_once __DIR__ . '/../inc/header.php';

// Handle login form submission
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_validate($_POST['csrf_token'] ?? null)) {
        $error = 'Token de segurança inválido. Por favor, tente novamente.';
    } else {
        $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
        $password = $_POST['password'] ?? '';

        if (!$email) {
            $error = 'Email inválido.';
        } elseif (empty($password)) {
            $error = 'Por favor, insira a sua password.';
        } else {
            // TODO: Validate against database
            // For now, just show error
            $error = 'Email ou password incorretos.';
        }
    }
}
?>

<div class="auth-container">
    <div class="auth-box">
        <div class="auth-header">
            <h1>Bem-vindo de Volta</h1>
            <p>Aceda à sua área de cliente</p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-error">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success">
                <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="" class="auth-form">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token()); ?>">
            
            <div class="form-group">
                <label for="email">Email</label>
                <input 
                    type="email" 
                    id="email" 
                    name="email" 
                    value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                    required 
                    autocomplete="email"
                    placeholder="seu@email.com"
                >
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input 
                    type="password" 
                    id="password" 
                    name="password" 
                    required 
                    autocomplete="current-password"
                    placeholder="••••••••"
                >
            </div>

            <div class="form-options">
                <label class="checkbox">
                    <input type="checkbox" name="remember">
                    <span>Lembrar-me</span>
                </label>
                <a href="/client/forgot-password.php" class="link-small">Esqueceu a password?</a>
            </div>

            <button type="submit" class="btn btn-primary btn-block">Entrar</button>
        </form>

        <div class="auth-footer">
            <p>Ainda não tem conta? <a href="/client/register.php">Criar conta</a></p>
        </div>
    </div>

    <div class="auth-info">
        <h2>Porquê CyberCore?</h2>
        <ul class="info-list">
            <li>
                <span class="icon">⚡</span>
                <div>
                    <strong>Performance Extrema</strong>
                    <span>Servidores NVMe de última geração</span>
                </div>
            </li>
            <li>
                <span class="icon">🔒</span>
                <div>
                    <strong>Segurança Total</strong>
                    <span>SSL grátis e proteção DDoS</span>
                </div>
            </li>
            <li>
                <span class="icon">💬</span>
                <div>
                    <strong>Suporte 24/7</strong>
                    <span>Equipa em português sempre disponível</span>
                </div>
            </li>
        </ul>
    </div>
</div>

<?php require_once __DIR__ . '/../inc/footer.php'; ?>
