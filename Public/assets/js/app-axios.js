// Make sure axios is available (either via CDN or npm build)
const api = axios.create({
    baseURL: '/api', // or your full API URL if needed
    timeout: 10000,
    headers: {
        'Content-Type': 'application/json',
    },
});

// Attach CSRF Token (important for Laravel)
const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
if (token) {
    api.defaults.headers.common['X-CSRF-TOKEN'] = token;
}

let isRefreshing = false;
let refreshQueue = [];

function processQueue(error, token = null) {
    refreshQueue.forEach(p => {
        if (error) {
            p.reject(error);
        } else {
            p.resolve(token);
        }
    });
    refreshQueue = [];
}

// Handle token expiration
api.interceptors.response.use(
    
    response => response,

    async error => {
        
        const originalRequest = error.config;

        // If unauthorized (token expired)
        // Skip refresh for the login endpoint — 401 there means bad credentials, not expired session
        if (error.response?.status === 401 && !originalRequest._retry && !originalRequest.url.includes('/auth/login')) {

            originalRequest._retry = true;

            // Avoid multiple parallel refresh requests
            if (isRefreshing) {
                return new Promise((resolve, reject) => {
                    refreshQueue.push({ resolve, reject });
                }).then(token => {                    
                    return api(originalRequest);
                });
            }

            isRefreshing = true;

            try {
                
                const { data } = await axios.post("/api/auth/refresh-token", {}, {
                    headers: { 'X-Client-Type': 'web' },
                    withCredentials: true,
                });

                const newToken = data.data?.access_token;

                isRefreshing = false;
                
                processQueue(null, newToken);

                return api(originalRequest);

            } catch (refreshError) {
                
                isRefreshing = false;
                
                processQueue(refreshError, null);

                const currentPath = window.location.pathname + window.location.search;
                window.location.href = "/login?redirect=" + encodeURIComponent(currentPath);
                
                return Promise.reject(refreshError);
            }
        }

        return Promise.reject(error);
    }
);

window.api = api; // Expose globally so you can use `api` anywhere