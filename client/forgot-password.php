<?php
// Forgot Password Page
$page_title = 'Recuperar Password | CyberCore';
$page_description = 'Recupere o acesso à sua conta';
$extra_css = ['/assets/css/auth.css'];

require_once __DIR__ . '/../inc/header.php';

// Handle forgot password form submission
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_validate($_POST['csrf_token'] ?? null)) {
        $error = 'Token de segurança inválido. Por favor, tente novamente.';
    } else {
        $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);

        if (!$email) {
            $error = 'Por favor, insira um email válido.';
        } else {
            // TODO: Generate reset token and send email
            // For now, show success message
            $success = 'Se o email existir na nossa base de dados, receberá instruções para redefinir a sua password.';
        }
    }
}
?>

<div class="auth-container auth-simple">
    <div class="auth-box">
        <div class="auth-header">
            <h1>Recuperar Password</h1>
            <p>Insira o seu email para receber instruções de recuperação</p>
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
            <div class="auth-footer">
                <p><a href="/client/login.php">Voltar ao login</a></p>
            </div>
        <?php else: ?>

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
                    autofocus
                >
                <small class="form-help">Enviaremos um link de recuperação para este email</small>
            </div>

            <button type="submit" class="btn btn-primary btn-block">Enviar Link de Recuperação</button>
        </form>

        <div class="auth-footer">
            <p>Lembrou-se da password? <a href="/client/login.php">Fazer login</a></p>
            <p>Ainda não tem conta? <a href="/client/register.php">Criar conta</a></p>
        </div>

        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../inc/footer.php'; ?>
