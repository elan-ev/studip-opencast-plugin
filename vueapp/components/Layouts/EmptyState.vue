<template>
    <div class="oc--empty-state">
        <OpencastIcon class="oc--empty-state__icon"/>
        <h1 class="oc--empty-state__title">{{ title }}</h1>
        <p class="oc--empty-state__description">{{ description }}</p>
        <div class="oc--empty-state__content-wrapper">
            <slot name="content" />
        </div>
        <div class="oc--empty-state__buttons">
            <slot name="buttons" />
        </div>
    </div> 
</template>
<script setup>
import OpencastIcon from '../OpencastIcon.vue';
import {} from 'vue';

const props = defineProps({
    title: {
        require: true,
        type: String
    },
    description: {
        type: String,
        default: ''
    }
});
</script>