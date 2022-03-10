<template>
    <div>
        <input v-model="search" placeholder="search">
    </div>
</template>

<script>
export default {
    name: "EpisodeSearch",

    data () {
        return {
            search: ''
        }
    },

    watch: {
        search: function (s) {
            this.$store.dispatch('setSearch', s)
        }
    },
}
</script>