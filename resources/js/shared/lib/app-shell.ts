import type { SharedData } from '@/shared/types';
import { router } from '@inertiajs/react';

export type AppShell = SharedData['app']['shell'];

export function applyAppShell(shell: AppShell | undefined): void {
  if (!shell || typeof document === 'undefined') {
    return;
  }

  document.documentElement.dataset.app = shell;
}

/**
 * Blade sets the initial `data-app` for first paint. This keeps it in sync
 * across Inertia client navigations (e.g. rare cross-shell links).
 */
export function initializeAppShell(initialShell: AppShell | undefined): void {
  applyAppShell(initialShell);

  router.on('navigate', (event) => {
    const shell = (event.detail.page.props as SharedData).app?.shell;
    applyAppShell(shell);
  });
}
