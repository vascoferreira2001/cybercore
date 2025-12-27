<?php
$page_title = 'Perfil | Área de Cliente';
$page_heading = 'Perfil e Segurança';
$active_menu = 'profile';
require_once __DIR__ . '/includes/layout-top.php';
?>

<section class="grid-2">
  <div class="panel">
    <div class="panel-head">
      <div>
        <p class="panel-kicker">Perfil</p>
        <h2>Dados pessoais</h2>
      </div>
    </div>
    <form method="POST" action="" class="form">
      <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token()); ?>">
      <div class="form-row">
        <div class="form-group">
          <label for="first_name">Nome</label>
          <input type="text" id="first_name" name="first_name" value="João" required>
        </div>
        <div class="form-group">
          <label for="last_name">Apelido</label>
          <input type="text" id="last_name" name="last_name" value="Silva" required>
        </div>
      </div>
      <div class="form-group">
        <label for="email">Email</label>
        <input type="email" id="email" name="email" value="joao@exemplo.pt" required>
        <small class="form-help">Usado para login e notificações</small>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label for="phone">Telefone</label>
          <input type="text" id="phone" name="phone" value="+351 910 000 000">
        </div>
        <div class="form-group">
          <label for="company">Empresa</label>
          <input type="text" id="company" name="company" value="Loja Online Lda">
        </div>
      </div>
      <button type="submit" class="btn btn-primary">Guardar alterações</button>
    </form>
  </div>

  <div class="panel">
    <div class="panel-head">
      <div>
        <p class="panel-kicker">Segurança</p>
        <h2>Password e sessões</h2>
      </div>
    </div>
    <form method="POST" action="" class="form">
      <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token()); ?>">
      <div class="form-group">
        <label for="current_password">Password atual</label>
        <input type="password" id="current_password" name="current_password" required autocomplete="current-password">
      </div>
      <div class="form-group">
        <label for="new_password">Nova password</label>
        <input type="password" id="new_password" name="new_password" required autocomplete="new-password" placeholder="Mínimo 8 caracteres">
      </div>
      <div class="form-group">
        <label for="new_password_confirm">Confirmar nova password</label>
        <input type="password" id="new_password_confirm" name="new_password_confirm" required autocomplete="new-password" placeholder="Repita a nova password">
      </div>
      <button type="submit" class="btn btn-primary">Atualizar password</button>
    </form>
  </div>
</section>

<section class="panel">
  <div class="panel-head">
    <div>
      <p class="panel-kicker">Sessões ativas</p>
      <h2>Gerir acessos</h2>
    </div>
    <a class="btn btn-ghost" href="#">Terminar todas</a>
  </div>
  <div class="list list-divided">
    <div class="list-item">
      <div>
        <p class="list-title">Safari • MacOS • Lisboa</p>
        <p class="list-meta">Ativa agora</p>
      </div>
      <span class="badge badge-success">Atual</span>
    </div>
    <div class="list-item">
      <div>
        <p class="list-title">Chrome • Windows • Porto</p>
        <p class="list-meta">Ativa há 2h</p>
      </div>
      <span class="badge badge-info">Ativa</span>
    </div>
    <div class="list-item">
      <div>
        <p class="list-title">Mobile • iOS • Coimbra</p>
        <p class="list-meta">Terminada há 1d</p>
      </div>
      <span class="badge badge-error">Terminada</span>
    </div>
  </div>
</section>

<?php require_once __DIR__ . '/includes/layout-bottom.php'; ?>
