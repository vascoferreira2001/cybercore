<?php

declare(strict_types=1);

session_name('cybercore_maintenance');
session_start();

$rootDir = __DIR__;
$htaccessFile = $rootDir . '/.htaccess';
$webConfigFile = $rootDir . '/web.config';

$authUser = getenv('CYBERCORE_MAINT_USER') ?: 'vascoferreira@cybercore.pt';
$defaultPasswordHash = '$2y$12$LhT1X7JPrbSGyKa.VZHMPu/zldLq4/d7r8RLRaFR3HXneI/bbo76e';
$envPasswordHash = getenv('CYBERCORE_MAINT_PASS_HASH');
$envPasswordPlain = getenv('CYBERCORE_MAINT_PASS');

if (is_string($envPasswordHash) && $envPasswordHash !== '') {
  $authPasswordHash = $envPasswordHash;
} elseif (is_string($envPasswordPlain) && $envPasswordPlain !== '') {
  $authPasswordHash = password_hash($envPasswordPlain, PASSWORD_DEFAULT);
} else {
  $authPasswordHash = $defaultPasswordHash;
}

$usingDefaultAuth = getenv('CYBERCORE_MAINT_USER') === false || (getenv('CYBERCORE_MAINT_PASS_HASH') === false && getenv('CYBERCORE_MAINT_PASS') === false);

$docRoot = rtrim((string)($_SERVER['DOCUMENT_ROOT'] ?? ''), '/\\');
$flagFiles = [$rootDir . '/.maintenance-on'];
if ($docRoot !== '') {
  $docRootFlag = $docRoot . '/.maintenance-on';
  if (!in_array($docRootFlag, $flagFiles, true)) {
    $flagFiles[] = $docRootFlag;
  }
}

$htaccessStart = '# BEGIN CYBERCORE MAINTENANCE IPS';
$htaccessEnd = '# END CYBERCORE MAINTENANCE IPS';
$webConfigStart = '<!-- BEGIN CYBERCORE MAINTENANCE IPS -->';
$webConfigEnd = '<!-- END CYBERCORE MAINTENANCE IPS -->';

function redirectToSelf(): void
{
  $self = (string)($_SERVER['PHP_SELF'] ?? '/maintenance-control.php');
  header('Location: ' . $self);
  exit;
}

function normalizeIps(string $raw): array
{
  $parts = preg_split('/[\s,;]+/', trim($raw)) ?: [];
  $ips = [];
  $invalid = [];

  foreach ($parts as $item) {
    $candidate = trim($item);
    if ($candidate === '') {
      continue;
    }

    if (filter_var($candidate, FILTER_VALIDATE_IP) === false) {
      $invalid[] = $candidate;
      continue;
    }

    $ips[] = $candidate;
  }

  if ($invalid !== []) {
    throw new RuntimeException('IPs inválidos: ' . implode(', ', $invalid));
  }

  return array_values(array_unique($ips));
}

function decodeEscapedIp(string $escapedIp): string
{
  return preg_replace('/\\\\([.:])/', '$1', $escapedIp) ?? $escapedIp;
}

function extractBlock(string $content, string $startMarker, string $endMarker): string
{
  $startPos = strpos($content, $startMarker);
  $endPos = strpos($content, $endMarker);

  if ($startPos === false || $endPos === false || $endPos <= $startPos) {
    return '';
  }

  return substr($content, $startPos, $endPos - $startPos);
}

function extractCurrentIpsFromHtaccess(string $content, string $startMarker, string $endMarker): array
{
  $block = extractBlock($content, $startMarker, $endMarker);
  if ($block === '') {
    return [];
  }

  preg_match_all('/RewriteCond\s+%\{REMOTE_ADDR\}\s+!\^(.+?)\$/', $block, $matches);

  $ips = [];
  foreach ($matches[1] ?? [] as $escapedIp) {
    $ips[] = decodeEscapedIp($escapedIp);
  }

  return array_values(array_unique($ips));
}

function extractCurrentIpsFromWebConfig(string $content, string $startMarker, string $endMarker): array
{
  $block = extractBlock($content, $startMarker, $endMarker);
  if ($block === '') {
    return [];
  }

  preg_match_all('/<add\s+input="\{REMOTE_ADDR\}"\s+pattern="\^(.+?)\$"\s+negate="true"\s*\/?\s*>/i', $block, $matches);

  $ips = [];
  foreach ($matches[1] ?? [] as $escapedIp) {
    $ips[] = decodeEscapedIp($escapedIp);
  }

  return array_values(array_unique($ips));
}

