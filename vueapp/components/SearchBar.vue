<template>
    <div class="oc--searchbar">
        <ul class="oc--searchbar-container">
            <li class="oc--searchbar-token" v-for="token in searchTokens" v-bind:key="token">
                <span>{{ token.type_name }}</span>
                <span>{{ token.compare }}</span>
                <span class="oc--shorten-token">{{ token.value_name }}</span>
                <studip-icon
                    shape="decline" role="info" class="oc--remove-filter"
                    @click="removeToken(token)"
                    @blur="delayedHideTokenSelector"
                />
            </li>
            <li class="oc--searchbar-token" v-if="token && token.type">
                 {{ token.type_name }} {{ token.compare }} {{ token.value_name }}
            </li>
            <li class="oc--searchbar-input">
                <input type="text" ref="searchbar"
                    v-on:keyup="hideTokenSelector"
                    v-on:keyup.enter="doSearch"
                    v-model="inputSearch" placeholder="Suche..."
                    @focus="showTokenSelector"
                    @click="showTokenSelector"
                    @blur="delayedHideTokenSelector"
                    @submit="doSearch"
                />
            </li>
        </ul>

        <select class="oc--searchbar-sorter" v-model="inputSort" @change="setSort">
            <option
                v-for="sort in availableSortOrders"
                v-bind:key="sort.key"
                v-bind:value="sort">
                {{ sort.text }}
            </option>
        </select>

        <div class="oc--tokenselector" v-if="showTS"
            :style="`left:` + tokenSelectorPos.left + `px; top:` + tokenSelectorPos.top + `px;`"
        >
            <ul v-if="tokenState == 'main'">
                <li @click="selectToken('tag')" v-if="filteredTags.length">
                    {{ $gettext('Tag') }}
                </li>
                <li @click="selectToken('playlist')" v-if="playlists && comparablePlaylists.length">
                    {{ $gettext('Wiedergabeliste') }}
                </li>
            </ul>

            <ul v-if="tokenState == 'compare'">
                <li @click="selectToken('=')">=</li>
                <li @click="selectToken('!=')">!=</li>
            </ul>

            <ul v-if="tokenState == 'value' && token.type == 'tag'">
                <li v-for="(tag, index) in filteredTags" v-bind:key="index" @click="selectToken(tag)">
                    {{ tag }}
                </li>
            </ul>

            <ul v-if="tokenState == 'value' && token.type == 'playlist'">
                <li v-for="playlist in comparablePlaylists" v-bind:key="playlist.token" @click="selectToken(playlist)">
                    {{ playlist.title }}
                </li>
            </ul>
        </div>
    </div>
</template>

<script>
import { mapGetters } from 'vuex'
import StudipIcon from '@studip/StudipIcon'

export default {
    name: "SearchBar",

    components: {
        StudipIcon
    },

    data() {
        return {
            inputSort: null,
            inputSearch: '',
            searchTokens: [],
            showTS: false,
            tokenState: 'main',
            token: null,
            tokenSelectorPos: {
                top: 0,
                left: 0
            }        }
    },

    computed: {
        ...mapGetters([
            'videoSort',
            'availableVideoTags',
            'playlists',
            'playlist'
        ]),

        filteredTags() {
            let filteredTags = [];

            for (let i = 0; i < this.availableVideoTags.length; i++) {
                if (!this.searchTokens.find(token => token.value == this.availableVideoTags[i])) {
                    filteredTags.push(this.availableVideoTags[i]);
                }
            }
            return filteredTags;
        },

        comparablePlaylists() {
            if (this.playlist) {
                return this.playlists.filter(playlist => playlist.token != this.playlist.token);
            }
            return this.playlists;
        },

        availableSortOrders() {
            let sortOrders = [
                {
                    field: 'created',
                    order: 'desc',
                    text : 'Datum hochgeladen: Neueste zuerst'
                },  {
                    field: 'created',
                    order: 'asc',
                    text : 'Datum hochgeladen: Älteste zuerst'
                },  {
                    field: 'title',
                    order: 'asc',
                    text : 'Titel: Alphabetisch'
                }, {
                    field: 'title',
                    order: 'desc',
                    text : 'Titel: Umgekehrt Alphabetisch'
                }
            ];

            if (this.playlist) {
                sortOrders.push({
                    field: 'order',
                    order: 'asc',
                    text : 'Benutzerdefiniert'
                }, {
                    field: 'order',
                    order: 'desc',
                    text : 'Benutzerdefiniert Umgekehrt'
                });
            }

            return sortOrders;
        }
    },

    methods: {
        setSort() {
            if (this.playlist && this.$route.name === 'playlist_edit') {
                this.$store.dispatch('setPlaylistSort', {
                    token: this.playlist.token,
                    sort:  this.inputSort
                });
            }
            this.$store.dispatch('setVideoSort', this.inputSort)
            this.doSearch();
        },

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
                if (content == 'tag') {
                    this.token.type      = 'tag';
                    this.token.type_name = this.$gettext('Tag')
                    this.tokenState      = 'compare';

                } else if (content == 'playlist') {
                    this.token.type      = 'playlist';
                    this.token.type_name = this.$gettext('Wiedergabeliste')
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

        doSearch() {
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
    },

    updated() {
        if (this.showTS) {
            this.showTokenSelector();
        }
    },

    mounted() {
        if (this.playlist) {
            // Default sort option should already be selected
            this.inputSort = this.availableSortOrders.find(elem => elem.field == this.videoSort.field && elem.order == this.videoSort.order);
        }
        else {
            // TODO Maybe use a global default sort order
            this.inputSort = {
                field: 'created',
                order: 'desc',
                text : 'Datum hochgeladen: Neueste zuerst'
            };
        }
        this.$store.dispatch('setVideoSort', this.inputSort);
    },

    watch: {
        // Make sure that inputSort is synced with store
        videoSort(newSort) {
            if (newSort != null) {
                this.inputSort = this.availableSortOrders.find(elem => elem.field == newSort.field && elem.order == newSort.order);
            }
        }
    }
}
</script>