<script setup>
import { computed } from 'vue';
import IconStar from './icons/IconStar.vue';

const props = defineProps({
    badge: { type: String, default: null },
});

const LABELS = {
    'beginner-badge': 'Beginner',
    'top-fan-badge': 'Top Fan',
    'super-fan-badge': 'Super Fan',
};

const label = computed(() => LABELS[props.badge] ?? 'No badge yet');
const modifierClass = computed(() => (props.badge ? `badge--${props.badge}` : 'badge--none'));
</script>

<template>
    <span class="badge" :class="modifierClass">
        <IconStar v-if="badge" :size="11" class="badge__icon" />
        {{ label }}
    </span>
</template>
