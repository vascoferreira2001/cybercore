# Navegação

## Fonte única
- `inc/menu_config.php` define:
  - `getMenuItemsByRole($role)` – itens do menu lateral por role.
  - `getRolePermissions($role)` – flags de permissão por role.
  - `getMenuIcon($icon)` – ícones SVG inline.

## Como adicionar um item
1) Editar `getMenuItemsByRole` e adicionar o item com `url`, `label`, `icon`, `key`, `roles`.
2) Certificar que a permissão existe (se necessário) em `getRolePermissions`.
3) Passar o `key` como `$sidebarActive` ao chamar `renderDashboardLayout` para destacar o item.

## Boas práticas
- Usar `roles` para controlar visibilidade do item; não duplicar lógica noutras camadas.
- Manter `key` curto e único (ex.: `expenses`, `fiscal-approvals`).
- Se o item requer permissão extra, verificar na página com `requirePermission('<flag>')`.
