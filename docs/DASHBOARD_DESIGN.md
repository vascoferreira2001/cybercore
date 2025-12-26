# CyberCore Dashboard Design

Last updated: 2025-12-26

## Goals
- Consistent dashboard layout across all roles (Gestor, Suporte, Cliente).
- Left sidebar navigation + top bar with search, notifications, and user/company chip.
- Clean, light theme aligned with reference image.

## Structure
- Wrapper: `.dashboard-app` splits sidebar and content.
- Sidebar: `.dashboard-sidebar` contains brand, user info, and `.sidebar-nav` menu.
- Top bar: `.dashboard-topbar` inside `.dashboard-content` provides:
  - Search: `.topbar-search` (input + submit icon button)
  - Notifications: `.icon-btn.bell` with `.badge` count
  - User chip: `.user-chip` with `.avatar` and `.name`
- Content: `.dashboard-content` wraps page-specific panels, cards, metrics.

## CSS Source
All dashboard styles live in a single file: assets/css/design-system.css
Key sections:
- CSS variables and typography
- Components: `.card`, `.btn`, tables, utilities
- Dashboard-specific: `.dashboard-*`, `.metrics-grid`, `.metric-card`, `.actions`, `.activity-list`

## Components
- Sidebar items: `.nav-item` and `.nav-item-sub` with hover and active states; groups use `.nav-group` with `.submenu`.
- Top bar controls: `.icon-btn` supports `.badge` for counts; `.user-chip` shows initial or photo fallback.
- Metrics grid: `.metrics-grid` > `.metric-card` with `.metric-title` and `.metric-value`.
- Quick actions: `.actions` grid with `.action-btn` and `.action-btn.primary`.
- Activity list: `.activity-list` > `.activity-item` with `.meta` timestamp.

## Data & Behavior
- Notifications count derived server-side per role (tickets, invoices).
- Search submits to `/search.php` using `q` parameter.
- Display name chooses company when available, else first+last name.

## Usage
- Single dashboard entry: `/dashboard.php` for all roles. Admin route `/admin/dashboard.php` redirects to the main dashboard.
- Enable dashboard layout on pages that should use the shell by defining `DASHBOARD_LAYOUT` before including `inc/header.php`.

```php
<?php define('DASHBOARD_LAYOUT', true); ?>
<?php include __DIR__ . '/inc/header.php'; ?>
... page content ...
<?php include __DIR__ . '/inc/footer.php'; ?>
```

## Accessibility
- Interactive elements use buttons and proper labels.
- Sufficient color contrast for text and icons.
- Keyboard focus visible on inputs and interactive controls.

## Future Enhancements
- Active menu indicators, collapsible groups persisted with localStorage.
- Notifications dropdown panel.
- Search result categories and deep links.
 - Role/permission-driven card visibility from a central config.
