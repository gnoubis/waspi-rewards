<script setup>
import { ref } from 'vue';
import api from '../api';
import { useSession } from '../composables/useSession';
import IconHeart from './icons/IconHeart.vue';

const props = defineProps({
    comment: { type: Object, required: true },
});

const emit = defineEmits(['deleted']);

const { session, refreshCurrentUser } = useSession();
const busy = ref(false);

function timeAgo(iso) {
    const seconds = Math.floor((Date.now() - new Date(iso).getTime()) / 1000);
    const units = [
        ['year', 31536000],
        ['month', 2592000],
        ['day', 86400],
        ['hour', 3600],
        ['minute', 60],
    ];

    for (const [label, secondsInUnit] of units) {
        const value = Math.floor(seconds / secondsInUnit);
        if (value >= 1) return `${value} ${label}${value > 1 ? 's' : ''} ago`;
    }

    return 'just now';
}

async function toggleLike() {
    if (!session.user || busy.value) return;

    busy.value = true;
    const method = props.comment.liked_by_current_user ? 'delete' : 'post';
    const url = `/comments/${props.comment.id}/like`;

    try {
        const { data } = await api[method](url);
        Object.assign(props.comment, data.data);
        await refreshCurrentUser();
    } finally {
        busy.value = false;
    }
}

async function deleteComment() {
    if (busy.value || !window.confirm('Delete this comment?')) return;

    busy.value = true;

    try {
        await api.delete(`/comments/${props.comment.id}`);
        emit('deleted', props.comment.id);
    } finally {
        busy.value = false;
    }
}
</script>

<template>
    <article class="comment">
        <div class="avatar avatar--sm">{{ comment.user.name.charAt(0) }}</div>

        <div class="comment__body">
            <header class="comment__header">
                <span class="comment__author">{{ comment.user.name }}</span>
                <span class="comment__time">{{ timeAgo(comment.created_at) }}</span>
            </header>

            <p class="comment__text">{{ comment.text }}</p>

            <div class="comment__actions">
                <button
                    type="button"
                    class="like-button"
                    :class="{ 'like-button--active': comment.liked_by_current_user }"
                    :disabled="!session.user || busy"
                    :title="session.user ? '' : 'Log in to like comments'"
                    @click="toggleLike"
                >
                    <IconHeart
                        class="like-button__icon"
                        :size="14"
                        :filled="comment.liked_by_current_user"
                    />
                    {{ comment.likes_count }}
                </button>

                <button
                    v-if="comment.can_delete"
                    type="button"
                    class="delete-button"
                    :disabled="busy"
                    @click="deleteComment"
                >
                    Delete
                </button>
            </div>
        </div>
    </article>
</template>