function replaceBlock(string $content, string $startMarker, string $endMarker, string $replacement): ?string
{
  $startPos = strpos($content, $startMarker);
  $endPos = strpos($content, $endMarker);

  if ($startPos === false || $endPos === false || $endPos <= $startPos) {
    return null;
  }

  $before = substr($content, 0, $startPos);
  $after = substr($content, $endPos + strlen($endMarker));

  return $before . $replacement . $after;
}

function updateHtaccessIpBlockIfPresent(string $filePath, array $ips, string $startMarker, string $endMarker): bool
{
  if (!is_file($filePath)) {
    return false;
  }

  $content = file_get_contents($filePath);
  if ($content === false) {
    throw new RuntimeException('Não foi possível ler o .htaccess.');
  }

  $lines = [$startMarker];
  foreach ($ips as $ip) {
    $safeIpRegex = preg_quote($ip, '/');
    $lines[] = '    RewriteCond %{REMOTE_ADDR} !^' . $safeIpRegex . '$';
  }
  $lines[] = '    ' . $endMarker;

  $replacement = implode(PHP_EOL, $lines);
  $newContent = replaceBlock($content, $startMarker, $endMarker, $replacement);
  if ($newContent === null) {
    return false;
  }

  if ($newContent !== $content && file_put_contents($filePath, $newContent, LOCK_EX) === false) {
    throw new RuntimeException('Não foi possível escrever no .htaccess.');
  }

  return true;
}

function updateWebConfigIpBlockIfPresent(string $filePath, array $ips, string $startMarker, string $endMarker): bool
{
  if (!is_file($filePath)) {
    return false;
  }

  $content = file_get_contents($filePath);
  if ($content === false) {
    throw new RuntimeException('Não foi possível ler o web.config.');
  }

  $lines = ['            ' . $startMarker];
  foreach ($ips as $ip) {
    $safeIpRegex = preg_quote($ip, '/');
    $lines[] = '            <add input="{REMOTE_ADDR}" pattern="^' . $safeIpRegex . '$" negate="true" />';
  }
  $lines[] = '            ' . $endMarker;

  $replacement = implode(PHP_EOL, $lines);
  $newContent = replaceBlock($content, $startMarker, $endMarker, $replacement);
  if ($newContent === null) {
    return false;
  }

  if ($newContent !== $content && file_put_contents($filePath, $newContent, LOCK_EX) === false) {
    throw new RuntimeException('Não foi possível escrever no web.config.');
  }

  return true;
}

function updateIpAllowLists(
  array $ips,
  string $htaccessFile,
  string $htaccessStart,
  string $htaccessEnd,
  string $webConfigFile,
  string $webConfigStart,
  string $webConfigEnd
): array {
  $updated = [];

  if (updateHtaccessIpBlockIfPresent($htaccessFile, $ips, $htaccessStart, $htaccessEnd)) {
    $updated[] = '.htaccess';
  }

  if (updateWebConfigIpBlockIfPresent($webConfigFile, $ips, $webConfigStart, $webConfigEnd)) {
    $updated[] = 'web.config';
  }

  if ($updated === []) {
    throw new RuntimeException('Não foi encontrado bloco de IPs em .htaccess nem web.config.');
  }

  return $updated;
}

function setMaintenanceFlagFiles(array $flagFiles, bool $enabled): void
{
  if ($enabled) {
    foreach ($flagFiles as $flagFile) {
      if (file_put_contents($flagFile, 'on', LOCK_EX) === false) {
        throw new RuntimeException('Não foi possível ativar o modo de manutenção em: ' . $flagFile);
      }
    }

    return;
  }

  foreach ($flagFiles as $flagFile) {
    if (is_file($flagFile) && !unlink($flagFile)) {
      throw new RuntimeException('Não foi possível desativar o modo de manutenção em: ' . $flagFile);
    }
  }
}

