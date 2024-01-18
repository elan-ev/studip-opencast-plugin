<template>
    <div class="oc-cw-searchbar">
        <ul class="oc-cw-searchbar-container">
            <li class="oc-cw-searchbar-input">
                <input type="text"
                    ref="searchbar"
                    v-on:keyup.enter="doSearch"
                    v-model="inputSearch"
                    placeholder="Suche..."
                    @input="doLiveSearch"
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
    </div>
</template>

<script>
export default {
    name: "CoursewareSearchBar",

    data() {
        return {
            inputSearch: '',
            timer: null,
            delay: 800 //ms
        }
    },

    methods: {
        doSearch() {
            clearTimeout(this.timer);

            this.$emit('doSearch', this.inputSearch);
        },

        doLiveSearch() {
            clearTimeout(this.timer);

            this.timer = setTimeout(() => {
                this.doSearch();
            }, this.delay);
        }
    },
}
</script>