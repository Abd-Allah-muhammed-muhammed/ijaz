/** Page-title / sidebar label key that must not collide with nested orders.* / orders.php tree. */
export const ORDERS_PAGE_TITLE_KEY = 'orders_label' as const;

/** Known call sites that must use ORDERS_PAGE_TITLE_KEY (or t('orders_label')), not bare t('orders'). */
export const ORDERS_LABEL_CALL_SITE_FILES = [
  'resources/js/apps/provider/layouts/sidebar/sidebar-menu/SidebarMenuMain.tsx',
  'resources/js/apps/provider/pages/Orders/Index.tsx',
  'resources/js/apps/provider/pages/Orders/Show.tsx',
  'resources/js/vendor/metronic/layout/components/sidebar/sidebar-menu/SidebarMenuMain.tsx',
  'resources/js/apps/admin/pages/Orders/Index.tsx',
  'resources/js/apps/admin/pages/Home.tsx',
  'resources/js/apps/admin/pages/components/DashboardCharts.tsx',
  'resources/js/shared/components/User/UserCard.tsx',
  'resources/js/shared/components/provider/ProviderCard.tsx',
] as const;

/** Nested dispute-UI keys that must keep working after the flat-label rename. */
export const ORDERS_NESTED_DISPUTE_KEYS = [
  'orders.dispute',
  'orders.no_dispute',
  'orders.dispute_opened',
  'orders.dispute_opened_reason',
  'orders.no_dispute_reason',
  'orders.system',
  'orders.dispute_resolved',
  'orders.dispute_outcome',
  'orders.awaiting_admin_resolution',
  'orders.awaiting_admin_resolution_hint',
  'orders.resolve_dispute',
  'orders.resolve_dispute_confirm',
  'orders.user_percentage',
  'orders.invalid_user_percentage',
  'orders.split_preview',
  'orders.dispute_resolution.full_user',
  'orders.dispute_resolution.full_provider',
  'orders.dispute_resolution.percentage_split',
  'orders.dispute_resolution.escalate',
] as const;