function setIisMaintenanceEnabledIfPresent(string $webConfigFile, bool $enabled): bool
{
  if (!is_file($webConfigFile)) {
    return false;
  }

  $content = file_get_contents($webConfigFile);
  if ($content === false) {
    throw new RuntimeException('Não foi possível ler o web.config.');
  }

  $newValue = $enabled ? 'true' : 'false';
  $count = 0;

  $newContent = preg_replace_callback(
    '/<rule\s+name="CyberCore Maintenance"[^>]*>/i',
    static function (array $matches) use ($newValue): string {
      $tag = $matches[0];

      if (preg_match('/\senabled="(true|false)"/i', $tag)) {
        return preg_replace('/\senabled="(true|false)"/i', ' enabled="' . $newValue . '"', $tag) ?? $tag;
      }

      return rtrim(substr($tag, 0, -1)) . ' enabled="' . $newValue . '">';
    },
    $content,
    1,
    $count
  );

  if ($newContent === null || $count === 0) {
    return false;
  }

  if ($newContent !== $content && file_put_contents($webConfigFile, $newContent, LOCK_EX) === false) {
    throw new RuntimeException('Não foi possível escrever no web.config.');
  }

  return true;
}

function isIisMaintenanceEnabled(string $webConfigFile): bool
{
  if (!is_file($webConfigFile)) {
    return false;
  }

  $content = file_get_contents($webConfigFile);
  if ($content === false) {
    return false;
  }

  return preg_match('/<rule\s+name="CyberCore Maintenance"[^>]*\senabled="true"[^>]*>/i', $content) === 1;
}

function isMaintenanceEnabled(array $flagFiles, string $webConfigFile): bool
{
  if (isIisMaintenanceEnabled($webConfigFile)) {
    return true;
  }

  foreach ($flagFiles as $flagFile) {
    if (is_file($flagFile)) {
      return true;
    }
  }

  return false;
}

function renderLoginPage(string $errorMessage, bool $usingDefaultAuth): void
{
  ?>
<!doctype html>
<html lang="pt-PT">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>CyberCore — Login Manutenção</title>
  <style>
    body {
      margin: 0;
      min-height: 100vh;
      display: grid;
      place-items: center;
      padding: 24px;
      background: #0b1020;
      color: #e8ecff;
      font-family: Inter, Arial, sans-serif;
    }

    .box {
      width: min(420px, 100%);
      background: #121933;
      border: 1px solid rgba(255, 255, 255, 0.12);
      border-radius: 14px;
      padding: 20px;
    }

    h1 {
      margin: 0 0 6px;
      font-size: 1.4rem;
    }

    p {
      margin: 0 0 14px;
      color: #a9b3d9;
      font-size: 0.94rem;
    }

    label {
      display: block;
      margin: 10px 0 6px;
      font-weight: 600;
      font-size: 0.92rem;
    }

    input {
      width: 100%;
      height: 42px;
      border-radius: 10px;
      border: 1px solid rgba(255, 255, 255, 0.2);
      background: #0f1831;
      color: #e8ecff;
      padding: 0 12px;
      box-sizing: border-box;
    }

    button {
      margin-top: 14px;
      width: 100%;
      height: 42px;
      border: 0;
      border-radius: 10px;
      cursor: pointer;
      font-weight: 700;
      background: #2973ec;
      color: #fff;
    }

    .err {
      margin: 0 0 10px;
      border: 1px solid rgba(239, 68, 68, 0.45);
      background: rgba(239, 68, 68, 0.2);
      padding: 9px 11px;
      border-radius: 10px;
      color: #ffd7d7;
      font-size: 0.9rem;
    }

    .warn {
      margin-top: 10px;
      border: 1px solid rgba(250, 204, 21, 0.45);
      background: rgba(250, 204, 21, 0.2);
      padding: 9px 11px;
      border-radius: 10px;
      color: #fff7cf;
      font-size: 0.85rem;
    }
  </style>
</head>
<body>
  <main class="box">
    <h1>Acesso ao painel de manutenção</h1>
    <p>Introduz as credenciais para gerir o modo de manutenção.</p>

    <?php if ($errorMessage !== ''): ?>
      <div class="err"><?php echo htmlspecialchars($errorMessage, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>

    <form method="post">
      <input type="hidden" name="action" value="login" />
      <label for="login_user">Utilizador</label>
      <input id="login_user" name="login_user" autocomplete="username" required />

      <label for="login_password">Password</label>
      <input id="login_password" name="login_password" type="password" autocomplete="current-password" required />

      <button type="submit">Entrar</button>
    </form>

    <?php if ($usingDefaultAuth): ?>
      <div class="warn">Credenciais por defeito ativas. Define CYBERCORE_MAINT_USER e CYBERCORE_MAINT_PASS_HASH (ou CYBERCORE_MAINT_PASS para retrocompatibilidade).</div>
    <?php endif; ?>
  </main>
</body>
</html>
  <?php
}

$loginError = '';

if (($_POST['action'] ?? '') === 'logout') {
  $_SESSION = [];
  if (session_status() === PHP_SESSION_ACTIVE) {
    session_regenerate_id(true);
  }
  redirectToSelf();
}

if (!($_SESSION['maintenance_authenticated'] ?? false)) {
  if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'login') {
    $inputUser = trim((string)($_POST['login_user'] ?? ''));
    $inputPassword = (string)($_POST['login_password'] ?? '');

    if (hash_equals($authUser, $inputUser) && password_verify($inputPassword, $authPasswordHash)) {
      $_SESSION['maintenance_authenticated'] = true;
      $_SESSION['maintenance_user'] = $authUser;
      session_regenerate_id(true);
      redirectToSelf();
    }

    $loginError = 'Credenciais inválidas.';
  }

  renderLoginPage($loginError, $usingDefaultAuth);
  exit;
}

