<script setup>
import { ref, watch } from 'vue';
import api from '../api';
import { useSession } from '../composables/useSession';
import AppModal from './AppModal.vue';

const props = defineProps({
    modelValue: { type: Boolean, required: true },
    postId: { type: Number, required: true },
});

const emit = defineEmits(['update:modelValue', 'created']);

const { session, refreshCurrentUser } = useSession();
const text = ref('');
const submitting = ref(false);
const error = ref('');

watch(
    () => props.modelValue,
    (open) => {
        if (open) {
            text.value = '';
            error.value = '';
        }
    }
);

function close() {
    emit('update:modelValue', false);
}

async function submit() {
    if (!text.value.trim()) {
        error.value = 'Write something first.';
        return;
    }

    submitting.value = true;
    error.value = '';

    try {
        const { data } = await api.post(`/posts/${props.postId}/comments`, { text: text.value });
        emit('created', data.data);
        await refreshCurrentUser();
        close();
    } catch (e) {
        error.value = e.response?.data?.message ?? 'Could not post your comment.';
    } finally {
        submitting.value = false;
    }
}
</script>

<template>
    <AppModal
        :model-value="modelValue"
        title="Add a comment"
        @update:model-value="$emit('update:modelValue', $event)"
    >
        <div v-if="!session.user" class="alert alert--error">
            Log in as a demo user (top right) before commenting.
        </div>

        <div class="field">
            <label class="field__label" for="comment-text">Your comment</label>
            <textarea
                id="comment-text"
                v-model="text"
                class="textarea"
                :class="{ 'input--invalid': error }"
                rows="4"
                placeholder="What do you think of this post?"
                :disabled="!session.user || submitting"
            />
            <p v-if="error" class="field__error">{{ error }}</p>
        </div>

        <template #footer>
            <button type="button" class="btn btn--ghost" @click="close">Cancel</button>
            <button
                type="button"
                class="btn btn--primary"
                :disabled="!session.user || submitting"
                @click="submit"
            >
                {{ submitting ? 'Posting...' : 'Post comment' }}
            </button>
        </template>
    </AppModal>
</template>
