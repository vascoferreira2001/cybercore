<?php
require_once __DIR__ . '/settings.php';
require_once __DIR__ . '/config.php';

function loadAuthTheme(PDO $pdo): array {
  $siteLogo = getSetting($pdo, 'site_logo');
  $loginBg = getSetting($pdo, 'login_background');
  $logoUrl = ($siteLogo && function_exists('getAssetPath') && getAssetPath($siteLogo) && file_exists(getAssetPath($siteLogo)))
    ? htmlspecialchars(getAssetUrl($siteLogo))
    : '';
  $bgUrl = ($loginBg && function_exists('getAssetPath') && getAssetPath($loginBg) && file_exists(getAssetPath($loginBg)))
    ? htmlspecialchars(getAssetUrl($loginBg))
    : '';

  $backgroundStyle = $bgUrl ? 'background: url(' . $bgUrl . ') center/cover no-repeat fixed, #0f172a;' : '';

  return [
    'logoUrl' => $logoUrl,
    'backgroundStyle' => $backgroundStyle,
  ];
}

function renderAuthLogo(string $logoUrl): void {
  if ($logoUrl) {
    echo '<img src="' . $logoUrl . '?v=' . time() . '" alt="Logo" style="height:40px;width:auto;">';
  } else {
    echo '<svg viewBox="0 0 200 50" fill="none" xmlns="http://www.w3.org/2000/svg"><text x="10" y="35" font-family="Manrope, sans-serif" font-size="28" font-weight="700" fill="#fff">CyberCore</text></svg>';
  }
}
