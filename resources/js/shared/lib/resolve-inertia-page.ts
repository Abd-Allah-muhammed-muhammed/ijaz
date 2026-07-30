import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';

/**
 * Inertia page resolution for the apps/{admin,provider,web} layout.
 *
 * Backend still renders names like `Dashboard/Users/Index`, `Provider/Home`,
 * `Frontend/LandingPage`, `Errors/ErrorPage` — those strings are NOT changed.
 * Physical files live under apps/<shell>/pages (Inertia prefix removed). This
 * helper remaps Vite glob keys to virtual `./pages/{BackendName}.tsx` paths so
 * resolvePageComponent keeps working with `./pages/${name}.tsx`.
 *
 * This file lives at resources/js/shared/lib/, so globs use ../../ relative to it.
 */

type PageLoader = () => Promise<unknown>;
type PageGlob = Record<string, PageLoader>;

function remapGlob(glob: PageGlob, fromPrefix: string, toPrefix: string): PageGlob {
  const remapped: PageGlob = {};

  for (const [path, loader] of Object.entries(glob)) {
    remapped[path.replace(fromPrefix, toPrefix)] = loader;
  }

  return remapped;
}

function buildInertiaPages(): PageGlob {
  return {
    ...remapGlob(
      import.meta.glob('../../apps/web/pages/**/*.tsx') as PageGlob,
      '../../apps/web/pages/',
      './pages/Frontend/',
    ),
    ...remapGlob(
      import.meta.glob('../../apps/provider/pages/**/*.tsx') as PageGlob,
      '../../apps/provider/pages/',
      './pages/Provider/',
    ),
    ...remapGlob(
      import.meta.glob('../../apps/admin/pages/**/*.tsx') as PageGlob,
      '../../apps/admin/pages/',
      './pages/Dashboard/',
    ),
    ...remapGlob(
      import.meta.glob('../../shared/pages/Errors/**/*.tsx') as PageGlob,
      '../../shared/pages/Errors/',
      './pages/Errors/',
    ),
  };
}

const pages = buildInertiaPages();

export function resolveInertiaPage(name: string): Promise<unknown> {
  return resolvePageComponent(`./pages/${name}.tsx`, pages);
}
