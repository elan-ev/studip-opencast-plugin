<template>
    <div class="oc-cw-searchbar">
        <ul class="oc-cw-searchbar-container">
            <li class="oc-cw-searchbar-token" v-show="showCurrentCourse">
                <span>{{ $gettext('Diese Veranstaltung') }}</span>
                <studip-icon
                    shape="decline" role="clickable" class="oc-cw-remove-filter"
                    @click="removeCourse"
                />
            </li>
            <li class="oc-cw-searchbar-input">
                <input type="text"
                    ref="searchbar"
                    v-on:keyup.enter="doSearch"
                    v-model="inputSearch"
                    :placeholder="$gettext('Suche...')"
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

    props: {
        showCurrentCourse: {
            type: Boolean,
            default: true,
        },
    },

    emits: ['doSearch'],

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

            this.$emit('doSearch', {
                searchText: this.inputSearch,
                showCurrentCourse: this.showCurrentCourse,
            });
        },

        doLiveSearch() {
            clearTimeout(this.timer);

            this.timer = setTimeout(() => {
                this.doSearch();
            }, this.delay);
        },

        removeCourse() {
            this.showCurrentCourse = false;
            this.doSearch();
        }
    },
}
</script>