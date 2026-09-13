<script setup>
import { onMounted, ref, watch } from 'vue';
import api from './api';
import BadgePill from './components/BadgePill.vue';
import IconStar from './components/icons/IconStar.vue';
import PostCard from './components/PostCard.vue';
import SessionSwitcher from './components/SessionSwitcher.vue';
import { useSession } from './composables/useSession';

const { session } = useSession();

const posts = ref([]);
const loading = ref(true);
const error = ref('');

async function loadPosts() {
    loading.value = true;
    error.value = '';

    try {
        const { data } = await api.get('/posts');
        posts.value = data.data;
    } catch {
        error.value = 'Could not load posts. Is the API running?';
    } finally {
        loading.value = false;
    }
}

onMounted(loadPosts);

// Per-user fields on each comment (liked_by_current_user, can_delete) are
// computed server-side from the request's authenticated user at fetch
// time. Without this, switching who's logged in would leave every
// comment showing the *previous* user's like/delete state until the next
// unrelated re-render - see README "Known fix: like requiring 2 clicks
// after switching users".
watch(
    () => session.user?.id,
    () => loadPosts()
);

const badgeRules = [
    { badge: 'beginner-badge', rule: '1st comment or 10th like', points: '+50 / +500 pts' },
    { badge: 'top-fan-badge', rule: '30th comment', points: '+2500 pts' },
    { badge: 'super-fan-badge', rule: '50th comment', points: '+5000 pts' },
];
</script>

<template>
    <div class="app-shell">
        <header class="app-header">
            <div class="app-header__inner">
                <div class="app-header__brand">
                    <span class="app-header__logo">
                        <IconStar :size="18" />
                    </span>
                    WASPI REWARDS
                </div>
                <SessionSwitcher />
            </div>
        </header>

        <main class="app-main">
            <section class="card" style="margin-bottom: 1.5rem">
                <h2 class="card__title" style="margin-bottom: 0.75rem">How points work</h2>
                <div style="display: flex; flex-direction: column; gap: 0.5rem">
                    <div
                        v-for="rule in badgeRules"
                        :key="rule.badge"
                        style="display: flex; align-items: center; justify-content: space-between; gap: 0.5rem"
                    >
                        <BadgePill :badge="rule.badge" />
                        <span class="muted" style="font-size: 0.8125rem">{{ rule.rule }}</span>
                        <span style="font-weight: 700; font-size: 0.8125rem">{{ rule.points }}</span>
                    </div>
                </div>
            </section>

            <div v-if="loading" class="empty-state">Loading posts...</div>
            <div v-else-if="error" class="alert alert--error">{{ error }}</div>
            <div v-else-if="!posts.length" class="empty-state">No posts yet.</div>
            <template v-else>
                <PostCard v-for="post in posts" :key="post.id" :post="post" />
            </template>
        </main>
    </div>
</template>
