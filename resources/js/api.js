import axios from 'axios';

/**
 * Dedicated API client for the WASPI REWARDS backend. Kept separate from
 * `window.axios` (set up in bootstrap.js) so we can attach the demo
 * session's bearer token without touching global defaults.
 */
const api = axios.create({
    baseURL: '/api',
    headers: {
        Accept: 'application/json',
    },
});

export function setAuthToken(token) {
    if (token) {
        api.defaults.headers.common.Authorization = `Bearer ${token}`;
    } else {
        delete api.defaults.headers.common.Authorization;
    }
}

export default api;
