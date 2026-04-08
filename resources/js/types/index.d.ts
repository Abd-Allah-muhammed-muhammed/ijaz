import {LucideIcon} from 'lucide-react';
import {AuthenticatedUser, Model} from "@/types/models";

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
  icon?: LucideIcon | null;
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
  }
  flash: {
    success: message | null;
    error: message | null;
  }
}

export interface PaginationResource<T extends typeof Model> {
  data: T[];
  links: {
    first: string;
    last: string;
    prev: string;
    next: string;
  };
  meta: PaginationMeta;
}

export interface PaginationLink {
  active: boolean;
  label: string | TrustedHTML;
  url: string;
}

export interface PaginationMeta {
  current_page: number;
  last_page: number;
  per_page: number;
  total: number;
  from: number;
  to: number;
  path: string;
  links: PaginationLink[];
}
export type LocalesOptions = {
  name: string;
  script: string;
  regional: string;
  native: string;
  flag: string;
}
export type Locales = {
  [key: string]: LocalesOptions;
}

export  type SelectOption = {
  label: string;
  value: string;
};

export type ReactSelect = {
  label: string;
  value: number | string;
};

