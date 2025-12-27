<?php
/**
 * CyberCore Menu Configuration
 * Define menu items por cargo/role
 */

/**
 * Retorna os itens de menu baseado no cargo do usuário
 * @param string $role - Cargo do usuário (Cliente, Gestor, Suporte ao Cliente, etc.)
 * @return array - Array de itens de menu
 */
function getMenuItemsByRole($role) {
    // Menu base para todos os roles
    $baseMenu = [
        [
            'url' => '/manager/dashboard.php',
            'label' => 'Dashboard',
            'icon' => 'dashboard',
            'key' => 'dashboard',
            'roles' => ['Cliente', 'Gestor', 'Suporte ao Cliente', 'Suporte Técnico', 'Suporte Financeiro']
        ]
    ];

    // Menu do Cliente
    $clientMenu = [
        [
            'url' => '/manager/services.php',
            'label' => 'Serviços',
            'icon' => 'package',
            'key' => 'services',
            'roles' => ['Cliente', 'Gestor', 'Suporte ao Cliente', 'Suporte Técnico']
        ],
        [
            'url' => '/manager/domains.php',
            'label' => 'Domínios',
            'icon' => 'globe',
            'key' => 'domains',
            'roles' => ['Cliente', 'Gestor', 'Suporte ao Cliente', 'Suporte Técnico']
        ],
        [
            'url' => '/manager/hosting.php',
            'label' => 'Alojamento',
            'icon' => 'server',
            'key' => 'hosting',
            'roles' => ['Cliente', 'Gestor', 'Suporte ao Cliente', 'Suporte Técnico']
        ],
        [
            'url' => '/manager/finance.php',
            'label' => 'Faturação',
            'icon' => 'credit-card',
            'key' => 'finance',
            'roles' => ['Cliente', 'Gestor', 'Suporte Financeiro']
        ],
        [
            'url' => '/manager/support.php',
            'label' => 'Suporte',
            'icon' => 'message-circle',
            'key' => 'support',
            'roles' => ['Cliente', 'Gestor', 'Suporte ao Cliente', 'Suporte Técnico', 'Suporte Financeiro']
        ]
    ];

    // Menu administrativo (apenas Gestor e Suporte)
    $adminMenu = [
        [
            'type' => 'divider',
            'label' => 'Administração',
            'roles' => ['Gestor', 'Suporte ao Cliente', 'Suporte Técnico', 'Suporte Financeiro']
        ],
        [
            'url' => '/manager/admin/customers.php',
            'label' => 'Clientes',
            'icon' => 'users',
            'key' => 'customers',
            'roles' => ['Gestor', 'Suporte ao Cliente']
        ],
        [
            'url' => '/manager/admin/services.php',
            'label' => 'Gestão de Serviços',
            'icon' => 'package',
            'key' => 'admin-services',
            'roles' => ['Gestor', 'Suporte Técnico']
        ],
        [
            'url' => '/manager/admin/payments.php',
            'label' => 'Pagamentos',
            'icon' => 'dollar-sign',
            'key' => 'payments',
            'roles' => ['Gestor', 'Suporte Financeiro']
        ],
        [
            'url' => '/manager/admin/payment-warnings.php',
            'label' => 'Avisos de Pagamento',
            'icon' => 'alert-circle',
            'key' => 'payment-warnings',
            'roles' => ['Gestor', 'Suporte Financeiro']
        ],
        [
            'url' => '/manager/admin/fiscal-approvals.php',
            'label' => 'Aprovações Fiscais',
            'icon' => 'check-square',
            'key' => 'fiscal-approvals',
            'roles' => ['Gestor', 'Suporte Financeiro']
        ],
        [
            'url' => '/manager/admin/hosting.php',
            'label' => 'Alojamentos Web',
            'icon' => 'globe',
            'key' => 'hosting',
            'roles' => ['Gestor', 'Suporte Técnico']
        ],
        [
            'url' => '/manager/admin/servers.php',
            'label' => 'Servidores',
            'icon' => 'server',
            'key' => 'servers',
            'roles' => ['Gestor', 'Suporte Técnico']
        ],
        [
            'url' => '/manager/admin/tickets.php',
            'label' => 'Bilhetes de Suporte',
            'icon' => 'message-square',
            'key' => 'tickets',
            'roles' => ['Gestor', 'Suporte ao Cliente', 'Suporte Técnico', 'Suporte Financeiro']
        ],
        [
            'url' => '/manager/admin/documents.php',
            'label' => 'Documentos da Equipa',
            'icon' => 'file-text',
            'key' => 'documents',
            'roles' => ['Gestor', 'Suporte Técnico']
        ],
        [
            'url' => '/manager/admin/reports.php',
            'label' => 'Relatórios',
            'icon' => 'bar-chart',
            'key' => 'reports',
            'roles' => ['Gestor']
        ],
        [
            'url' => '/manager/admin/team.php',
            'label' => 'Equipa',
            'icon' => 'users',
            'key' => 'team',
            'roles' => ['Gestor']
        ],
        [
            'url' => '/manager/admin/settings.php',
            'label' => 'Configurações',
            'icon' => 'settings',
            'key' => 'settings',
            'roles' => ['Gestor']
        ]
    ];

    // Menu de logs e atualizações
    $systemMenu = [
        [
            'url' => '/manager/logs.php',
            'label' => 'Logs',
            'icon' => 'list',
            'key' => 'logs',
            'roles' => ['Gestor', 'Suporte ao Cliente', 'Suporte Técnico', 'Suporte Financeiro']
        ],
        [
            'url' => '/manager/updates.php',
            'label' => 'Atualizações',
            'icon' => 'upload',
            'key' => 'updates',
            'roles' => ['Cliente', 'Gestor', 'Suporte ao Cliente', 'Suporte Técnico', 'Suporte Financeiro']
        ]
    ];

    // Combinar todos os menus
    $allMenuItems = array_merge($baseMenu, $clientMenu, $adminMenu, $systemMenu);

    // Filtrar itens baseado no role
    $filteredMenu = array_filter($allMenuItems, function($item) use ($role) {
        return isset($item['roles']) && in_array($role, $item['roles']);
    });

    return array_values($filteredMenu);
}