$message = '';
$error = '';
$clientIp = (string)($_SERVER['REMOTE_ADDR'] ?? 'desconhecido');
$serverSoftware = (string)($_SERVER['SERVER_SOFTWARE'] ?? 'desconhecido');

try {
  $currentIps = [];

  if (is_file($webConfigFile)) {
    $webConfigContent = file_get_contents($webConfigFile);
    if ($webConfigContent === false) {
      throw new RuntimeException('Não foi possível ler o web.config.');
    }

    $currentIps = extractCurrentIpsFromWebConfig($webConfigContent, $webConfigStart, $webConfigEnd);
  } elseif (is_file($htaccessFile)) {
    $htaccessContent = file_get_contents($htaccessFile);
    if ($htaccessContent === false) {
      throw new RuntimeException('Não foi possível ler o .htaccess.');
    }

    $currentIps = extractCurrentIpsFromHtaccess($htaccessContent, $htaccessStart, $htaccessEnd);
  }

  if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'toggle') {
      $mode = $_POST['mode'] ?? '';

      if ($mode === 'on') {
        setMaintenanceFlagFiles($flagFiles, true);
        setIisMaintenanceEnabledIfPresent($webConfigFile, true);
        $message = 'Modo de manutenção ativado com sucesso.';
      } elseif ($mode === 'off') {
        setMaintenanceFlagFiles($flagFiles, false);
        setIisMaintenanceEnabledIfPresent($webConfigFile, false);
        $message = 'Modo de manutenção desativado com sucesso.';
      } else {
        throw new RuntimeException('Ação de manutenção inválida.');
      }
    }

    if ($action === 'quick_off_open') {
      setMaintenanceFlagFiles($flagFiles, false);
      setIisMaintenanceEnabledIfPresent($webConfigFile, false);
      header('Location: /?t=' . time());
      exit;
    }

    if ($action === 'save_ips') {
      $rawIps = $_POST['ips'] ?? '';
      $ips = normalizeIps($rawIps);

      $updatedFiles = updateIpAllowLists(
        $ips,
        $htaccessFile,
        $htaccessStart,
        $htaccessEnd,
        $webConfigFile,
        $webConfigStart,
        $webConfigEnd
      );

      $currentIps = $ips;
      $message = 'Lista de IPs autorizados atualizada com sucesso em: ' . implode(', ', $updatedFiles) . '.';
    }
  }

  $maintenanceEnabled = isMaintenanceEnabled($flagFiles, $webConfigFile);
  $ipTextarea = implode(PHP_EOL, $currentIps);
} catch (Throwable $exception) {
  $maintenanceEnabled = isMaintenanceEnabled($flagFiles, $webConfigFile);
  $ipTextarea = '';
  $error = $exception->getMessage();
}
?>
<!doctype html>
<html lang="pt-PT">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>CyberCore — Controlo de Manutenção</title>
  <style>
  body {
    margin: 0;
    padding: 24px;
    font-family: Inter, Arial, sans-serif;
    background: #0b1020;
    color: #e8ecff;
  }

  .wrap {
    width: min(880px, 100%);
    margin: 0 auto;
    background: #121933;
    border: 1px solid rgba(255, 255, 255, 0.12);
    border-radius: 14px;
    padding: 20px;
  }

  h1 {
    margin: 0 0 4px;
    font-size: 1.6rem;
  }

  .status {
    margin: 0 0 8px;
    color: #b7c6ff;
  }

  .pill {
    display: inline-block;
    border-radius: 999px;
    padding: 4px 10px;
    font-size: 0.8rem;
    font-weight: 700;
    margin-left: 8px;
  }

  .on {
    background: #1d9a5a;
    color: #fff;
  }

  .off {
    background: #6b7280;
    color: #fff;
  }

  .row {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
    margin-bottom: 16px;
  }

  button {
    border: 0;
    border-radius: 10px;
    padding: 10px 14px;
    cursor: pointer;
    font-weight: 600;
  }

  .btn-on { background: #16a34a; color: #fff; }
  .btn-off { background: #dc2626; color: #fff; }
  .btn-open { background: #0891b2; color: #fff; }
  .btn-save { background: #2973ec; color: #fff; }
  .btn-logout { background: #4b5563; color: #fff; }

  textarea {
    width: 100%;
    min-height: 170px;
    border-radius: 10px;
    border: 1px solid rgba(255, 255, 255, 0.18);
    background: #0f1831;
    color: #e8ecff;
    padding: 12px;
    resize: vertical;
    font-family: ui-monospace, SFMono-Regular, Menlo, monospace;
    margin-bottom: 12px;
  }

  .hint {
    color: #a9b3d9;
    font-size: 0.92rem;
    margin-top: 0;
  }

  .msg {
    margin: 0 0 12px;
    padding: 10px 12px;
    border-radius: 10px;
    font-size: 0.95rem;
  }

  .ok {
    background: rgba(34, 197, 94, 0.2);
    border: 1px solid rgba(34, 197, 94, 0.45);
  }

  .err {
    background: rgba(239, 68, 68, 0.2);
    border: 1px solid rgba(239, 68, 68, 0.45);
  }
  </style>
</head>
<body>
  <main class="wrap">
  <h1>Controlo de Manutenção</h1>
  <p class="status">
    Estado atual:
    <?php if ($maintenanceEnabled): ?>
    <span class="pill on">ATIVO</span>
    <?php else: ?>
    <span class="pill off">INATIVO</span>
    <?php endif; ?>
  </p>
  <p class="hint">Servidor: <?php echo htmlspecialchars($serverSoftware, ENT_QUOTES, 'UTF-8'); ?></p>
  <p class="hint">IP atual detetado: <?php echo htmlspecialchars($clientIp, ENT_QUOTES, 'UTF-8'); ?></p>
  <p class="hint">Utilizador autenticado: <?php echo htmlspecialchars((string)($_SESSION['maintenance_user'] ?? 'desconhecido'), ENT_QUOTES, 'UTF-8'); ?></p>

  <?php if ($message !== ''): ?>
    <p class="msg ok"><?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?></p>
  <?php endif; ?>

  <?php if ($error !== ''): ?>
    <p class="msg err"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></p>
  <?php endif; ?>

  <form method="post" class="row">
    <input type="hidden" name="action" value="toggle" />
    <button class="btn-on" type="submit" name="mode" value="on">Ativar manutenção</button>
    <button class="btn-off" type="submit" name="mode" value="off">Desativar manutenção</button>
  </form>

  <form method="post" class="row">
    <input type="hidden" name="action" value="quick_off_open" />
    <button class="btn-open" type="submit">Desativar manutenção e abrir website</button>
  </form>

  <form method="post">
    <input type="hidden" name="action" value="save_ips" />
    <label for="ips"><strong>IPs autorizados (1 por linha)</strong></label>
    <textarea id="ips" name="ips" spellcheck="false"><?php echo htmlspecialchars($ipTextarea, ENT_QUOTES, 'UTF-8'); ?></textarea>
    <button class="btn-save" type="submit">Guardar IPs autorizados</button>
    <p class="hint">Os IPs nesta lista ignoram a manutenção e conseguem aceder normalmente ao site. Se ficar vazio, ninguém é autorizado por IP.</p>
  </form>

  <form method="post" class="row">
    <input type="hidden" name="action" value="logout" />
    <button class="btn-logout" type="submit">Terminar sessão</button>
  </form>
  </main>
</body>
</html>
