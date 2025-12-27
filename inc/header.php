<?php
// Load configuration
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/bootstrap.php';

// Current page detection
$current_page = basename($_SERVER['PHP_SELF'], '.php');
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?php echo htmlspecialchars($page_description ?? 'Alojamento Web profissional em Portugal. Servidores dedicados, VPS, Cloud e domínios.'); ?>">
    <meta name="keywords" content="alojamento web, hosting portugal, vps, servidor dedicado, cloud, domínios">
    <title><?php echo htmlspecialchars($page_title ?? 'CyberCore – Alojamento Web & Soluções Digitais'); ?></title>
    
    <!-- Preconnect for performance -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    
    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Stylesheets -->
    <link rel="stylesheet" href="/assets/css/base.css">
    <link rel="stylesheet" href="/assets/css/layout.css">
    <?php if (isset($extra_css)): ?>
        <?php foreach ((array)$extra_css as $css): ?>
            <link rel="stylesheet" href="<?php echo htmlspecialchars($css); ?>">
        <?php endforeach; ?>
    <?php endif; ?>
</head>
<body>
    <!-- Header -->
    <header class="site-header">
        <div class="container">
            <div class="header-inner">
                <!-- Logo -->
                <a href="/" class="logo">
                    <span class="logo-text">Cyber<strong>Core</strong></span>
                </a>

                <!-- Navigation -->
                <nav class="main-nav" id="mainNav">
                    <ul class="nav-list">
                        <li class="nav-item has-dropdown">
                            <a href="#" class="nav-link">Produtos</a>
                            <div class="dropdown">
                                <div class="dropdown-inner">
                                    <div class="dropdown-section">
                                        <h4>Alojamento</h4>
                                        <a href="/hosting.php">Alojamento Web</a>
                                        <a href="/wordpress.php">WordPress</a>
                                        <a href="/email.php">Email Profissional</a>
                                    </div>
                                    <div class="dropdown-section">
                                        <h4>Servidores</h4>
                                        <a href="/vps.php">VPS Cloud</a>
                                        <a href="/dedicated.php">Dedicados</a>
                                        <a href="/reseller.php">Revenda</a>
                                    </div>
                                    <div class="dropdown-section">
                                        <h4>Domínios & Mais</h4>
                                        <a href="/domains.php">Domínios</a>
                                        <a href="/ssl.php">Certificados SSL</a>
                                        <a href="/backup.php">Backups</a>
                                    </div>
                                </div>
                            </div>
                        </li>
                        <li class="nav-item">
                            <a href="/pricing.php" class="nav-link <?php echo $current_page === 'pricing' ? 'active' : ''; ?>">Preços</a>
                        </li>
                        <li class="nav-item">
                            <a href="/about.php" class="nav-link <?php echo $current_page === 'about' ? 'active' : ''; ?>">Sobre Nós</a>
                        </li>
                        <li class="nav-item">
                            <a href="/support.php" class="nav-link <?php echo $current_page === 'support' ? 'active' : ''; ?>">Suporte</a>
                        </li>
                        <li class="nav-item">
                            <a href="/contact.php" class="nav-link <?php echo $current_page === 'contact' ? 'active' : ''; ?>">Contacto</a>
                        </li>
                    </ul>
                </nav>

                <!-- Actions -->
                <div class="header-actions">
                    <a href="/client" class="btn btn-text">Área de Cliente</a>
                    <a href="/client" class="btn btn-primary">Entrar</a>
                    <button class="mobile-toggle" id="mobileToggle" aria-label="Menu">
                        <span></span>
                        <span></span>
                        <span></span>
                    </button>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="site-main">
