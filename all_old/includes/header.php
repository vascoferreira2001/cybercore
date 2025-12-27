<?php
if (session_status() === PHP_SESSION_NONE) session_start();
$SITE_NAME = 'CyberCore - Alojamento Web & Soluções Digitais';
?>
<!doctype html>
<html lang="pt">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="description" content="CyberCore • Alojamento Web, Email, Domínios, VPS, Servidores Dedicados, Desenvolvimento Web e Gestão de Redes Sociais.">
  <meta property="og:title" content="CyberCore - Alojamento Web & Soluções Digitais">
  <meta property="og:description" content="Infraestrutura moderna e Área de Cliente profissional para gerir serviços com segurança e performance.">
  <meta property="og:type" content="website">
  <meta property="og:url" content="https://www.dominio.pt/">
  <meta property="og:image" content="/assets/img/og-image.svg">
  <meta name="twitter:card" content="summary_large_image">
  <meta name="twitter:title" content="CyberCore - Alojamento Web & Soluções Digitais">
  <meta name="twitter:description" content="Infraestrutura moderna e Área de Cliente profissional para gerir serviços com segurança e performance.">
  <meta name="twitter:image" content="/assets/img/og-image.svg">
  <link rel="canonical" href="https://www.dominio.pt/">
  <title><?php echo htmlspecialchars($SITE_NAME); ?></title>
  <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="/assets/css/website.css">
  <link rel="icon" type="image/svg+xml" href="/assets/img/favicon.svg">
  <script type="application/ld+json">
  {
    "@context": "https://schema.org",
    "@type": "Organization",
    "name": "CyberCore",
    "url": "https://www.dominio.pt/",
    "logo": "https://www.dominio.pt/assets/img/favicon.svg",
    "sameAs": [
      "https://www.facebook.com/",
      "https://www.linkedin.com/"
    ]
  }
  </script>
</head>
<body>
<header class="site-header">
  <div class="container header-inner">
    <a class="brand" href="/" aria-label="Página inicial">
      <svg width="32" height="32" viewBox="0 0 32 32" fill="none" aria-hidden="true">
        <rect width="32" height="32" rx="8" fill="url(#g1)"/>
        <path d="M16 8L22 12V20L16 24L10 20V12L16 8Z" stroke="white" stroke-width="2" fill="none"/>
        <defs>
          <linearGradient id="g1" x1="0" y1="0" x2="32" y2="32">
            <stop offset="0%" stop-color="#3b82f6"/>
            <stop offset="100%" stop-color="#1d4ed8"/>
          </linearGradient>
        </defs>
      </svg>
      <span>CyberCore</span>
    </a>
    <nav class="nav" aria-label="Principal" data-nav>
      <a href="/hosting.php">Alojamento Web</a>
      <a href="/solutions.php">Soluções Digitais</a>
      <a href="/pricing.php">Preços</a>
      <a href="/contact.php">Contacto</a>
    </nav>
    <div class="header-actions">
      <a class="btn outline" href="/manager/register.php">Criar Conta</a>
      <a class="btn primary" href="/manager/login.php">Entrar no Painel</a>
      <button class="menu-toggle" data-menu-toggle aria-label="Abrir menu">☰</button>
    </div>
  </div>
</header>
<main>
