# Permissões (fonte única)

Baseado em roles definidas em `inc/menu_config.php` (mapa `getRolePermissions`). As páginas consultam `inc/permissions.php`.

## API a usar
- `userHasPermission('can_manage_expenses')`
- `userHasAnyPermission(['can_manage_expenses', 'can_manage_fiscal_approvals'])`
- `userHasAllPermissions([...])`
- `requirePermission('can_manage_expenses')`
- `requireAnyPermission([...])`
- `requireAllPermissions([...])`

## Como aplicar numa página
1) Garantir role permitido: `checkRole(['Gestor', 'Suporte Financeiro']);`
2) Reforçar permissão: `requirePermission('can_manage_expenses');`
3) Usar `userHasPermission` para mostrar/ocultar ações.

## Como adicionar nova permissão
1) Editar `getRolePermissions()` em `inc/menu_config.php` e adicionar o flag ao(s) roles.
2) Usar `requirePermission('<novo_flag>')` nas páginas que precisem.
3) Opcional: adicionar entrada de menu em `getMenuItemsByRole` se for uma nova rota.

## Convenções
- Prefixos: `can_view_*`, `can_manage_*`, `is_*` (booleano).
- Gestor herda tudo (fallback em `permissions.php`).
- Evitar permissões dependentes de departamento; usar apenas roles.
