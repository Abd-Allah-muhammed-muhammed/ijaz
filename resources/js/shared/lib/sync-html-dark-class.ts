/**
 * Keep Tailwind's `.dark` class in sync with Metronic/Bootstrap `data-bs-theme`.
 * Metronic owns the moon/sun toggle; Tailwind tokens key off `.dark` on <html>.
 */
export function syncHtmlDarkClass(mode: 'light' | 'dark' | string): void {
  if (typeof document === 'undefined') {
    return;
  }

  const root = document.documentElement;

  if (mode === 'dark') {
    root.classList.add('dark');
  } else {
    root.classList.remove('dark');
  }
}
