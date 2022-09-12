<template>
    <div>
        <h2>Wiedergabelisten</h2>

        <PaginationButtons @changePage="changePage" v-if="Object.keys(playlists).length !== 0"/>

        <div id="episodes" class="oc--flexitem oc--flexepisodelist">
            <table class="default">
                <colgroup>
                    <col style="width: 2%">
                    <col style="width: 50%">
                    <col style="width: 2%">
                    <col style="width: 20%">
                    <col style="width: 13%">
                    <col style="width: 13%">
                    <col style="width: 2%">
                </colgroup>
                <thead>
                    <tr>
                        <th>
                            <input type="checkbox">
                        </th>
                        <th>
                            {{ $gettext('Name') }}
                        </th>

                        <th></th>

                        <th>
                            {{ $gettext('Sichtbarkeit') }}
                        </th>
                        <th>
                            {{ $gettext('Videos') }}
                        </th>
                        <th>
                            {{ $gettext('Erstellt am') }}
                        </th>
                        <th></th>
                    </tr>
                </thead>
                <tbody v-if="Object.keys(playlists).length === 0 && loading" class="oc--episode-list oc--episode-list--empty">
                    <EmptyPlaylistCard />
                </tbody>
                <tbody class="oc--playlist" v-else>
                    <PlaylistCard
                        v-for="playlist in playlists"
                        v-bind:playlist="playlist"
                        v-bind:key="playlist.token"
                        @addToCourse="addToCourse"
                        @deletePlaylist="deletePlaylist"
                    ></PlaylistCard>
                </tbody>
            </table>

            <MessageBox type="info" v-if="Object.keys(playlists).length === 0 && !addPlaylist">
                <translate>
                    Es gibt bisher keine Wiedergabelisten.
                </translate>
            </MessageBox>

             <PlaylistAddCard v-if="addPlaylist"
                @done="createPlaylist"
                @cancel="cancelPlaylistAdd"
            />
        </div>

        <PlaylistAddToCourseDialog :title="playlistCourse.title"
            :playlist="playlistCourse"
            @key="playlistCourse.token"
            v-if="playlistCourse"
            @cancel="playlistCourse = null"
        />
    </div>
</template>

<script>
import { mapGetters } from "vuex";
import PlaylistCard from '../components/Playlists/PlaylistCard.vue';
import EmptyPlaylistCard from '../components/Playlists/EmptyPlaylistCard.vue';
import PlaylistAddCard from '../components/Playlists/PlaylistAddCard.vue';
import PaginationButtons from '@/components/PaginationButtons.vue';
import PlaylistAddToCourseDialog from '@/components/Playlists/PlaylistAddToCourseDialog.vue'
import MessageBox from '@/components/MessageBox.vue';

export default {
    name: "Playlists",

    components: {
        PlaylistCard,       EmptyPlaylistCard,
        PaginationButtons,  MessageBox,
        PlaylistAddCard,    PlaylistAddToCourseDialog
    },

    data() {
        return {
            playlistCourse: null
        }
    },

    computed: {
        ...mapGetters([
            "playlists",
            "currentPlaylist",
            "paging",
            "loading",
            'addPlaylist'
        ]),

        /*
        visVideos() {
            if (this.videos[this.currentPlaylist] === undefined ||
                this.videos[this.currentPlaylist][this.paging.currPage] === undefined) {
                return {};
            }
            return this.videos[this.currentPlaylist][this.paging.currPage]
        }
        */
    },

    methods: {
        changePage: async function(page) {
            await this.$store.dispatch('setPage', page)
            await this.$store.dispatch('loadPlaylists')
        },

        cancelPlaylistAdd() {
            this.$store.dispatch('addPlaylistUI', false);
        },

        createPlaylist(playlist) {
            this.$store.dispatch('addPlaylist', playlist);
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
        this.$store.commit('clearPaging');
        this.$store.dispatch('loadPlaylists');
        this.$store.commit('setPlaylistForVideos', null);
    }
};
</script>