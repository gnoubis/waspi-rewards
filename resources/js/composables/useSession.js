import { reactive, readonly } from 'vue';
import api, { setAuthToken } from '../api';

const STORAGE_KEY = 'waspi-rewards.session';

const state = reactive({
    user: null,
    token: null,
    ready: false,
});

function persist() {
    try {
        if (state.token) {
            localStorage.setItem(STORAGE_KEY, JSON.stringify({ user: state.user, token: state.token }));
        } else {
            localStorage.removeItem(STORAGE_KEY);
        }
    } catch {
        // Storage can be unavailable (private mode, etc). The session
        // still works for the current page load without persistence.
    }
}

function restore() {
    try {
        const raw = localStorage.getItem(STORAGE_KEY);

        if (raw) {
            const { user, token } = JSON.parse(raw);
            state.user = user;
            state.token = token;
            setAuthToken(token);
        }
    } catch {
        // Ignore a corrupted or inaccessible stored session.
    } finally {
        state.ready = true;
    }
}

async function loginAs(email) {
    const { data } = await api.post('/auth/login', { email });

    state.user = data.user;
    state.token = data.token;
    setAuthToken(data.token);
    persist();
}

async function logout() {
    try {
        if (state.token) {
            await api.post('/auth/logout');
        }
    } finally {
        state.user = null;
        state.token = null;
        setAuthToken(null);
        persist();
    }
}

/** Merge fresh fields (e.g. updated points/badge) into the current user. */
function updateCurrentUser(patch) {
    if (state.user) {
        Object.assign(state.user, patch);
        persist();
    }
}

/**
 * Re-fetch the current user so points/badge reflect any reward just
 * earned from commenting or liking. Cheap and simple beats trying to
 * thread the updated totals back from every action's response.
 */
async function refreshCurrentUser() {
    if (!state.token) return;

    const { data } = await api.get('/auth/user');
    updateCurrentUser(data);
}

restore();

/**
 * Shared "who am I acting as" session for the demo UI. See
 * App\Http\Controllers\Api\AuthController for why this is a
 * password-less login rather than full authentication.
 */
export function useSession() {
    return {
        session: readonly(state),
        loginAs,
        logout,
        updateCurrentUser,
        refreshCurrentUser,
    };
}
