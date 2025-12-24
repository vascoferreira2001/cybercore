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
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $filename = uniqid('img_') . '.' . $ext;
    $filepath = $uploadDir . $filename;
    
    if (move_uploaded_file($file['tmp_name'], $filepath)) {
        return $filepath;
    }
    
    return false;
}

// Eliminar ficheiro antigo
function deleteOldFile($filepath) {
    if ($filepath && file_exists($filepath)) {
        unlink($filepath);
    }
}