/**
 * Retorna o ícone SVG para um item de menu
 * @param string $iconName - Nome do ícone
 * @return string - SVG HTML
 */
function getMenuIcon($iconName) {
    $icons = [
        'dashboard' => '<rect x="3" y="3" width="7" height="7"></rect><rect x="14" y="3" width="7" height="7"></rect><rect x="14" y="14" width="7" height="7"></rect><rect x="3" y="14" width="7" height="7"></rect>',
        'package' => '<path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path>',
        'globe' => '<circle cx="12" cy="12" r="10"></circle><line x1="2" y1="12" x2="22" y2="12"></line><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"></path>',
        'server' => '<rect x="2" y="2" width="20" height="8" rx="2" ry="2"></rect><rect x="2" y="14" width="20" height="8" rx="2" ry="2"></rect><line x1="6" y1="6" x2="6.01" y2="6"></line><line x1="6" y1="18" x2="6.01" y2="18"></line>',
        'credit-card' => '<rect x="2" y="5" width="20" height="14" rx="2"></rect><line x1="2" y1="10" x2="22" y2="10"></line>',
        'message-circle' => '<path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>',
        'users' => '<path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path>',
        'dollar-sign' => '<line x1="12" y1="1" x2="12" y2="23"></line><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>',
        'message-square' => '<path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>',
        'bar-chart' => '<line x1="12" y1="20" x2="12" y2="10"></line><line x1="18" y1="20" x2="18" y2="4"></line><line x1="6" y1="20" x2="6" y2="16"></line>',
        'settings' => '<circle cx="12" cy="12" r="3"></circle><path d="M12 1v6m0 6v6m5.66-13v.01M18.36 5.64v.01M21 12h-6m-6 0H3m13.66 5.66v.01M18.36 18.36v.01M12 21v-6"></path>',
        'list' => '<line x1="8" y1="6" x2="21" y2="6"></line><line x1="8" y1="12" x2="21" y2="12"></line><line x1="8" y1="18" x2="21" y2="18"></line><line x1="3" y1="6" x2="3.01" y2="6"></line><line x1="3" y1="12" x2="3.01" y2="12"></line><line x1="3" y1="18" x2="3.01" y2="18"></line>',
        'upload' => '<path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="17 8 12 3 7 8"></polyline><line x1="12" y1="3" x2="12" y2="15"></line>',
            'alert-circle' => '<circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line>',
            'file-text' => '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="12" y1="13" x2="18" y2="13"></line><line x1="12" y1="17" x2="18" y2="17"></line><line x1="12" y1="9" x2="18" y2="9"></line>',
        'logout' => '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line>'
    ];

    return $icons[$iconName] ?? $icons['dashboard'];
}

