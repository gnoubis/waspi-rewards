<script setup>
import { ref, watch } from 'vue';
import { useSession } from '../composables/useSession';
import AppModal from './AppModal.vue';

const props = defineProps({
    modelValue: { type: Boolean, required: true },
});

const emit = defineEmits(['update:modelValue', 'created']);

const { register } = useSession();

const name = ref('');
const email = ref('');
const submitting = ref(false);
const error = ref('');

watch(
    () => props.modelValue,
    (open) => {
        if (open) {
            name.value = '';
            email.value = '';
            error.value = '';
        }
    }
);

function close() {
    emit('update:modelValue', false);
}

async function submit() {
    if (!name.value.trim() || !email.value.trim()) {
        error.value = 'Name and email are both required.';
        return;
    }

    submitting.value = true;
    error.value = '';

    try {
        await register(name.value.trim(), email.value.trim());
        emit('created');
        close();
    } catch (e) {
        const message = e.response?.data?.errors?.email?.[0];
        error.value = message ?? 'Could not create that user.';
    } finally {
        submitting.value = false;
    }
}
</script>

<template>
    <AppModal
        :model-value="modelValue"
        title="New user"
        @update:model-value="$emit('update:modelValue', $event)"
    >
        <div class="field">
            <label class="field__label" for="new-user-name">Name</label>
            <input
                id="new-user-name"
                v-model="name"
                type="text"
                class="input"
                placeholder="Ada Lovelace"
                :disabled="submitting"
            >
        </div>

        <div class="field">
            <label class="field__label" for="new-user-email">Email</label>
            <input
                id="new-user-email"
                v-model="email"
                type="email"
                class="input"
                placeholder="ada@example.com"
                :disabled="submitting"
            >
            <p v-if="error" class="field__error">{{ error }}</p>
        </div>

        <template #footer>
            <button type="button" class="btn btn--ghost" @click="close">Cancel</button>
            <button
                type="button"
                class="btn btn--primary"
                :disabled="submitting"
                @click="submit"
            >
                {{ submitting ? 'Creating...' : 'Create and log in' }}
            </button>
        </template>
    </AppModal>
</template>
