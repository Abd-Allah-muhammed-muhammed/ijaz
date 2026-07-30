import {
  createContext,
  useCallback,
  useContext,
  useEffect,
  useMemo,
  useState,
  type ReactNode,
} from 'react';

const COLLAPSED_KEY = 'ijaz_admin_sidebar_collapsed';

type AdminShellContextValue = {
  collapsed: boolean;
  mobileOpen: boolean;
  toggleCollapsed: () => void;
  setCollapsed: (value: boolean) => void;
  setMobileOpen: (value: boolean) => void;
};

const AdminShellContext = createContext<AdminShellContextValue | null>(null);

export function AdminShellProvider({ children }: { children: ReactNode }) {
  const [collapsed, setCollapsedState] = useState(() => {
    if (typeof window === 'undefined') {
      return false;
    }

    return localStorage.getItem(COLLAPSED_KEY) === '1';
  });
  const [mobileOpen, setMobileOpen] = useState(false);

  const setCollapsed = useCallback((value: boolean) => {
    setCollapsedState(value);
    localStorage.setItem(COLLAPSED_KEY, value ? '1' : '0');
  }, []);

  const toggleCollapsed = useCallback(() => {
    setCollapsed(!collapsed);
  }, [collapsed, setCollapsed]);

  useEffect(() => {
    const onResize = () => {
      if (window.innerWidth >= 1024) {
        setMobileOpen(false);
      }
    };

    window.addEventListener('resize', onResize);

    return () => window.removeEventListener('resize', onResize);
  }, []);

  const value = useMemo(
    () => ({
      collapsed,
      mobileOpen,
      toggleCollapsed,
      setCollapsed,
      setMobileOpen,
    }),
    [collapsed, mobileOpen, toggleCollapsed, setCollapsed],
  );

  return <AdminShellContext.Provider value={value}>{children}</AdminShellContext.Provider>;
}

export function useAdminShell(): AdminShellContextValue {
  const ctx = useContext(AdminShellContext);

  if (!ctx) {
    throw new Error('useAdminShell must be used within AdminShellProvider');
  }

  return ctx;
}
