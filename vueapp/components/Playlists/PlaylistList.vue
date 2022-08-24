<template>
    <div>
        <PaginationButtons @changePage="changePage" v-if="Object.keys(playlists).length !== 0"/>

        <div id="episodes" class="oc--flexitem oc--flexepisodelist">
            <ul v-if="Object.keys(playlists).length === 0 && loading" class="oc--episode-list oc--episode-list--empty">
                <EmptyPlaylistCard />
                <EmptyPlaylistCard />
                <EmptyPlaylistCard />
                <EmptyPlaylistCard />
                <EmptyPlaylistCard />
            </ul>

            <ul v-else-if="Object.keys(playlists).length === 0 && !addPlaylist" class="oc--episode-list oc--episode-list--empty">
                <MessageBox type="info">
                    <translate>
                        Es gibt bisher keine Wiedergabelisten.
                    </translate>
                </MessageBox>
            </ul>

            <ul class="oc--play-list" v-else>
                <PlaylistAddCard @done="addPlaylist" @cancel="cancelPlaylistAdd"/>
                <PlaylistCard
                    v-for="playlist in playlists"
                    v-bind:playlist="playlist"
                    v-bind:key="playlist.id"
                ></PlaylistCard>
            </ul>
        </div>
    </div>
</template>

<script>
import { mapGetters } from "vuex";
import PlaylistCard from './PlaylistCard.vue';
import EmptyPlaylistCard from './EmptyPlaylistCard.vue';
import PlaylistAddCard from './PlaylistAddCard.vue';
import PaginationButtons from '@/components/PaginationButtons.vue';
import MessageBox from '@/components/MessageBox.vue';

export default {
    name: "PlaylistList",

    components: {
        PlaylistCard, EmptyPlaylistCard,
        PaginationButtons, MessageBox, PlaylistAddCard
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
        }
    },

    mounted() {
        this.$store.dispatch('loadPlaylists');
    }
};
</script>