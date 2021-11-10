<template>
    <button class="button" type="submit" :name="name" @click="onClick">
        <studip-icon v-if="icon" :icon="icon" role="clickable"/>
        <slot>ButtonLabel</slot>
    </button>
</template>

<script>
import StudipIcon from './StudipIcon.vue';

export default {
    name: "OpencastButton",
   
        components: {
            StudipIcon
        },
        props: {
        name: {
            type: String,
        },
        icon: {
            type: String,
            default: ''
        }
    },
    methods: {
        onClick() {
            this.$emit('click');
        }
    }
}
</script>
