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
                <translate>{{ sort.text }}</translate>
            </option>
        </select>

        <div class="oc--tokenselector" v-if="showTS"
            :style="`left:` + tokenSelectorPos.left + `px; top:` + tokenSelectorPos.top + `px;`"
        >
            <ul v-if="tokenState == 'main'">
                <li @click="selectToken('tag')" v-if="filteredTags.length">
                    {{ $gettext('Tag') }}
                </li>
                <li @click="selectToken('playlist')" v-if="playlist && playlists.length">
                    {{ $gettext('Wiedergabeliste') }}
                </li>
            </ul>

            <ul v-if="tokenState == 'compare'">
                <li @click="selectToken('=')">=</li>
                <li @click="selectToken('!=')">!=</li>
            </ul>

            <ul v-if="tokenState == 'value' && token.type == 'tag'">
                <li v-for="tag in filteredTags" v-bind:key="tag.id" @click="selectToken(tag)">
                    {{ tag.tag }}
                </li>
            </ul>

            <ul v-if="tokenState == 'value' && token.type == 'playlist'">
                <li v-for="playlist in playlists" v-bind:key="playlist.token" @click="selectToken(playlist)">
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

    props: {
        'playlist' : {
            type: Object,
            default: null
        }
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
            'availableTags',
            'playlists'
        ]),

        filteredTags() {
            let filteredTags = [];

            for (let i = 0; i < this.availableTags.length; i++) {
                 if (!this.searchTokens.find(token => token.value == this.availableTags[i].tag)
                ) {
                    filteredTags.push(this.availableTags[i]);
                }
            }
            return filteredTags;
        },

        availableSortOrders() {
            let sortOrders = [
                {
                    field: 'mkdate',
                    order: 'desc',
                    text : 'Datum hochgeladen: Neueste zuerst'
                },  {
                    field: 'mkdate',
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
            if (this.playlist) {
                this.$store.dispatch('setPlaylistSort', {
                    token: this.playlist.token,
                    sort:  this.inputSort
                });
            } else {
                this.$store.dispatch('setVideoSort', this.inputSort)
            }

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
                    this.token.value      = content.tag;
                    this.token.value_name = content.tag;
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
                order:  this.inputSort.field + '_' + this.inputSort.order
            });
        }

    },

    updated() {
        if (this.showTS) {
            this.showTokenSelector();
        }
    },

    mounted() {
        this.$store.dispatch('updateAvailableTags');
        this.$store.dispatch('loadPlaylists');

        if (this.playlist) {
            // find sort order for current playlist
            let sort, order;

            if (!this.playlist.sort_order) {
                sort = 'mkdate';
                order = 'desc';
            } else {
                [sort, order] = this.playlist.sort_order.split('_');
            }
                this.inputSort = this.availableSortOrders.find(elem => elem.field == sort && elem.order == order);
        }
        else {
            this.inputSort = this.videoSort
        }
    }
}
</script>