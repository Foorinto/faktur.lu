import '../css/app.css';
import './bootstrap';

import { createInertiaApp, router } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createApp, h } from 'vue';
import { ZiggyVue } from '../../vendor/tightenco/ziggy';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

// Track if Vue app is properly mounted
let vueAppMounted = false;

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

    // Prevent default and force reload for any invalid response
    // This ensures we never show raw JSON to users
    event.preventDefault();

    // If it's an auth error or any other issue, reload the page
    if (response?.status === 401 || response?.status === 419 || response?.status === 409) {
        window.location.reload();
    } else {
        // For other cases, navigate to the current URL to get fresh HTML
        window.location.href = window.location.href;
    }
});

// Handle Inertia exceptions (network errors, etc.)
router.on('exception', (event) => {
    // For most exceptions, let the browser handle it naturally
    // by reloading the page
    if (event.detail.exception) {
        console.error('Inertia exception:', event.detail.exception);
        // Force reload on exception to recover
        window.location.reload();
    }
});

// Detect and fix JSON/Inertia response being displayed as raw text
// This can happen when browser shows cached XHR response or bfcache restores stale state
const detectJsonDisplay = () => {
    // If Vue app is mounted and running, everything is fine
    if (vueAppMounted) {
        const app = document.getElementById('app');
        if (app && app.children.length > 0) {
            return false;
        }
    }

    const body = document.body;
    if (!body) return false;

    // Check if the page content looks like raw JSON
    const bodyText = body.innerText?.trim() || body.textContent?.trim() || '';

    // Quick check: if body starts with { and contains "component", it's likely Inertia JSON
    if (bodyText.startsWith('{') && bodyText.includes('"component"')) {
        try {
            const parsed = JSON.parse(bodyText);
            // Check if this looks like an Inertia response
            if (parsed.component && parsed.props && parsed.url) {
                console.warn('Detected raw Inertia JSON response, reloading page...');
                window.location.reload();
                return true;
            }
        } catch {
            // Not valid JSON, continue checking
        }
    }

    // Also check for pre tag containing JSON (some browsers wrap it)
    const pre = body.querySelector('pre');
    if (pre) {
        const preText = pre.textContent?.trim() || '';
        if (preText.startsWith('{') && preText.includes('"component"')) {
            try {
                const parsed = JSON.parse(preText);
                if (parsed.component && parsed.props) {
                    console.warn('Detected raw Inertia JSON in <pre> tag, reloading page...');
                    window.location.reload();
                    return true;
                }
            } catch {
                // Not valid JSON, ignore
            }
        }
    }

    return false;
};

// Check for JSON display on DOMContentLoaded
document.addEventListener('DOMContentLoaded', () => {
    // Delay slightly to allow Vue to mount
    setTimeout(() => {
        if (!vueAppMounted) {
            detectJsonDisplay();
        }
    }, 500);
});

// Handle page restored from bfcache (back-forward cache)
window.addEventListener('pageshow', (event) => {
    if (event.persisted) {
        // Page was restored from bfcache - check state and potentially reload
        setTimeout(detectJsonDisplay, 100);
    }
});

// Check when tab becomes visible again after being hidden
let lastHiddenTime = null;
document.addEventListener('visibilitychange', () => {
    if (document.visibilityState === 'hidden') {
        lastHiddenTime = Date.now();
    } else if (document.visibilityState === 'visible') {
        // Check for JSON display
        setTimeout(detectJsonDisplay, 100);

        // If tab was hidden for more than 5 minutes, do a soft refresh
        // This helps keep the page state fresh
        if (lastHiddenTime && (Date.now() - lastHiddenTime) > 5 * 60 * 1000) {
            // Check if the page state seems stale or broken
            const app = document.getElementById('app');
            if (!app || app.children.length === 0 || !vueAppMounted) {
                console.warn('Page state appears stale after long inactivity, reloading...');
                window.location.reload();
            }
        }
    }
});

// Also handle focus event as backup
window.addEventListener('focus', () => {
    setTimeout(detectJsonDisplay, 200);
});

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) =>
        resolvePageComponent(
            `./Pages/${name}.vue`,
            import.meta.glob('./Pages/**/*.vue'),
        ),
    setup({ el, App, props, plugin }) {
        const app = createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(ZiggyVue)
            .mount(el);

        // Mark Vue app as successfully mounted
        vueAppMounted = true;

        return app;
    },
    progress: {
        color: '#4B5563',
    },
});
