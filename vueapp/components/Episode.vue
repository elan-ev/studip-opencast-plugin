<template>
    <div>
        {{index+1}} : {{event.id}} : {{event.title}} : {{event.lecturer}}
        <button v-on:click="removeEpisode">Remove Episode</button>
    </div>
</template>

<script>
import gpl from "graphql-tag"

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

    methods: {
        removeEpisode() {
            this.$apollo.mutate({
                mutation: gpl` mutation ($id: ID!) {
                    removeEvent(id: $id) {
                        id
                        title
                        lecturer
                    }
                }`,
                variables: {
                    id: this.event.id
                },
            })
        }
    }
}
</script>

<style lang="scss" scoped>

</style>