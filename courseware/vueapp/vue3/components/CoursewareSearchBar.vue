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

<script setup>
import { ref, onUpdated } from "vue";

const props = defineProps({
    currentCourseSelectable: {
        type: Boolean,
        default: true,
    },
    showCurrentCourse: {
        type: Boolean,
        default: true,
    },
});

const emit = defineEmits(['doSearch']);

// Data refs
const selectorPosInit = {
    top: 0,
    left: 0
};
const inputSearch = ref('');
const showCourseSelector = ref(false);
const selectorPos = ref(selectorPosInit);
const timer = ref();
const delay = ref(800);

// HTML ref.
const searchbar = ref();

// Methods.
const openCourseSelector = () => {
    // filter needs to be selectable and not active
    if (props.currentCourseSelectable && !props.showCurrentCourse) {
        showCourseSelector.value = true;

        selectorPos.value.top = searchbar.value.offsetTop + 30;
        selectorPos.value.left = searchbar.value.offsetLeft;
    }
};

const hideCourseSelector = () => {
    showCourseSelector.value = false;
};
// this is done in order to avoid hiding (and therefore deactivating the token selector)
// before the click-events of the token selector had a chance to fire
const delayedHideCourseSelector = () => {
    window.setTimeout(() => {
        hideCourseSelector();
    }, 200);
};

const doSearch = () => {
    if (timer.value) {
        clearTimeout(timer.value);
    }

    emit('doSearch', {
        sText: inputSearch.value,
        currentCourseDisplayToggle: props.showCurrentCourse,
    });
};

const doLiveSearch = () => {
    if (timer.value) {
        clearTimeout(timer.value);
    }

    timer.value = setTimeout(() => {
        doSearch();
    }, delay.value);
};

const selectCourse = () => {
    props.showCurrentCourse = true;
    hideCourseSelector();
    doSearch();
};

const removeCourse = () => {
    props.showCurrentCourse = false;
    doSearch();
};

onUpdated(() => {
    if (showCourseSelector.value) {
        openCourseSelector();
    }
});

</script>
