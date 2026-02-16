import axios from 'axios';
window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
window.axios.defaults.withCredentials = true;
window.axios.defaults.withXSRFToken = true;

// Configure CSRF token for all axios requests
const updateCsrfToken = () => {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    if (csrfToken) {
        window.axios.defaults.headers.common['X-CSRF-TOKEN'] = csrfToken;
    }
    return csrfToken;
};

// Initial CSRF token setup
updateCsrfToken();

// Update token before each request to ensure freshness
axios.interceptors.request.use((config) => {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    if (csrfToken) {
        config.headers['X-CSRF-TOKEN'] = csrfToken;
    }
    return config;
});

// Refresh CSRF token when tab becomes visible after being hidden
let lastVisibilityChange = Date.now();
const SESSION_CHECK_THRESHOLD = 5 * 60 * 1000; // 5 minutes

document.addEventListener('visibilitychange', async () => {
    if (document.visibilityState === 'visible') {
        const timeSinceLastChange = Date.now() - lastVisibilityChange;

        // Only refresh if tab was hidden for more than 5 minutes
        if (timeSinceLastChange > SESSION_CHECK_THRESHOLD) {
            try {
                // Fetch fresh CSRF token from server
                const response = await axios.get('/api/csrf-token', {
                    headers: {
                        'Accept': 'application/json',
                    },
                });

                if (response.data?.csrf_token) {
                    // Update meta tag
                    const metaTag = document.querySelector('meta[name="csrf-token"]');
                    if (metaTag) {
                        metaTag.setAttribute('content', response.data.csrf_token);
                    }
                    // Update axios default header
                    window.axios.defaults.headers.common['X-CSRF-TOKEN'] = response.data.csrf_token;
                }
            } catch (error) {
                // If we get 401/419, session is invalid - reload page
                if (error.response?.status === 401 || error.response?.status === 419) {
                    window.location.reload();
                }
            }
        }
        lastVisibilityChange = Date.now();
    } else {
        lastVisibilityChange = Date.now();
    }
});

// Handle 419 errors globally - refresh page to get new session/token
axios.interceptors.response.use(
    response => response,
    error => {
        if (error.response?.status === 419) {
            // CSRF token mismatch - reload page to get fresh token
            window.location.reload();
            return new Promise(() => {}); // Never resolve to prevent further processing
        }
        return Promise.reject(error);
    }
);
