<?php
// Reset Password Page
$page_title = 'Redefinir Password | CyberCore';
$page_description = 'Crie uma nova password para a sua conta';
$extra_css = ['/assets/css/auth.css'];

require_once __DIR__ . '/../inc/header.php';

// Get token from URL
$token = $_GET['token'] ?? '';

if (empty($token)) {
    header('Location: /client/forgot-password.php');
    exit;
}

// Handle reset password form submission
$error = '';
$success = '';
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_validate($_POST['csrf_token'] ?? null)) {
        $error = 'Token de segurança inválido. Por favor, tente novamente.';
    } else {
        $password = $_POST['password'] ?? '';
        $password_confirm = $_POST['password_confirm'] ?? '';

        // Validation
        if (strlen($password) < 8) {
            $errors['password'] = 'Password deve ter no mínimo 8 caracteres.';
        }
        if ($password !== $password_confirm) {
            $errors['password_confirm'] = 'As passwords não coincidem.';
        }

        if (empty($errors)) {
            // TODO: Validate token and update password in database
            // For now, show success message
            $success = 'Password redefinida com sucesso! Pode agora fazer login.';
        } else {
            $error = 'Por favor, corrija os erros abaixo.';
        }
    }
}
?>

<div class="auth-container auth-simple">
    <div class="auth-box">
        <div class="auth-header">
            <h1>Redefinir Password</h1>
            <p>Crie uma nova password segura para a sua conta</p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-error">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success">
                <?php echo htmlspecialchars($success); ?>
                <a href="/client/login.php" class="btn btn-primary btn-sm" style="margin-top: 1rem;">Ir para Login</a>
            </div>
        <?php else: ?>

        <form method="POST" action="" class="auth-form">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token()); ?>">
            <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
            
            <div class="form-group">
                <label for="password">Nova Password</label>
                <input 
                    type="password" 
                    id="password" 
                    name="password" 
                    required 
                    autocomplete="new-password"
                    placeholder="Mínimo 8 caracteres"
                    autofocus
                    class="<?php echo isset($errors['password']) ? 'error' : ''; ?>"
                >
                <?php if (isset($errors['password'])): ?>
                    <span class="error-message"><?php echo htmlspecialchars($errors['password']); ?></span>
                <?php endif; ?>
                <small class="form-help">Use uma combinação de letras, números e símbolos</small>
            </div>

            <div class="form-group">
                <label for="password_confirm">Confirmar Nova Password</label>
                <input 
                    type="password" 
                    id="password_confirm" 
                    name="password_confirm" 
                    required 
                    autocomplete="new-password"
                    placeholder="Repita a nova password"
                    class="<?php echo isset($errors['password_confirm']) ? 'error' : ''; ?>"
                >
                <?php if (isset($errors['password_confirm'])): ?>
                    <span class="error-message"><?php echo htmlspecialchars($errors['password_confirm']); ?></span>
                <?php endif; ?>
            </div>

            <button type="submit" class="btn btn-primary btn-block">Redefinir Password</button>
        </form>

        <div class="auth-footer">
            <p><a href="/client/login.php">Voltar ao login</a></p>
        </div>

        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../inc/footer.php'; ?>
