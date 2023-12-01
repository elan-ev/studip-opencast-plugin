<template>
    <div>
        <SearchBar
            :availableTags="playlistsTags"
            :availableCourses="playlistsCourses"
            @search="doSearch"
        />

        <table class="default">
            <colgroup>
                <col v-if="selectable" style="width: 2%">
                <col style="width: 50%">
                <col style="width: 2%">
                <col style="width: 20%">
                <!--
                    <col style="width: 13%">
                -->
                <col style="width: 13%">
                <col v-if="showActions" style="width: 2%">
            </colgroup>
            <thead>
            <tr>
                <th v-if="selectable">
                    <input
                        type="checkbox"
                        :checked="allSelected"
                        @click.stop="toggleAll"
                        :title="$gettext('Alle Wiedergabelisten auswählen')"
                    >
                </th>
                <th>
                    {{ $gettext('Name') }}
                </th>

                <th></th>

                <!--
                <th>
                    {{ $gettext('Sichtbarkeit') }}
                </th>
                -->

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
import ApiService from "@/common/api.service";

export default {
    name: "PlaylistsTable",

    components: {
        PlaylistCard,
        EmptyPlaylistCard,
        PlaylistAddToCourseDialog,
        SearchBar
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
        }
    },

    computed: {
        ...mapGetters([
            "axios_running",
        ]),

        isCourse() {
            return this.cid !== null;
        },

        allSelected() {
            return this.playlists.length === this.selectedPlaylists.length;
        }
    },

    methods: {
        loadPlaylists() {
            let route = !this.isCourse ? 'playlists' : 'courses/' + this.cid + '/playlists';

            // Add search bar filters
            const params = new URLSearchParams();
            params.append('filters', JSON.stringify(this.filters));

            if (this.isCourse) {
                params.append('cid', this.cid);
            }

            ApiService.get(route, { params })
                .then(({ data }) => {
                    this.playlists = data.playlists;
                    this.playlistsTags = data.tags;
                    this.playlistsCourses = data.courses;
                });
        },

        doSearch(filters) {
            this.filters = filters.filters;
            this.loadPlaylists();
        },

        togglePlaylist(data) {
            if (data.checked === false) {
                let index = this.selectedPlaylists.indexOf(data.token);
                if (index >= 0) {
                    this.selectedPlaylists.splice(index, 1);
                }
            } else {
                this.selectedPlaylists.push(data.token);
            }

            this.$emit('selectedPlaylistsChange', this.selectedPlaylists);
        },

        toggleAll(e) {
            if (e.target.checked) {
                // Select all playlist
                this.selectedPlaylists = this.playlists.map(p => p.token);
            } else {
                this.selectedPlaylists = [];
            }

            this.$emit('selectedPlaylistsChange', this.selectedPlaylists);
        },

        addToCourse(playlist) {
            this.playlistCourse = playlist;
        },

        deletePlaylist(playlist) {
            if (confirm(this.$gettext('Sind sie sicher, dass sie die komplette Wiedergabeliste löschen möchten?'))) {
                this.$store.dispatch('deletePlaylist', playlist.token)
                    .then(() => {
                        this.$store.dispatch('loadPlaylists');
                    })
            }
        }
    },

    mounted() {
        this.loadPlaylists();
    }
};
</script>