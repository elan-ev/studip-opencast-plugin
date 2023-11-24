<template>
    <div>
        <PlaylistsTable
            :playlists="playlists"
        />

        <MessageBox type="info" v-if="Object.keys(playlists).length === 0 && !addPlaylist">
            <translate>
                Es gibt bisher keine Wiedergabelisten.
            </translate>
        </MessageBox>

        <PlaylistAddNewCard v-if="addPlaylist"
            @done="createPlaylist"
            @cancel="cancelPlaylistAdd"
        />
    </div>
</template>

<script>
import { mapGetters } from "vuex";
import PlaylistsTable from '@/components/Playlists/PlaylistsTable.vue';
import MessageBox from '@/components/MessageBox.vue';
import PlaylistAddNewCard from '@/components/Playlists/PlaylistAddNewCard.vue';

export default {
    name: "Playlists",

    components: {
        PlaylistsTable,
        MessageBox,
        PlaylistAddNewCard,
    },

    computed: {
        ...mapGetters([
            "playlists",
            'addPlaylist'
        ])
    },

    methods: {
        cancelPlaylistAdd() {
            this.$store.dispatch('addPlaylistUI', false);
        },

        createPlaylist(playlist) {
            this.$store.dispatch('addPlaylist', playlist);
        },
    },

    created() {
        this.$store.commit('setPlaylists', {});
    },

    mounted() {
        this.$store.dispatch('loadPlaylists');
    }
};
</script>