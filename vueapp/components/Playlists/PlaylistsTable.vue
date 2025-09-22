<template>
    <div>
        <SearchBar
            :availableTags="playlistsTags"
            :availableCourses="playlistsCourses"
            @search="doSearch"
        />

        <PaginationButtons
            :paging="paging"
            @changePage="changePage"
            @changeLimit="changeLimit"
        />

        <table class="default">
            <colgroup>
                <col v-if="selectable" style="width: 2%">
                <col style="width: 40%">
                <col style="width: 18%">
                <col style="width: 18%">
                <col style="width: 5%">
                <col style="width: 15%">
                <col v-if="showActions" style="width: 2%">
            </colgroup>
            <thead>
            <tr>
                <th v-if="selectable">
                    <input v-if="multiSelect"
                        type="checkbox"
                        :checked="allSelected"
                        @click.stop="toggleAll"
                        :title="$gettext('Alle Wiedergabelisten auswählen')"
                    >
                </th>
                <th>
                    {{ $gettext('Name') }}
                </th>

                <th>
                    {{ $gettext('Veranstaltung') }}
                </th>

                <th>
                    {{ $gettext('Semester') }}
                </th>

                <th>
                    {{ $gettext('Videos') }}
                </th>
                <th>
                    {{ $gettext('Erstellt am') }}
                </th>
                <th v-if="showActions"></th>
            </tr>
            </thead>
            <tbody v-if="Object.keys(playlists).length === 0 && axios_running" class="oc--episode-list oc--episode-list--empty">
            <EmptyPlaylistCard />
            </tbody>
            <tbody class="oc--playlist" v-else>
            <PlaylistCard
                v-for="playlist in playlists"
                v-bind:playlist="playlist"
                v-bind:key="playlist.token"
                :showActions="showActions"
                :selectable="selectable"
                :selectedPlaylists="selectedPlaylists"
                @togglePlaylist="togglePlaylist"
                @addToCourse="addToCourse"
                @deletePlaylist="deletePlaylist"
            ></PlaylistCard>
            </tbody>
        </table>

        <PlaylistAddToCourseDialog :title="playlistCourse.title"
           :playlist="playlistCourse"
           @key="playlistCourse.token"
           v-if="playlistCourse"
           @cancel="playlistCourse = null"
           @done="playlistCourse = null"
        />
    </div>
</template>

<script>
import { mapGetters } from "vuex";
import PlaylistCard from '@/components/Playlists/PlaylistCard.vue';
import EmptyPlaylistCard from '@/components/Playlists/EmptyPlaylistCard.vue';
import PlaylistAddToCourseDialog from '@/components/Playlists/PlaylistAddToCourseDialog.vue'
import SearchBar from "@/components/SearchBar.vue";
import PaginationButtons from "@/components/PaginationButtons.vue";
import ApiService from "@/common/api.service";

export default {
    name: "PlaylistsTable",

    components: {
        PlaylistCard,
        EmptyPlaylistCard,
        PlaylistAddToCourseDialog,
        SearchBar,
        PaginationButtons,
    },

    props: {
        cid: {
            type: String,
            default: null,
        },
        showActions: {
            type: Boolean,
            default: true,
        },
        selectable: {
            type: Boolean,
            default: false,
        },
        multiSelect: {
            type: Boolean,
            default: true,
        },
    },

    emits: ['selectedPlaylistsChange'],

    data() {
        return {
            playlists: [],
            playlistsTags: [],
            playlistsCourses: [],
            playlistCourse: null,
            selectedPlaylists: [],
            filters: [],
            limit: 15,
            paging: {
                currPage: 0,
                lastPage: 0,
                items: 0
            },
        }
    },

    computed: {
        ...mapGetters([
            "axios_running",
            "playlistsReload",
        ]),

        isCourse() {
            return this.cid !== null;
        },

        allSelected() {
            return this.playlists.length === this.selectedPlaylists.length;
        },

        offset() {
            return this.paging.currPage * this.limit;
        },
    },

    methods: {
        loadPlaylists() {
            let route = !this.isCourse ? 'playlists' : 'courses/' + this.cid + '/playlists';

            // Add search bar filters
            const params = new URLSearchParams();
            params.append('filters', JSON.stringify(this.filters));
            params.append('offset', this.offset);
            params.append('limit', this.limit);

            if (this.isCourse) {
                params.append('cid', this.cid);
            }

            ApiService.get(route, { params })
                .then(({ data }) => {
                    this.playlists = data.playlists;
                    this.playlistsTags = data.tags;
                    this.playlistsCourses = data.courses;

                    this.updatePaging(data.count);
                });
        },

        doSearch(filters) {
            this.filters = filters.filters;
            this.changePage(0);
        },

        changeLimit(limit) {
            this.limit = limit;
        },

        changePage(page) {
            if (page >= 0 && page <= this.paging.lastPage) {
                this.paging.currPage = page;
            }
            this.loadPlaylists();
        },

        updatePaging(playlistsCount) {
            this.paging.items = playlistsCount;
            this.paging.lastPage = (this.paging.items === this.limit) ? 0 : Math.floor((this.paging.items - 1) / this.limit);
        },

        togglePlaylist(data) {
            if (!this.selectable) return

            if (data.checked === false) {
                let index = this.selectedPlaylists.indexOf(data.token);
                if (index >= 0) {
                    this.selectedPlaylists.splice(index, 1);
                }
            } else {
                if (!this.multiSelect) {
                    this.selectedPlaylists = [];
                }
                this.selectedPlaylists.push(data.token);
            }

            this.$emit('selectedPlaylistsChange', this.selectedPlaylists);
        },

        toggleAll(e) {
            if (this.selectable && this.multiSelect) {
                if (e.target.checked) {
                    // Select all playlist
                    this.selectedPlaylists = this.playlists.map(p => p.token);
                } else {
                    this.selectedPlaylists = [];
                }

                this.$emit('selectedPlaylistsChange', this.selectedPlaylists);
            }
        },

        addToCourse(playlist) {
            this.playlistCourse = playlist;
        },

        deletePlaylist(playlist) {
            let confirm_text = this.$gettext('Sind Sie sicher, dass Sie die komplette Wiedergabeliste löschen möchten?');
            if (playlist?.default_course_tooltip) {
                confirm_text += ' ' + this.$gettext('Bitte beachten Sie, dass diese Wiedergabeliste ist eine') + ' ' + playlist.default_course_tooltip;
            }
            if (confirm(this.$gettext(confirm_text))) {
                this.$store.dispatch('deletePlaylist', playlist.token)
                    .then(() => {
                        this.loadPlaylists();
                    })
                    .catch(() => {
                        this.$store.dispatch('addMessage', {
                            type: 'error',
                            text: this.$gettext('Die Wiedergabeliste konnte nicht gelöscht werden.')
                        });
                    })
            }
        }
    },

    mounted() {
        this.$store.dispatch('setPlaylistsReload', false);
        this.loadPlaylists();
    },

    watch: {
        // Handle reloading Playlists from outside of this component (e.g. used after PlaylistAdd)
        playlistsReload(reload) {
            if (reload) {
                this.loadPlaylists();
                this.$store.dispatch('setPlaylistsReload', false);
            }
        }
    }
};
</script>
