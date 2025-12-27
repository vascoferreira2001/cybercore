<?php
$page_title = 'Meus Serviços | Área de Cliente';
$page_heading = 'Meus Serviços';
$active_menu = 'services';
require_once __DIR__ . '/includes/layout-top.php';
?>

<section class="panel">
  <div class="panel-head">
    <div>
      <p class="panel-kicker">Serviços</p>
      <h2>Todos os serviços</h2>
    </div>
    <div class="panel-actions">
      <a class="btn btn-ghost" href="#">Exportar</a>
      <a class="btn btn-primary" href="#">Adicionar serviço</a>
    </div>
  </div>

  <div class="table-responsive">
    <table class="table">
      <thead>
        <tr>
          <th>Serviço</th>
          <th>Plano</th>
          <th>Status</th>
          <th>Criação</th>
          <th>Próximo vencimento</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        <tr>
          <td>alojamento123.pt</td>
          <td>Business</td>
          <td><span class="badge badge-success">Ativo</span></td>
          <td>15 Jan 2025</td>
          <td>15 Jan 2026</td>
          <td><a class="link" href="#">Gerir</a></td>
        </tr>
        <tr>
          <td>vps-core-02</td>
          <td>VPS Pro</td>
          <td><span class="badge badge-warning">A atualizar</span></td>
          <td>03 Jul 2025</td>
          <td>03 Jan 2026</td>
          <td><a class="link" href="#">Gerir</a></td>
        </tr>
        <tr>
          <td>lojaonline.pt</td>
          <td>Starter</td>
          <td><span class="badge badge-success">Ativo</span></td>
          <td>28 Dec 2024</td>
          <td>28 Dec 2025</td>
          <td><a class="link" href="#">Gerir</a></td>
        </tr>
        <tr>
          <td>api-core.app</td>
          <td>Cloud Standard</td>
          <td><span class="badge badge-info">Pendente</span></td>
          <td>20 Dec 2025</td>
          <td>20 Jan 2026</td>
          <td><a class="link" href="#">Gerir</a></td>
        </tr>
      </tbody>
    </table>
  </div>
</section>

<?php require_once __DIR__ . '/includes/layout-bottom.php'; ?>
