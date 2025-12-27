<?php
$page_title = 'Faturas | Área de Cliente';
$page_heading = 'Faturas';
$active_menu = 'invoices';
require_once __DIR__ . '/includes/layout-top.php';
?>

<section class="panel">
  <div class="panel-head">
    <div>
      <p class="panel-kicker">Faturação</p>
      <h2>Resumo de faturas</h2>
    </div>
    <div class="panel-actions">
      <a class="btn btn-ghost" href="#">Exportar PDF</a>
      <a class="btn btn-primary" href="#">Pagar tudo</a>
    </div>
  </div>

  <div class="table-responsive">
    <table class="table">
      <thead>
        <tr>
          <th>#</th>
          <th>Referência</th>
          <th>Data</th>
          <th>Valor</th>
          <th>Status</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        <tr>
          <td>2025-1045</td>
          <td>FAT-1045</td>
          <td>12 Dec 2025</td>
          <td>29,99 €</td>
          <td><span class="badge badge-warning">Em aberto</span></td>
          <td class="table-actions"><a class="link" href="#">Ver</a><a class="link" href="#">Pagar</a></td>
        </tr>
        <tr>
          <td>2025-1032</td>
          <td>FAT-1032</td>
          <td>28 Nov 2025</td>
          <td>9,99 €</td>
          <td><span class="badge badge-success">Pago</span></td>
          <td class="table-actions"><a class="link" href="#">Ver</a><a class="link" href="#">Recibo</a></td>
        </tr>
        <tr>
          <td>2025-1028</td>
          <td>FAT-1028</td>
          <td>15 Nov 2025</td>
          <td>19,99 €</td>
          <td><span class="badge badge-error">Vencido</span></td>
          <td class="table-actions"><a class="link" href="#">Ver</a><a class="link" href="#">Pagar</a></td>
        </tr>
      </tbody>
    </table>
  </div>
</section>

<?php require_once __DIR__ . '/includes/layout-bottom.php'; ?>
