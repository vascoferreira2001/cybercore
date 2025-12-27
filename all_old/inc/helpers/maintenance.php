<?php
// Helper para renderizar o modal de manutenção de forma padronizada
// Uso:
//   require_once __DIR__ . '/inc/helpers/maintenance.php';
//   if ($maintenanceDisabled) {
//     renderMaintenanceModal($maintenanceMessage ?: 'Mensagem padrão...', ['disable_form' => true]);
//   }

function renderMaintenanceModal(string $message, array $options = []): void {
  $closeText   = isset($options['close_text']) ? (string)$options['close_text'] : 'Fechar aviso';
  $disableForm = !empty($options['disable_form']);
  $overlayId   = isset($options['overlay_id']) ? (string)$options['overlay_id'] : 'maintenanceOverlay';

  echo '<div class="maintenance-overlay" id="' . htmlspecialchars($overlayId) . '">';
  echo '  <div class="maintenance-modal">';
  echo '    <strong>Modo de Manutenção</strong>';
  echo '    <p class="maintenance-text">' . htmlspecialchars($message) . '</p>';
  echo '    <button type="button" class="btn maintenance-close" onclick="document.getElementById(\'' . htmlspecialchars($overlayId) . '\').style.display=\'none\';">' . htmlspecialchars($closeText) . '</button>';
  echo '  </div>';
  echo '</div>';

  if ($disableForm) {
    echo '<script>document.addEventListener(\'DOMContentLoaded\', function(){';
    echo '  document.querySelectorAll(\'form input, form button, form select, form textarea\').forEach(function(el){ el.setAttribute(\'disabled\',\'disabled\'); });';
    echo '});</script>';
  }
}
