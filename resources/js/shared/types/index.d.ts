import {AuthenticatedUser, Model} from "@/shared/types/models";

export interface Auth {
  user: AuthenticatedUser;
  permissions: string[];
}

export interface BreadcrumbItem {
  title: string;
  href: string;
}

export interface NavGroup {
  title: string;
  items: NavItem[];
}

export interface NavItem {
  title: string;
  href: string;
  icon?: string | null;
  isActive?: boolean;
}

type message = {
  id: string;
  content: string;
}

export interface SharedData {
  name: string;
  auth: Auth;
  sidebarOpen: boolean;
  app: {
    locale: string;
    /** Frontend shell identifier (admin / provider / marketer / web) */
    shell: 'admin' | 'provider' | 'marketer' | 'web';
  }
  flash: {
    success: message | null;
    error: message | null;
  }
  payment: {
    /** Active PAYMENT_DRIVER value (paytabs | rajhi | testing) */
    driver: string;
    /** False only when driver is empty/unrecognized; Testing is a valid online gateway */
    online_enabled: boolean;
  }
}

export interface PaginationResource<T extends typeof Model> {
  data: T[];
  links: {
    first: string;
    last: string;
    prev: string | null;
    next: string | null;
  };
  meta: {
    current_page: number;
    from: number | null;
    last_page: number;
    path: string;
    per_page: number;
    to: number | null;
    total: number;
  };
}

export type { AuthenticatedUser, Model };
