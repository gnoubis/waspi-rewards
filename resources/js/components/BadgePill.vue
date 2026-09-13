<script setup>
import { computed } from 'vue';

const props = defineProps({
    badge: { type: String, default: null },
});

const LABELS = {
    'beginner-badge': { icon: '🥉', label: 'Beginner' },
    'top-fan-badge': { icon: '🥈', label: 'Top Fan' },
    'super-fan-badge': { icon: '🥇', label: 'Super Fan' },
};

const info = computed(() => LABELS[props.badge] ?? { icon: '·', label: 'No badge yet' });
const modifierClass = computed(() => (props.badge ? `badge--${props.badge}` : 'badge--none'));
</script>

<template>
    <span class="badge" :class="modifierClass">
        <span class="badge__icon" aria-hidden="true">{{ info.icon }}</span>
        {{ info.label }}
    </span>
</template>
