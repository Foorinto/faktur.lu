import '../css/app.css';
import './bootstrap';

import { createInertiaApp, router } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createApp, h } from 'vue';
import { ZiggyVue } from '../../vendor/tightenco/ziggy';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

// Sync CSRF token after each Inertia navigation
// This ensures axios always has the latest token from the server
router.on('success', (event) => {
    const csrfToken = event.detail.page.props.csrf_token;
    if (csrfToken) {
        // Update meta tag
        const metaTag = document.querySelector('meta[name="csrf-token"]');
        if (metaTag) {
            metaTag.setAttribute('content', csrfToken);
        }
        // Update axios default header
        window.axios.defaults.headers.common['X-CSRF-TOKEN'] = csrfToken;
    }
});

// Handle Inertia invalid responses (non-Inertia response received)
// This can happen when session expires or server returns HTML error page
router.on('invalid', (event) => {
    const response = event.detail.response;

    // Only reload for authentication-related errors
    // Don't reload for validation errors (422) or other handled errors
    if (response?.status === 401 || response?.status === 419) {
        event.preventDefault();
        window.location.reload();
    }
    // For other cases, let Inertia handle it naturally
});

// Handle Inertia exceptions (network errors, etc.)
router.on('exception', (event) => {
    // For most exceptions, let the browser handle it naturally
    // by reloading the page
    if (event.detail.exception) {
        console.error('Inertia exception:', event.detail.exception);
    }
});

// Detect and fix JSON/Inertia response being displayed as raw text
// This can happen when browser shows cached XHR response or bfcache restores stale state
const detectJsonDisplay = () => {
    // Check if body has no #app element (Inertia container)
    const app = document.getElementById('app');
    const body = document.body;

    if (!body) return;

    // If there's a proper #app with Vue mounted, page is fine
    if (app && app.children.length > 0 && app.__vue_app__) {
        return;
    }

    // Check body text content for raw Inertia JSON response
    const text = body.textContent?.trim() || '';
    if (text.startsWith('{') && text.endsWith('}')) {
        try {
            const parsed = JSON.parse(text);
            // Check if this looks like an Inertia response
            if (parsed.component && parsed.props) {
                // Inertia JSON is being displayed as text - reload page
                window.location.reload();
                return;
            }
        } catch {
            // Not valid JSON, ignore
        }
    }

    // Also check for pre tag containing JSON (some browsers wrap it)
    const pre = body.querySelector('pre');
    if (pre && !app) {
        const preText = pre.textContent?.trim() || '';
        if (preText.startsWith('{') && preText.endsWith('}')) {
            try {
                const parsed = JSON.parse(preText);
                if (parsed.component && parsed.props) {
                    window.location.reload();
                    return;
                }
            } catch {
                // Not valid JSON, ignore
            }
        }
    }
};

// Check for JSON display on DOMContentLoaded
document.addEventListener('DOMContentLoaded', detectJsonDisplay);

// Handle page restored from bfcache (back-forward cache)
window.addEventListener('pageshow', (event) => {
    if (event.persisted) {
        // Page was restored from bfcache - check state and potentially reload
        detectJsonDisplay();
    }
});

// Check when tab becomes visible again
document.addEventListener('visibilitychange', () => {
    if (document.visibilityState === 'visible') {
        // Small delay to let page render
        setTimeout(detectJsonDisplay, 100);
    }
});

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) =>
        resolvePageComponent(
            `./Pages/${name}.vue`,
            import.meta.glob('./Pages/**/*.vue'),
        ),
    setup({ el, App, props, plugin }) {
        return createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(ZiggyVue)
            .mount(el);
    },
    progress: {
        color: '#4B5563',
    },
});
