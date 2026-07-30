import { SidebarMenuMain } from '@/shared/components/Sidebar/SidebarMenuMain';

export function SidebarMenu() {
  return (
    <nav
      aria-label="Admin"
      className="flex min-h-0 flex-1 flex-col overflow-y-auto overflow-x-hidden py-3"
    >
      <div className="flex flex-col gap-0.5">
        <SidebarMenuMain />
      </div>
    </nav>
  );
}
