import { ThemeModeProvider } from '@/_metronic/partials';
import { RecommendedOrdersProvider } from '@/store/recommend-orders-context';
import { ConversationProvider } from '@/store/use-chat';
import { createInertiaApp } from '@inertiajs/react';
import createServer from '@inertiajs/react/server';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { Suspense } from 'react';
import ReactDOMServer from 'react-dom/server';
import { LayoutProvider, LayoutSplashScreen } from './_metronic/layout/core';
import I18nextEffect from './lang/I18next-effect';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

createServer((page) =>
    createInertiaApp({
        page,
        render: ReactDOMServer.renderToString,
        title: (title) => `${title} - ${appName}`,
        resolve: (name) => resolvePageComponent(`./pages/${name}.tsx`, import.meta.glob('./pages/**/*.tsx')),
        setup({ App, props }) {
            const locale = (props.initialPage.props.app as { locale: string })?.locale || 'en';
            const queryClient = new QueryClient();

            return (
                <I18nextEffect locale={locale}>
                    <Suspense fallback={<LayoutSplashScreen />}>
                        <LayoutProvider>
                            <ThemeModeProvider>
                                <ConversationProvider>
                                    <RecommendedOrdersProvider>
                                        <QueryClientProvider client={queryClient}>
                                            <App {...props} />
                                        </QueryClientProvider>
                                    </RecommendedOrdersProvider>
                                </ConversationProvider>
                                {/* MasterInit excluded from SSR - requires document */}
                            </ThemeModeProvider>
                        </LayoutProvider>
                    </Suspense>
                </I18nextEffect>
            );
        },
    }),
);
