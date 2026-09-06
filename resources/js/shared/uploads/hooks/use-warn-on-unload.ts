import { useEffect } from 'react';

/**
 * Registers a browser beforeunload warning while `enabled` is true
 * (e.g. while uploads are in flight).
 */
export function useWarnOnUnload(enabled: boolean): void {
  useEffect(() => {
    if (!enabled) {
      return;
    }

    const onBeforeUnload = (event: BeforeUnloadEvent) => {
      event.preventDefault();
      event.returnValue = '';
    };

    window.addEventListener('beforeunload', onBeforeUnload);

    return () => {
      window.removeEventListener('beforeunload', onBeforeUnload);
    };
  }, [enabled]);
}
