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

<script>
export default {
    name: 'studip-progress-indicator',
    props: {
        description: {
            type: String,
            required: false
        },
        size: {
            type: Number,
            default: 32
        }
    },
    computed: {
        indicatorStyle() {
            return {
                backgroundSize: `${this.size}px`,
                height: `${this.size}px`
            };
        },
        hasDescription () {
            return this.description?.trim().length > 0;
        }
    }
}
</script>
