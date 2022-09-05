<template>
    <div class="oc-cw-searchbar">
        <ul class="oc-cw-searchbar-container">
            <li class="oc-cw-searchbar-input">
                <input type="text"
                    ref="searchbar"
                    v-on:keyup.enter="doSearch"
                    v-model="inputSearch"
                    placeholder="Suche..."
                    @submit="doSearch"
                />
            </li>
        </ul>

        <select class="oc-cw-searchbar-sorter" v-model="inputSort" @change="setSort">
            <option
                v-for="sort in sorts"
                v-bind:key="sort.key"
                v-bind:value="sort">
                <translate>{{ sort.text }}</translate>
            </option>
        </select>
    </div>
</template>

<script>
export default {
    name: "CoursewareSearchBar",

    props: ['sorts'],

    data() {
        return {
            inputSort: null,
            inputSearch: ''
        }
    },

    methods: {
        doSearch() {
            this.$emit('doSearch', this.inputSearch);
        },

        resetSearch() {
            this.inputSearch = '';
            this.startSearch();
        },

        setSort() {
            this.$emit('doSort', this.inputSort);
        },

        resetSort() {
            this.inputSort = null;
            this.startSort();
        }
    },
}
</script>