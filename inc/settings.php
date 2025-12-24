<?php
// Helper para gestão de configurações

function getSetting($pdo, $key, $default = '') {
    try {
        $stmt = $pdo->prepare('SELECT setting_value FROM settings WHERE setting_key = ?');
        $stmt->execute([$key]);
        $row = $stmt->fetch();
        return $row ? $row['setting_value'] : $default;
    } catch (PDOException $e) {
        error_log('Settings error: ' . $e->getMessage());
        return $default;
    }
}

function setSetting($pdo, $key, $value) {
    try {
        $stmt = $pdo->prepare('INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = ?');
        $stmt->execute([$key, $value, $value]);
        return true;
    } catch (PDOException $e) {
        error_log('Settings error: ' . $e->getMessage());
        return false;
    }
}

// Definições gerais (valores por defeito)
function getGeneralSettingsDefaults() {
    return [
        'site_language' => 'pt-PT',
        'site_timezone' => 'Europe/Lisbon',
        'date_format' => 'd/m/Y',
        'time_format' => 'H:i',
        'week_start' => 'Segunda',
        'weekend_days' => 'Sábado,Domingo',
        'currency' => 'EUR',
        'currency_symbol' => '€',
        'currency_position' => 'left',
        'decimal_separator' => ',',
        'decimal_precision' => '2',
        'cron_interval_minutes' => '10',
    ];
}

// Ler definições gerais aplicando defaults
function getGeneralSettings($pdo) {
    $defaults = getGeneralSettingsDefaults();
    $settings = [];
    foreach ($defaults as $key => $value) {
        $settings[$key] = getSetting($pdo, $key, $value);
    }
    return $settings;
}

// Aplicar definições globais (timezone)
function applyGeneralSettings($pdo) {
    $settings = getGeneralSettings($pdo);
    if (!empty($settings['site_timezone'])) {
        date_default_timezone_set($settings['site_timezone']);
    }
    return $settings;
}

// Obter URL pública do ficheiro
function getAssetUrl($relativePath) {
    if (!$relativePath) return '';
    // Garantir que o caminho começa com /
    if (strpos($relativePath, '/') !== 0) {
        $relativePath = '/' . $relativePath;
    }
    return $relativePath;
}

// Obter caminho absoluto do servidor para verificação de ficheiro
function getAssetPath($relativePath) {
    if (!$relativePath) return '';
    $docRoot = $_SERVER['DOCUMENT_ROOT'];
    return $docRoot . '/' . ltrim($relativePath, '/');
}

// Validação de upload de imagem
function validateImageUpload($file, $maxSizeKB = 5000, $allowedFormats = ['jpg', 'jpeg', 'png']) {
    $errors = [];
    
    if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
        $errors[] = 'Erro ao fazer upload da imagem.';
        return $errors;
    }
    
    // Verificar tamanho
    if ($file['size'] > ($maxSizeKB * 1024)) {
        $errors[] = "Ficheiro não pode exceder {$maxSizeKB}KB.";
    }
    
    // Verificar extensão
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $allowedFormats)) {
        $errors[] = 'Formato não permitido. Use: ' . implode(', ', $allowedFormats);
    }
    
    // Verificar MIME type
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    $allowedMimes = ['image/jpeg', 'image/png', 'image/gif'];
    if (!in_array($mime, $allowedMimes)) {
        $errors[] = 'Tipo de ficheiro inválido.';
    }
    
    return $errors;
}

// Guardar ficheiro de upload
function saveUploadedFile($file, $uploadDir = 'uploads/') {
    $docRoot = $_SERVER['DOCUMENT_ROOT'];
    $fullUploadDir = $docRoot . '/' . ltrim($uploadDir, '/');
    
    if (!is_dir($fullUploadDir)) {
        mkdir($fullUploadDir, 0755, true);
    }
    
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $filename = uniqid('img_') . '.' . $ext;
    $filepath = $fullUploadDir . $filename;
    $relativePath = '/' . ltrim($uploadDir, '/') . $filename;
    
    if (move_uploaded_file($file['tmp_name'], $filepath)) {
        return $relativePath;
    }
    
    return false;
}

// Eliminar ficheiro antigo
function deleteOldFile($filepath) {
    if (!$filepath) return;
    $docRoot = $_SERVER['DOCUMENT_ROOT'];
    $fullPath = $docRoot . $filepath;
    if (file_exists($fullPath)) {
        unlink($fullPath);
    }
}

?>
