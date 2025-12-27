# Layouts

## Dashboard
- Arquitetura central em `inc/dashboard_helper.php`.
- Partials reutilizáveis:
  - `inc/layouts/sidebar.php` – constrói sidebar com itens de `getMenuItemsByRole`.
  - `inc/layouts/topbar.php` – topbar com pesquisa e perfil.
- Uso: numa página de dashboard, definir `DASHBOARD_LAYOUT = true`, chamar helpers (`checkRole`, `requirePermission`) e renderizar conteúdo via `renderDashboardLayout($title, $subtitle, $html, $activeKey)`.
- Sidebar ativa: passe `$activeKey` com a chave do item do menu (`key` em `menu_config.php`).

## Auth / Público
- Páginas de autenticação usam `auth-modern.css` + includes atuais.
- Páginas públicas usam `inc/header.php`/`inc/footer.php` e `style.css`.

## Convenções
- Evitar HTML duplicado de sidebar/topbar; usar as partials.
- Scripts específicos do dashboard devem ser carregados no final de `dashboard_helper.php` ou via `assets/js/...`.
