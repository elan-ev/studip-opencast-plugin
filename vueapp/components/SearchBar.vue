<template>
    <div class="oc--searchbar">
        <ul class="oc--searchbar-container">
            <li class="oc--searchbar-token" v-for="token in searchTokens" v-bind:key="token">
                <span>{{ token.type_name }}</span>
                <span>{{ token.compare }}</span>
                <span class="oc--shorten-token">{{ token.value_name }}</span>
                <studip-icon
                    shape="decline" role="clickable" class="oc--remove-filter"
                    @click="removeToken(token)"
                    @blur="delayedHideTokenSelector"
                />
            </li>
            <li class="oc--searchbar-token" v-if="token && token.type">
                <span>{{ token.type_name }}</span>
                <span>{{ token.compare }}</span>
                <span class="oc--shorten-token">{{ token.value_name }}</span>
                <studip-icon
                    shape="decline" role="clickable" class="oc--remove-filter"
                    @click="removeTokenSelect"
                    @blur="delayedHideTokenSelector"
                />
            </li>
            <li class="oc--searchbar--input">
                <input type="text" ref="searchbar"
                    v-on:keyup="hideTokenSelector"
                    v-on:keyup.enter="doSearch"
                    v-model="inputSearch" 
                    :placeholder="$gettext('Suche...')"
                    @focus="showTokenSelector"
                    @click="showTokenSelector"
                    @blur="delayedHideTokenSelector"
                    @submit="doSearch"
                    @input="doLiveSearch"
                />
            </li>
            <li :title="$gettext('Suche starten')"
                class="oc--searchbar--search-icon"
                @click="doSearch"
            >
                <studip-icon
                    shape="search" role="clickable"
                />
            </li>
        </ul>

        <div class="oc--tokenselector" v-if="showTS"
            :style="`left:` + tokenSelectorPos.left + `px; top:` + tokenSelectorPos.top + `px;`"
        >
            <ul v-if="tokenState == 'main'">
                <li v-if="availableTags"
                    @click="selectToken('tag')" :class="{
                    'oc--tokenselector--disabled-option': !filteredTags.length
                }">
                    {{ $gettext('Schlagwort') }}
                </li>

                <li v-if="availablePlaylists"
                    @click="selectToken('playlist')" :class="{
                    'oc--tokenselector--disabled-option': !filteredPlaylists.length
                }">
                    {{ $gettext('Wiedergabeliste') }}
                </li>

                <li v-if="availableCourses"
                    @click="selectToken('course')" :class="{
                    'oc--tokenselector--disabled-option': !filteredCourses.length
                }">
                    {{ $gettext('Veranstaltung') }}
                </li>

                <li v-if="availableCourses"
                    @click="selectToken('lecturer')" :class="{
                    'oc--tokenselector--disabled-option': !filteredLecturers.length
                }">
                    {{ $gettext('Dozent/-in') }}
                </li>
            </ul>

            <ul v-if="tokenState == 'compare'" class="oc--tokenselector--comparison">
                <li @click="selectToken('=')">
                    gleich
                    <span>=</span>
                </li>
                <li @click="selectToken('!=')">
                    ungleich
                    <span>!=</span>
                </li>
            </ul>

            <ul v-if="tokenState == 'value' && token.type == 'tag'">
                <li v-for="(tag, index) in filteredTags" v-bind:key="index" @click="selectToken(tag)">
                    {{ tag }}
                </li>
            </ul>

            <ul v-if="tokenState == 'value' && token.type == 'playlist'">
                <li v-for="playlist in filteredPlaylists" v-bind:key="playlist.token" @click="selectToken(playlist)">
                    {{ playlist.title }}
                </li>
            </ul>

            <ul v-if="tokenState == 'value' && token.type == 'course'">
                <li v-for="course in filteredCourses" v-bind:key="course.id" @click="selectToken(course)">
                    {{ course.name }}
                </li>
            </ul>

            <ul v-if="tokenState == 'value' && token.type == 'lecturer'">
                <li v-for="lecturer in filteredLecturers" v-bind:key="lecturer.username" @click="selectToken(lecturer)">
                    {{ lecturer.name }}
                </li>
            </ul>
        </div>
    </div>
</template>

<script>
import StudipIcon from '@studip/StudipIcon'

