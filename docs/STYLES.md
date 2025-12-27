# Estilos

## Ficheiros principais
- `assets/css/design-system.css` – tokens, cores, tipografia base.
- `assets/css/dashboard.css` – layout e componentes do dashboard (sidebar/topbar/cards).
- `assets/css/style.css` – site geral/páginas públicas.
- `assets/css/auth-modern.css` – páginas de autenticação.

## Boas práticas
- Adicionar variáveis e utilitários ao design-system antes de usar nos demais.
- Dashboard: preferir classes existentes (`dashboard-app`, `sidebar`, `topbar`, `card`).
- Manter gradientes e cores via CSS variables; evitar valores hardcoded repetidos.
- Responsividade: usar media queries já presentes em `dashboard.css` como modelo.

## Onde incluir
- Layout de dashboard já injeta `design-system.css` + `dashboard.css` (ver `inc/dashboard_helper.php`).
- Outras páginas podem incluir `design-system.css` + `style.css` conforme necessário.