/**
 * Verifica se o usuário tem acesso a determinada rota
 * @param string $url - URL da rota
 * @param string $role - Cargo do usuário
 * @return bool
 */
function hasRouteAccess($url, $role) {
    $menuItems = getMenuItemsByRole($role);
    foreach ($menuItems as $item) {
        if (isset($item['url']) && $item['url'] === $url) {
            return true;
        }
    }
    return false;
}

/**
 * Retorna informações de permissões por cargo
 * @param string $role
 * @return array
 */
function getRolePermissions($role) {
    $permissions = [
        'Cliente' => [
            'can_view_own_services' => true,
            'can_view_own_invoices' => true,
            'can_create_tickets' => true,
            'can_view_own_tickets' => true,
            'can_manage_profile' => true,
            'can_view_all_clients' => false,
            'can_manage_users' => false,
            'can_view_reports' => false,
            'can_access_admin' => false
        ],
        'Suporte ao Cliente' => [
            'can_view_own_services' => true,
            'can_view_own_invoices' => true,
            'can_create_tickets' => true,
            'can_view_own_tickets' => true,
            'can_view_all_tickets' => true,
            'can_manage_tickets' => true,
            'can_view_all_clients' => true,
            'can_manage_profile' => true,
            'can_view_reports' => false,
            'can_access_admin' => true
        ],
        'Suporte Técnico' => [
            'can_view_own_services' => true,
            'can_view_own_invoices' => true,
            'can_create_tickets' => true,
            'can_view_own_tickets' => true,
            'can_view_all_tickets' => true,
            'can_manage_tickets' => true,
            'can_manage_services' => true,
            'can_manage_domains' => true,
            'can_manage_hosting' => true,
            'can_manage_profile' => true,
            'can_view_reports' => false,
            'can_access_admin' => true
        ],
        'Suporte Financeiro' => [
            'can_view_own_services' => true,
            'can_view_own_invoices' => true,
            'can_view_all_invoices' => true,
            'can_manage_invoices' => true,
            'can_view_all_payments' => true,
            'can_manage_payments' => true,
            'can_manage_expenses' => true,
            'can_manage_fiscal_approvals' => true,
            'can_create_tickets' => true,
            'can_view_own_tickets' => true,
            'can_view_all_tickets' => true,
            'can_manage_profile' => true,
            'can_view_reports' => false,
            'can_access_admin' => true
        ],
        'Gestor' => [
            'can_view_own_services' => true,
            'can_view_own_invoices' => true,
            'can_view_all_services' => true,
            'can_manage_services' => true,
            'can_view_all_invoices' => true,
            'can_manage_invoices' => true,
            'can_view_all_payments' => true,
            'can_manage_payments' => true,
            'can_manage_expenses' => true,
            'can_manage_fiscal_approvals' => true,
            'can_view_all_tickets' => true,
            'can_manage_tickets' => true,
            'can_view_all_clients' => true,
            'can_manage_users' => true,
            'can_manage_team' => true,
            'can_view_reports' => true,
            'can_manage_settings' => true,
            'can_view_logs' => true,
            'can_manage_profile' => true,
            'can_access_admin' => true,
            'is_super_admin' => true
        ]
    ];

    return $permissions[$role] ?? $permissions['Cliente'];
}

/**
 * Verifica se usuário tem uma permissão específica
 * @param string $role - Cargo do usuário
 * @param string $permission - Nome da permissão
 * @return bool
 */
function roleHasPermission($role, $permission) {
    $rolePermissions = getRolePermissions($role);
    return isset($rolePermissions[$permission]) && $rolePermissions[$permission] === true;
}
