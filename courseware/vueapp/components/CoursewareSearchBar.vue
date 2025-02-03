<template>
    <div class="oc-cw-searchbar">
        <ul class="oc-cw-searchbar-container">
            <li class="oc-cw-searchbar-token" v-show="showCurrentCourse">
                <span>{{ $gettext('Diese Veranstaltung') }}</span>
                <studip-icon
                    shape="decline" role="clickable" class="oc-cw-remove-filter"
                    @click="removeCourse"
                    @blur="delayedHideCourseSelector"
                />
            </li>
            <li class="oc-cw-searchbar-input">
                <input type="text"
                    ref="searchbar"
                    v-on:keyup="hideCourseSelector"
                    v-on:keyup.enter="doSearch"
                    v-model="inputSearch"
                    :placeholder="$gettext('Suche...')"
                    @focus="openCourseSelector"
                    @click="openCourseSelector"
                    @blur="delayedHideCourseSelector"
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

        <div class="oc-cw-tokenselector" v-if="showCourseSelector"
            :style="`left:` + selectorPos.left + `px; top:` + selectorPos.top + `px;`"
        >
            <ul>
                <li @click="selectCourse">
                    {{ $gettext('Diese Veranstaltung') }}
                </li>
            </ul>
        </div>
    </div>
</template>

<script>
export default {
    name: "CoursewareSearchBar",

    props: {
        currentCourseSelectable: {
            type: Boolean,
            default: true,
        },
        showCurrentCourse: {
            type: Boolean,
            default: true,
        },
    },

    emits: ['doSearch'],

    data() {
        return {
            inputSearch: '',
            showCourseSelector: false,
            selectorPos: {
                top: 0,
                left: 0
            },
            timer: null,
            delay: 800 //ms
        }
    },

    methods: {
        openCourseSelector() {
            // filter needs to be selectable and not active
            if (this.currentCourseSelectable && !this.showCurrentCourse) {
                this.showCourseSelector = true;

                this.selectorPos.top = this.$refs.searchbar.offsetTop + 30;
                this.selectorPos.left = this.$refs.searchbar.offsetLeft;
            }
        },

        // this is done in order to avoid hiding (and therefore deactivating the token selector)
        // before the click-events of the token selector had a chance to fire
        delayedHideCourseSelector() {
            let view = this;

            window.setTimeout(() => {
                view.hideCourseSelector();
            }, 200);
        },

        hideCourseSelector() {
            this.showCourseSelector = false;
        },

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

        selectCourse() {
            this.showCurrentCourse = true;
            this.hideCourseSelector();
            this.doSearch();
        },

        removeCourse() {
            this.showCurrentCourse = false;
            this.doSearch();
        }
    },

    updated() {
        if (this.showCourseSelector) {
            this.openCourseSelector();
        }
    },
}
</script>