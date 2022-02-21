<template>
    <button class="button" :class="[icon]" type="submit" :name="name" @click="onClick">
        <slot>ButtonLabel</slot>
    </button>
</template>

<script>
import StudipIcon from '@studip/StudipIcon.vue';

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
