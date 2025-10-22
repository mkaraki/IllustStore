import '../css/app.scss';
import '../css/global.css';

import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import type { DefineComponent } from 'vue';
import { createApp, h } from 'vue';
import * as Sentry from "@sentry/vue";

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

createInertiaApp({
    title: (title) => (title ? `${title} - ${appName}` : appName),
    resolve: (name) => resolvePageComponent(`./pages/${name}.vue`, import.meta.glob<DefineComponent>('./pages/**/*.vue')),
    setup({ el, App, props, plugin }) {
        const app = createApp({ render: () => h(App, props) })
            .use(plugin);

        Sentry.init({
            app,
            dsn: props.initialPage.props?.sentryDsn ?? undefined,
            environment: props.initialPage.props?.laravelEnvironment ?? undefined,
            sendDefaultPii: false,
            integrations: [
                Sentry.browserTracingIntegration({}),
                Sentry.replayIntegration(),
            ],
            enableLogs: true,
            tracesSampleRate: 1.0,
            tracePropagationTargets: [ import.meta.env.BASE_URL ],
            replaysSessionSampleRate: 0.1,
            replaysOnErrorSampleRate: 1.0,
        });

        app.mount(el);
    },
    progress: {
        color: '#4B5563',
    },
});
