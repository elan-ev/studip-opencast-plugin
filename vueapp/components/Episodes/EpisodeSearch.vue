<template>
    <div class="oc--searchbar">
        <input type="text" v-model="search" placeholder="search" @keyup.enter="searchHandler">
        <button class="submit" 
            @click="searchHandler">submit</button>
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

    methods: {
        searchHandler() {
            this.$store.dispatch('setSearch', this.search)
        }
    }
}
</script>