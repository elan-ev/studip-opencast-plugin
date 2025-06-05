<template>
    <button class="button" :class="[icon]" :type="type" :name="name" v-bind="$attrs">
        <slot>ButtonLabel</slot>
    </button>
</template>

<script>
export default {
    name: "StudipButton",
    inheritAttrs: false,
    props: {
        type: {
            type: String,
            default: 'submit'
        },
        name: {
            type: String,
        },
        icon: {
            type: String,
            validator(value) {
                return ['', 'accept', 'cancel', 'edit', 'move-up', 'move-down', 'add', 'download', 'search', 'trash', 'remove', 'globe'].includes(value)
            },
            default: ''
        }
    },
}
</script>
