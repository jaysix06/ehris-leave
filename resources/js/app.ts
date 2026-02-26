import { createInertiaApp, router } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import type { DefineComponent } from 'vue';
import { createApp, h } from 'vue';
import { setupCalendar } from 'v-calendar';
import '../css/app.css';
import 'v-calendar/style.css';
import { initializeTheme } from './composables/useAppearance';
import { configureEcho } from '@laravel/echo-vue';

// Prevent failed navigations from leaving the UI unresponsive
router.on('error', (event) => {
    console.warn('[Inertia] Navigation error:', event.detail);
});

// Only connect to Reverb when enabled. Set VITE_REVERB_ENABLED=false in .env when
// Reverb server isn't running to avoid connection timeouts and UI lag.
const reverbEnabled = import.meta.env.VITE_REVERB_ENABLED !== 'false';
if (reverbEnabled) {
    configureEcho({
        broadcaster: 'reverb',
    });
}

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

createInertiaApp({
    title: (title) => (title ? `${title} - ${appName}` : appName),
    resolve: (name) =>
        resolvePageComponent(
            `./pages/${name}.vue`,
            import.meta.glob<DefineComponent>('./pages/**/*.vue'),
        ),
    setup({ el, App, props, plugin }) {
        createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(setupCalendar, {})
            .mount(el);
    },
    progress: {
        color: '#4B5563',
    },
});

// This will set light / dark mode on page load...
initializeTheme();
