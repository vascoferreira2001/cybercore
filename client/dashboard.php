<?php
$page_title = 'Dashboard | Área de Cliente';
$page_heading = 'Visão Geral';
$active_menu = 'dashboard';
require_once __DIR__ . '/includes/layout-top.php';
?>

<section class="grid-3">
  <div class="card metric">
    <div class="metric-label">Serviços ativos</div>
    <div class="metric-value">12</div>
    <div class="metric-sub">+2 desde o mês passado</div>
  </div>
  <div class="card metric">
    <div class="metric-label">Faturas em aberto</div>
    <div class="metric-value">2</div>
    <div class="metric-sub">Próximo vencimento em 5 dias</div>
  </div>
  <div class="card metric">
    <div class="metric-label">Tickets</div>
    <div class="metric-value">1</div>
    <div class="metric-sub">Tempo médio de resposta: 18m</div>
  </div>
</section>

<section class="panel">
  <div class="panel-head">
    <div>
      <p class="panel-kicker">Serviços</p>
      <h2>Ativos e recentes</h2>
    </div>
    <a class="btn btn-ghost" href="/client/services.php">Ver todos</a>
  </div>
  <div class="table-responsive">
    <table class="table">
      <thead>
        <tr>
          <th>Serviço</th>
          <th>Plano</th>
          <th>Status</th>
          <th>Próximo vencimento</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        <tr>
          <td>alojamento123.pt</td>
          <td>Business</td>
          <td><span class="badge badge-success">Ativo</span></td>
          <td>15 Jan 2026</td>
          <td><a class="link" href="/client/services.php">Gerir</a></td>
        </tr>
        <tr>
          <td>vps-core-02</td>
          <td>VPS Pro</td>
          <td><span class="badge badge-warning">A atualizar</span></td>
          <td>03 Jan 2026</td>
          <td><a class="link" href="/client/services.php">Gerir</a></td>
        </tr>
        <tr>
          <td>lojaonline.pt</td>
          <td>Starter</td>
          <td><span class="badge badge-success">Ativo</span></td>
          <td>28 Dec 2025</td>
          <td><a class="link" href="/client/services.php">Gerir</a></td>
        </tr>
      </tbody>
    </table>
  </div>
</section>

<section class="panel">
  <div class="panel-head">
    <div>
      <p class="panel-kicker">Suporte</p>
      <h2>Tickets recentes</h2>
    </div>
    <a class="btn btn-primary" href="/client/tickets.php">Abrir ticket</a>
  </div>
  <div class="list">
    <div class="list-item">
      <div>
        <p class="list-title">Erro 500 após deploy</p>
        <p class="list-meta">#1843 • Última resposta há 2h</p>
      </div>
      <span class="badge badge-warning">Em progresso</span>
    </div>
    <div class="list-item">
      <div>
        <p class="list-title">Migração de alojamento</p>
        <p class="list-meta">#1839 • Última resposta há 6h</p>
      </div>
      <span class="badge badge-success">Resolvido</span>
    </div>
    <div class="list-item">
      <div>
        <p class="list-title">DNS para domínio novo</p>
        <p class="list-meta">#1835 • Última resposta há 1d</p>
      </div>
      <span class="badge badge-info">Aberto</span>
    </div>
  </div>
</section>

<?php require_once __DIR__ . '/includes/layout-bottom.php'; ?>
