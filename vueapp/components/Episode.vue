<template>
    <div>
        {{index+1}} : {{event.id}} : {{event.title}} : {{event.lecturer}}
    </div>
</template>

<script>
export default {
    name: "Episode",

    props: {
        event: Object,
        index: Number
    },

    data() {
        return {
        }
    },
}
</script>

<style lang="scss" scoped>

</style>