// Tailwind v4 (theme + utilities only — preflight disabled; see resources/css/app.css)
import '../css/app.css';
// import './_metronic/assets/sass/style.react.scss'
// import './_metronic/assets/fonticon/fonticon.css'
import './_metronic/assets/keenicons/duotone/style.css';
import './_metronic/assets/keenicons/outline/style.css';
import './_metronic/assets/keenicons/solid/style.css';
// import './selects.css';
// import './_metronic/assets/sass/style.scss'
import { MasterInit } from '@/_metronic/layout/MasterInit';
import { ThemeModeProvider } from '@/_metronic/partials';
import { RecommendedOrdersProvider } from '@/store/recommend-orders-context';
import { ConversationProvider } from '@/store/use-chat';
import '@fortawesome/fontawesome-svg-core/styles.css';
import { createInertiaApp } from '@inertiajs/react';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { ReactQueryDevtools } from '@tanstack/react-query-devtools';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { Suspense } from 'react';
import { createRoot, hydrateRoot } from 'react-dom/client';
import { LayoutProvider, LayoutSplashScreen } from './_metronic/layout/core';
import './echo';
import { initializeTheme } from './hooks/use-appearance';
import './lang/i18next';
import I18nextEffect from './lang/I18next-effect';
import { setApiLocale } from './lib/api-client';
import { initializeAppShell } from './lib/app-shell';
import type { SharedData } from './types';

const appName = import.meta.env.VITE_APP_NAME || 'Ijaz';

const queryClient = new QueryClient();

createInertiaApp({
  title: (title) => `${title} - ${appName}`,
  resolve: (name) => resolvePageComponent(`./pages/${name}.tsx`, import.meta.glob('./pages/**/*.tsx')),
  setup({ el, App, props }) {
    const shared = props.initialPage.props as SharedData;
    const locale = shared.app?.locale || 'en';
    // Must set on the shared apiClient instance — query hooks do not use global axios.
    setApiLocale(locale);
    initializeAppShell(shared.app?.shell);

    const appElement = (
      <I18nextEffect locale={locale}>
        <Suspense fallback={<LayoutSplashScreen />}>
          <LayoutProvider>
            <ThemeModeProvider>
              <ConversationProvider>
                <RecommendedOrdersProvider>
                  <QueryClientProvider client={queryClient}>
                    <App {...props} />
                    <ReactQueryDevtools />
                  </QueryClientProvider>
                </RecommendedOrdersProvider>
              </ConversationProvider>
              <MasterInit />
            </ThemeModeProvider>
          </LayoutProvider>
        </Suspense>
      </I18nextEffect>
    );

    if (el.hasChildNodes()) {
      hydrateRoot(el, appElement);
    } else {
      createRoot(el).render(appElement);
    }
  },
  progress: {
    color: '#4B5563',
  },
});

// This will set light / dark mode on load...
initializeTheme();