export default {
    name: "SearchBar",

    components: {
        StudipIcon
    },

    props: {
        availablePlaylists: {
            type: Array,
            default: null,
        },
        availableTags: {
            type: Array,
            default: null,
        },
        availableCourses: {
            type: Array,
            default: null,
        },
        activePlaylist: {
            type: Object,
            default: null
        },
    },

    emits: ['search'],

    data() {
        return {
            inputSearch: '',
            searchTokens: [],
            showTS: false,
            tokenState: 'main',
            token: null,
            tokenSelectorPos: {
                top: 0,
                left: 0
            },
            timer: null,
            delay: 800 // ms
        }
    },

    computed: {
        filteredTags() {
            if (!this.availableTags) {
                return [];
            }

            let filteredTags = [];

            for (let i = 0; i < this.availableTags.length; i++) {
                if (!this.searchTokens.find(token => token.value === this.availableTags[i])) {
                    filteredTags.push(this.availableTags[i]);
                }
            }
            return filteredTags;
        },

        filteredPlaylists() {
            if (!this.availablePlaylists) {
                return [];
            }

            return this.availablePlaylists.filter(playlist =>
                !this.searchTokens.find(token => token.value === playlist.token)
                && (!this.activePlaylist || this.activePlaylist.token !== playlist.token)
            );
        },

        filteredCourses() {
            if (!this.availableCourses) {
                return [];
            }

            return this.availableCourses.filter(course =>
                !this.searchTokens.find(token => token.value === course.id)
            );
        },

        filteredLecturers() {
            if (!this.availableCourses) {
                return [];
            }

            return this.availableCourses
                .flatMap(course => course.lecturers)
                .filter((lecturer, index, array) => array.findIndex(l => l.username === lecturer.username) === index  // Filter out duplicate lecturers
                    && !this.searchTokens.find(token => token.value === lecturer.username));
        },
    },

    methods: {
        showTokenSelector() {
            this.showTS = true;

            if (this.token == null) {
                this.token = {
                    type_name: null,
                    value_name: null,
                    type:       null,
                    compare:    null,
                    value:      null
                }
            }

            this.tokenSelectorPos.top = this.$refs.searchbar.offsetTop + 30;
            this.tokenSelectorPos.left = this.$refs.searchbar.offsetLeft;
        },

        // this is done in order to avoid hiding (and therefore deactivating the token selector)
        // before the click-events of the token selector had a chance to fire
        delayedHideTokenSelector() {
            let view = this;

            window.setTimeout(() => {
                if (view.token.type == null) {
                    view.hideTokenSelector();
                }
            }, 200);
        },

        hideTokenSelector() {
            this.showTS = false;
        },

        selectToken(content) {
            if (this.tokenState == 'main')
            {
                if (content == 'tag' && this.filteredTags.length) {
                    this.token.type      = 'tag';
                    this.token.type_name = this.$gettext('Schlagwort')
                    this.tokenState      = 'compare';

                } else if (content == 'playlist' && this.filteredPlaylists.length) {
                    this.token.type      = 'playlist';
                    this.token.type_name = this.$gettext('Wiedergabeliste')
                    this.tokenState      = 'compare';

                } else if (content == 'course' && this.filteredCourses.length) {
                    this.token.type      = 'course';
                    this.token.type_name = this.$gettext('Veranstaltung')
                    this.tokenState      = 'compare';

                } else if (content == 'lecturer' && this.filteredLecturers.length) {
                    this.token.type      = 'lecturer';
                    this.token.type_name = this.$gettext('Dozent/-in')
                    this.tokenState      = 'compare';
                }

            } else if (this.tokenState == 'compare')
            {
                this.token.compare = content;
                this.tokenState    = 'value';

            } else if (this.tokenState == 'value')
            {
                if (this.token.type == 'tag') {
                    this.token.value      = content;
                    this.token.value_name = content;
                } else if (this.token.type == 'playlist') {
                    this.token.value      = content.token;
                    this.token.value_name = content.title;
                } else if (this.token.type == 'course') {
                    this.token.value      = content.id;
                    this.token.value_name = content.name;
                } else if (this.token.type == 'lecturer') {
                    this.token.value      = content.username;
                    this.token.value_name = content.name;
                }
                this.tokenState       = 'main';

                this.searchTokens.push(this.token);
                this.token = null;

                this.hideTokenSelector();
                this.doSearch();
            }

        },

        removeToken(token) {
            this.searchTokens.splice(this.searchTokens.indexOf(token), 1);
            this.doSearch();
        },

        removeTokenSelect() {
            this.token = null;
            this.tokenState = 'main';
            this.hideTokenSelector();
        },

        doSearch() {
            clearTimeout(this.timer);

            let filters = JSON.parse(JSON.stringify(this.searchTokens));

            if (this.inputSearch) {
                filters.push({
                    type: 'text',
                    value: this.inputSearch
                });
            }

            this.$emit('search', {
                filters: filters,
            });
        },

        doLiveSearch() {
            clearTimeout(this.timer);

            this.timer = setTimeout(() => {
                this.doSearch();
            }, this.delay);
        },
    },

    updated() {
        if (this.showTS) {
            this.showTokenSelector();
        }
    },
}
</script>