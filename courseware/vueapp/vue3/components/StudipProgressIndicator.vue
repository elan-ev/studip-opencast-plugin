<template>
    <div class="progress-indicator-wrapper">
        <div class="progress-indicator" :style="indicatorStyle"></div>
        <p v-if="hasDescription" class="progress-indicator-description">
            {{ description }}
        </p>
        <p v-else class="progress-indicator-description-default">
            {{ $gettext('Lade...') }}
        </p>
    </div>
</template>

<script setup>
import { computed } from "vue";

const props = defineProps({
    description: {
        type: String,
        required: false
    },
    size: {
        type: Number,
        default: 32
    }
});

// Computed props.
const indicatorStyle = computed(() => {
    return {
        backgroundSize: `${props.size}px`,
        height: `${props.size}px`
    };
});

const hasDescription = computed(() => {
    return props.description?.trim().length > 0;
});
</script>
