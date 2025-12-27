<!doctype html>
<html lang="pt">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Acesso Negado - CyberCore</title>
  <link rel="stylesheet" href="assets/css/shared/style.css">
  <link rel="stylesheet" href="assets/css/shared/design-system.css">
  <style>
    body {
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 20px;
    }
    .access-denied-card {
      background: white;
      border-radius: 16px;
      box-shadow: 0 20px 60px rgba(0,0,0,0.3);
      max-width: 600px;
      padding: 40px;
      text-align: center;
    }
    .icon-wrapper {
      width: 80px;
      height: 80px;
      background: #fee;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      margin: 0 auto 24px;
    }
    .icon-wrapper svg {
      width: 48px;
      height: 48px;
      stroke: #dc2626;
    }
    h1 {
      color: #1f2937;
      font-size: 28px;
      font-weight: 700;
      margin: 0 0 12px;
    }
    .subtitle {
      color: #6b7280;
      font-size: 16px;
      margin-bottom: 24px;
    }
    .info-box {
      background: #f9fafb;
      border: 1px solid #e5e7eb;
      border-radius: 8px;
      padding: 16px;
      margin: 24px 0;
      text-align: left;
    }
    .info-box strong {
      display: block;
      color: #374151;
      margin-bottom: 8px;
    }
    .info-box code {
      background: white;
      border: 1px solid #e5e7eb;
      padding: 4px 8px;
      border-radius: 4px;
      font-size: 13px;
      color: #dc2626;
    }
    .actions {
      display: flex;
      gap: 12px;
      justify-content: center;
      margin-top: 32px;
    }
    .btn {
      padding: 12px 24px;
      border-radius: 8px;
      text-decoration: none;
      font-weight: 600;
      transition: all 0.2s;
    }
    .btn-primary {
      background: #007dff;
      color: white;
    }
    .btn-primary:hover {
      background: #0066cc;
      transform: translateY(-1px);
    }
    .btn-secondary {
      background: #f3f4f6;
      color: #374151;
    }
    .btn-secondary:hover {
      background: #e5e7eb;
    }
  </style>
</head>
<body>
  <div class="access-denied-card">
    <div class="icon-wrapper">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <circle cx="12" cy="12" r="10"></circle>
        <line x1="15" y1="9" x2="9" y2="15"></line>
        <line x1="9" y1="9" x2="15" y2="15"></line>
      </svg>
    </div>
    
    <h1>Acesso Negado</h1>
    <p class="subtitle">Não tem permissões suficientes para aceder a esta página</p>
    
    <?php
    session_start();
    $reason = isset($_GET['reason']) ? $_GET['reason'] : 'unknown';
    $required = isset($_GET['required']) ? $_GET['required'] : '';
    $userRole = isset($_SESSION['user_role']) ? $_SESSION['user_role'] : 'Desconhecido';
    
    if ($reason === 'insufficient_role' && !empty($required)):
      $requiredRoles = explode(',', $required);
    ?>
      <div class="info-box">
        <strong>Detalhes:</strong>
        <p style="margin: 8px 0; color: #6b7280;">
          O seu cargo atual é <code><?php echo htmlspecialchars($userRole); ?></code>
        </p>
        <p style="margin: 8px 0; color: #6b7280;">
          Esta página requer um dos seguintes cargos:
        </p>
        <div style="margin-top: 8px;">
          <?php foreach ($requiredRoles as $role): ?>
            <code style="margin-right: 8px;"><?php echo htmlspecialchars($role); ?></code>
          <?php endforeach; ?>
        </div>
      </div>
    <?php else: ?>
      <div class="info-box">
        <p style="color: #6b7280; margin: 0;">
          Se acredita que isto é um erro, contacte o administrador do sistema.
        </p>
      </div>
    <?php endif; ?>
    
    <div class="actions">
      <a class="btn btn-primary" href="/dashboard.php">
        <svg style="width:16px;height:16px;display:inline-block;vertical-align:middle;margin-right:6px" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
        </svg>
        Voltar ao Dashboard
      </a>
      <a class="btn btn-secondary" href="/support.php">
        <svg style="width:16px;height:16px;display:inline-block;vertical-align:middle;margin-right:6px" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
        </svg>
        Contactar Suporte
      </a>
    </div>
    
    <p style="margin-top: 32px; font-size: 14px; color: #9ca3af;">
      Referência: <?php echo date('Y-m-d H:i:s'); ?> • IP: <?php echo htmlspecialchars($_SERVER['REMOTE_ADDR'] ?? 'unknown'); ?>
    </p>
  </div>
</body>
</html>
