<script setup>
import { onMounted, ref } from 'vue';
import api from '../api';
import { useSession } from '../composables/useSession';
import BadgePill from './BadgePill.vue';

const { session, loginAs, logout } = useSession();

const demoUsers = ref([]);
const selectedEmail = ref('');
const loading = ref(false);
const error = ref('');

onMounted(async () => {
    try {
        const { data } = await api.get('/auth/demo-users');
        demoUsers.value = data;
    } catch {
        error.value = 'Could not load demo users.';
    }
});

async function handleLogin() {
    if (!selectedEmail.value) return;

    loading.value = true;
    error.value = '';

    try {
        await loginAs(selectedEmail.value);
    } catch {
        error.value = 'Login failed. Try another user.';
    } finally {
        loading.value = false;
    }
}
</script>

<template>
    <div class="session-switcher">
        <template v-if="session.user">
            <div class="session-switcher__current">
                <div class="avatar avatar--sm">{{ session.user.name.charAt(0) }}</div>
                <div>
                    <div style="font-weight: 700; font-size: 0.875rem">{{ session.user.name }}</div>
                    <BadgePill :badge="session.user.badge" />
                </div>
            </div>
            <button type="button" class="btn btn--ghost btn--sm" @click="logout">
                Switch user
            </button>
        </template>

        <template v-else>
            <select v-model="selectedEmail" class="select" style="max-width: 12rem">
                <option value="" disabled>Log in as...</option>
                <option v-for="user in demoUsers" :key="user.id" :value="user.email">
                    {{ user.name }}
                </option>
            </select>
            <button
                type="button"
                class="btn btn--primary btn--sm"
                :disabled="!selectedEmail || loading"
                @click="handleLogin"
            >
                {{ loading ? 'Logging in...' : 'Go' }}
            </button>
        </template>
    </div>
    <p v-if="error" class="muted" style="font-size: 0.75rem">{{ error }}</p>
</template>
