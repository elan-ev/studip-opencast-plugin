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
            <li title="Suche starten"
                class="oc-cw-searchbar-search-icon"
                @click="doSearch"
            >
                <studip-icon
                    shape="search" role="clickable"
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

    data() {
        const sorts = [
            {
                field: 'created',
                order: 'desc',
                text: 'Datum hochgeladen: Neueste zuerst'
            }, {
                field: 'created',
                order: 'asc',
                text: 'Datum hochgeladen: Älteste zuerst'
            }, {
                field: 'title',
                order: 'asc',
                text: 'Titel: Alphabetisch'
            }, {
                field: 'title',
                order: 'desc',
                text: 'Titel: Umgekehrt Alphabetisch'
            }
        ];

        return {
            sorts: sorts,
            inputSort: sorts[0],
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