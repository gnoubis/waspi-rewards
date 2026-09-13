<script setup>
/**
 * Small, dependency-free modal component.
 *
 * The task brief points to `vue-js-modal`, which is a Vue 2-only plugin
 * with no Vue 3 release - it can't be installed alongside the Vue 3 +
 * Vite stack Laravel ships today. Per the brief's own fallback rule
 * ("anything not [a cited library] should be done in vanilla JS"), this
 * is a small hand-rolled modal instead: a <Teleport> to <body>, a
 * click-outside/Escape-to-close handler, and plain CSS for the overlay
 * and transition (see resources/scss/components/_modal.scss). See
 * README "Assumptions" for the full reasoning.
 */
import { onBeforeUnmount, onMounted, watch } from 'vue';

const props = defineProps({
    modelValue: { type: Boolean, required: true },
    title: { type: String, default: '' },
});

const emit = defineEmits(['update:modelValue']);

function close() {
    emit('update:modelValue', false);
}

function onKeydown(event) {
    if (event.key === 'Escape' && props.modelValue) {
        close();
    }
}

watch(
    () => props.modelValue,
    (open) => {
        document.body.style.overflow = open ? 'hidden' : '';
    }
);

onMounted(() => window.addEventListener('keydown', onKeydown));
onBeforeUnmount(() => {
    window.removeEventListener('keydown', onKeydown);
    document.body.style.overflow = '';
});
</script>

<template>
    <Teleport to="body">
        <Transition name="modal-fade">
            <div
                v-if="modelValue"
                class="modal-overlay"
                @mousedown.self="close"
            >
                <div
                    class="modal"
                    role="dialog"
                    aria-modal="true"
                    :aria-label="title"
                >
                    <header class="modal__header">
                        <h2 class="modal__title">{{ title }}</h2>
                        <button
                            type="button"
                            class="modal__close"
                            aria-label="Close"
                            @click="close"
                        >
                            &times;
                        </button>
                    </header>

                    <div class="modal__body">
                        <slot />
                    </div>

                    <footer v-if="$slots.footer" class="modal__footer">
                        <slot name="footer" />
                    </footer>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>
