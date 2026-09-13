<script setup>
defineProps({
    size: { type: [Number, String], default: 16 },
    filled: { type: Boolean, default: false },
});
</script>

<template>
    <svg
        :width="size"
        :height="size"
        viewBox="0 0 24 24"
        :fill="filled ? 'currentColor' : 'none'"
        stroke="currentColor"
        stroke-width="1.8"
        stroke-linecap="round"
        stroke-linejoin="round"
        aria-hidden="true"
        focusable="false"
    >
        <path d="M12 20.5s-7-4.35-9.5-8.6C1 9 2.5 5.5 6 5.5c2 0 3.5 1.2 4 2.5.5-1.3 2-2.5 4-2.5 3.5 0 5 3.5 3.5 6.9-2.5 4.25-9.5 8.6-9.5 8.6z" />
    </svg>
</template>
