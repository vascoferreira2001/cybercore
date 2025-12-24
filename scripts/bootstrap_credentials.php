<?php
/**
 * Bootstrap de Credenciais
 *
 * Gera inc/db_credentials.php de forma segura a partir de variáveis de ambiente
 * ou por input interativo (CLI). Nunca commitamos este ficheiro ao Git.
 *
 * Uso:
 *   php scripts/bootstrap_credentials.php
 *
 * Com env vars:
 *   DB_HOST, DB_NAME, DB_USER, DB_PASS, SITE_URL, SITE_NAME
 */

error_reporting(E_ALL);
ini_set('display_errors', '1');

$projectRoot = dirname(__DIR__);
$target = $projectRoot . '/inc/db_credentials.php';

if (file_exists($target)) {
    echo "inc/db_credentials.php já existe. Nada a fazer.\n";
    exit(0);
}

function getenv_default($key, $default = '') {
    $val = getenv($key);
    return $val !== false && $val !== '' ? $val : $default;
}

function prompt($label, $default = '') {
    $line = $default ? "$label [$default]: " : "$label: ";
    echo $line;
    $input = trim(fgets(STDIN));
    return $input !== '' ? $input : $default;
}

$interactive = posix_isatty(STDIN);

$DB_HOST = getenv_default('DB_HOST', '127.0.0.1');
$DB_NAME = getenv_default('DB_NAME', 'cybercore');
$DB_USER = getenv_default('DB_USER', 'cybercore');
$DB_PASS = getenv_default('DB_PASS', '');
$SITE_URL = getenv_default('SITE_URL', 'https://seu-dominio.com');
$SITE_NAME = getenv_default('SITE_NAME', 'CyberCore - Área de Cliente');

if ($interactive) {
    echo "\n=== Bootstrap de Credenciais ===\n";
    $DB_HOST = prompt('DB_HOST', $DB_HOST);
    $DB_NAME = prompt('DB_NAME', $DB_NAME);
    $DB_USER = prompt('DB_USER', $DB_USER);
    $DB_PASS = prompt('DB_PASS', $DB_PASS);
    $SITE_URL = prompt('SITE_URL', $SITE_URL);
    $SITE_NAME = prompt('SITE_NAME', $SITE_NAME);
}

if ($DB_PASS === '') {
    fwrite(STDERR, "\nAviso: DB_PASS está vazio. Defina uma password para produção.\n");
}

$php = <<<PHP
<?php
// Credenciais da base de dados - NÃO VERSIONAR ESTE FICHEIRO
// Gerado por scripts/bootstrap_credentials.php em " . date('Y-m-d H:i:s') . "
define('DB_HOST', '" . addslashes($DB_HOST) . "');
define('DB_NAME', '" . addslashes($DB_NAME) . "');
define('DB_USER', '" . addslashes($DB_USER) . "');
define('DB_PASS', '" . addslashes($DB_PASS) . "');

// Configurações do site
define('SITE_URL', '" . addslashes($SITE_URL) . "');
define('SITE_NAME', '" . addslashes($SITE_NAME) . "');
?>
PHP;

if (!is_dir($projectRoot . '/inc')) {
    mkdir($projectRoot . '/inc', 0755, true);
}

$result = file_put_contents($target, $php);
if ($result === false) {
    fwrite(STDERR, "Falha ao escrever $target\n");
    exit(1);
}

@chmod($target, 0600);

echo "\n✓ Criado: inc/db_credentials.php\n";
echo "Permissões definidas para 600 (apenas proprietário).\n";
echo "Pronto.\n";
