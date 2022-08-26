<template>
    <input type="text" v-on:keyup.enter="setSearch" v-model="inputSearch" placeholder="Suche..." />
    <select v-model="inputSort" @change="setSort">
        <option
            v-for="sort in sorts"
            v-bind:key="sort.key"
            v-bind:value="sort">
            <translate>{{ sort.text }}</translate>
        </option>
    </select>
</template>

<script>
import { mapGetters } from 'vuex'
export default {
    name: "SearchBar",

    data() {
        return {
            inputSort: null,
            sorts: null,
            inputSearch: '',
            searchRoute: '',
            sortRoute: ''
        }
    },

    computed: {
        ...mapGetters([
            'videoSort',
            'videoSorts',
            'playlistSort',
            'playlistSorts'
        ]),
    },

    methods: {
        setSearch() {
            this.$store.dispatch(this.searchRoute, this.inputSearch)
        },

        setSort() {
            console.log(this.sortRoute)
            this.$store.dispatch(this.sortRoute, this.inputSort)
        },
    },

    mounted() {
        if (this.$route.name == 'playlists') {
            this.inputSort = this.playlistSort
            this.sorts = this.playlistSorts
            this.sortRoute = 'setPlaylistSort'
            this.searchRoute = 'setPlaylistSearch'
        }
        else {
            this.inputSort = this.videoSort
            this.sorts = this.videoSorts
            this.sortRoute = 'setVideoSort'
            this.searchRoute = 'setVideoSearch'
        }  
    }
}
</script>