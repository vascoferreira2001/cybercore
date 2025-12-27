<?php
// Register Page
$page_title = 'Criar Conta | CyberCore';
$page_description = 'Crie a sua conta CyberCore e comece hoje';
$extra_css = ['/assets/css/auth.css'];

require_once __DIR__ . '/../inc/header.php';

// Handle registration form submission
$error = '';
$success = '';
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_validate($_POST['csrf_token'] ?? null)) {
        $error = 'Token de segurança inválido. Por favor, tente novamente.';
    } else {
        // Validate inputs
        $first_name = trim($_POST['first_name'] ?? '');
        $last_name = trim($_POST['last_name'] ?? '');
        $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
        $password = $_POST['password'] ?? '';
        $password_confirm = $_POST['password_confirm'] ?? '';
        $terms = isset($_POST['terms']);

        // Validation
        if (empty($first_name)) {
            $errors['first_name'] = 'Nome é obrigatório.';
        }
        if (empty($last_name)) {
            $errors['last_name'] = 'Apelido é obrigatório.';
        }
        if (!$email) {
            $errors['email'] = 'Email inválido.';
        }
        if (strlen($password) < 8) {
            $errors['password'] = 'Password deve ter no mínimo 8 caracteres.';
        }
        if ($password !== $password_confirm) {
            $errors['password_confirm'] = 'As passwords não coincidem.';
        }
        if (!$terms) {
            $errors['terms'] = 'Deve aceitar os termos de serviço.';
        }

        if (empty($errors)) {
            // TODO: Create user in database
            // For now, show success message
            $success = 'Conta criada com sucesso! Pode agora fazer login.';
        } else {
            $error = 'Por favor, corrija os erros abaixo.';
        }
    }
}
?>

<div class="auth-container">
    <div class="auth-box">
        <div class="auth-header">
            <h1>Criar Conta</h1>
            <p>Junte-se a milhares de clientes satisfeitos</p>
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
            
            <div class="form-row">
                <div class="form-group">
                    <label for="first_name">Nome *</label>
                    <input 
                        type="text" 
                        id="first_name" 
                        name="first_name" 
                        value="<?php echo htmlspecialchars($_POST['first_name'] ?? ''); ?>"
                        required 
                        autocomplete="given-name"
                        placeholder="João"
                        class="<?php echo isset($errors['first_name']) ? 'error' : ''; ?>"
                    >
                    <?php if (isset($errors['first_name'])): ?>
                        <span class="error-message"><?php echo htmlspecialchars($errors['first_name']); ?></span>
                    <?php endif; ?>
                </div>

                <div class="form-group">
                    <label for="last_name">Apelido *</label>
                    <input 
                        type="text" 
                        id="last_name" 
                        name="last_name" 
                        value="<?php echo htmlspecialchars($_POST['last_name'] ?? ''); ?>"
                        required 
                        autocomplete="family-name"
                        placeholder="Silva"
                        class="<?php echo isset($errors['last_name']) ? 'error' : ''; ?>"
                    >
                    <?php if (isset($errors['last_name'])): ?>
                        <span class="error-message"><?php echo htmlspecialchars($errors['last_name']); ?></span>
                    <?php endif; ?>
                </div>
            </div>

            <div class="form-group">
                <label for="email">Email *</label>
                <input 
                    type="email" 
                    id="email" 
                    name="email" 
                    value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                    required 
                    autocomplete="email"
                    placeholder="seu@email.com"
                    class="<?php echo isset($errors['email']) ? 'error' : ''; ?>"
                >
                <?php if (isset($errors['email'])): ?>
                    <span class="error-message"><?php echo htmlspecialchars($errors['email']); ?></span>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label for="password">Password *</label>
                <input 
                    type="password" 
                    id="password" 
                    name="password" 
                    required 
                    autocomplete="new-password"
                    placeholder="Mínimo 8 caracteres"
                    class="<?php echo isset($errors['password']) ? 'error' : ''; ?>"
                >
                <?php if (isset($errors['password'])): ?>
                    <span class="error-message"><?php echo htmlspecialchars($errors['password']); ?></span>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label for="password_confirm">Confirmar Password *</label>
                <input 
                    type="password" 
                    id="password_confirm" 
                    name="password_confirm" 
                    required 
                    autocomplete="new-password"
                    placeholder="Repita a password"
                    class="<?php echo isset($errors['password_confirm']) ? 'error' : ''; ?>"
                >
                <?php if (isset($errors['password_confirm'])): ?>
                    <span class="error-message"><?php echo htmlspecialchars($errors['password_confirm']); ?></span>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label class="checkbox">
                    <input type="checkbox" name="terms" <?php echo isset($_POST['terms']) ? 'checked' : ''; ?>>
                    <span>Aceito os <a href="/terms.php" target="_blank">Termos de Serviço</a> e <a href="/privacy.php" target="_blank">Política de Privacidade</a></span>
                </label>
                <?php if (isset($errors['terms'])): ?>
                    <span class="error-message"><?php echo htmlspecialchars($errors['terms']); ?></span>
                <?php endif; ?>
            </div>

            <button type="submit" class="btn btn-primary btn-block">Criar Conta</button>
        </form>

        <?php endif; ?>

        <div class="auth-footer">
            <p>Já tem conta? <a href="/client/login.php">Fazer login</a></p>
        </div>
    </div>

    <div class="auth-info">
        <h2>O Que Está Incluído</h2>
        <ul class="info-list">
            <li>
                <span class="icon">✓</span>
                <div>
                    <strong>Painel de Controlo Intuitivo</strong>
                    <span>Gerencie todos os seus serviços facilmente</span>
                </div>
            </li>
            <li>
                <span class="icon">✓</span>
                <div>
                    <strong>Suporte Técnico Incluído</strong>
                    <span>Equipa especializada sempre disponível</span>
                </div>
            </li>
            <li>
                <span class="icon">✓</span>
                <div>
                    <strong>Sem Taxas Ocultas</strong>
                    <span>Preços transparentes e sem surpresas</span>
                </div>
            </li>
        </ul>
    </div>
</div>

<?php require_once __DIR__ . '/../inc/footer.php'; ?>
