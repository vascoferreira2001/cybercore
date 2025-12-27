<?php
$page_title = 'Tickets de Suporte | Área de Cliente';
$page_heading = 'Tickets de Suporte';
$active_menu = 'tickets';
require_once __DIR__ . '/includes/layout-top.php';
?>

<section class="panel">
  <div class="panel-head">
    <div>
      <p class="panel-kicker">Suporte</p>
      <h2>Tickets recentes</h2>
    </div>
    <div class="panel-actions">
      <a class="btn btn-ghost" href="#">Filtros</a>
      <a class="btn btn-primary" href="#">Abrir novo ticket</a>
    </div>
  </div>

  <div class="list list-divided">
    <div class="list-item">
      <div>
        <p class="list-title">Erro 500 após deploy</p>
        <p class="list-meta">#1843 • Última resposta há 2h • Prioridade Alta</p>
      </div>
      <span class="badge badge-warning">Em progresso</span>
    </div>
    <div class="list-item">
      <div>
        <p class="list-title">Configuração de SSL</p>
        <p class="list-meta">#1841 • Última resposta há 4h • Prioridade Normal</p>
      </div>
      <span class="badge badge-info">Aberto</span>
    </div>
    <div class="list-item">
      <div>
        <p class="list-title">Migração de alojamento</p>
        <p class="list-meta">#1839 • Respondido há 6h • Prioridade Alta</p>
      </div>
      <span class="badge badge-success">Resolvido</span>
    </div>
    <div class="list-item">
      <div>
        <p class="list-title">DNS para domínio novo</p>
        <p class="list-meta">#1835 • Respondido há 1d • Prioridade Baixa</p>
      </div>
      <span class="badge badge-info">Aberto</span>
    </div>
  </div>
</section>

<?php require_once __DIR__ . '/includes/layout-bottom.php'; ?>
