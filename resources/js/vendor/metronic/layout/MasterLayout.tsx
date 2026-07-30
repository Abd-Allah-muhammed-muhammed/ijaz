/**
 * Compatibility shim — Admin shell lives at @/apps/admin/layouts/AdminLayout.
 * Existing pages import MasterLayout from this path; keep that contract stable.
 */
export { default } from '@/apps/admin/layouts/AdminLayout';
