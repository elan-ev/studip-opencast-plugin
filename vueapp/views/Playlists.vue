<template>
    <div>
        <PlaylistsTable
            :playlists="playlists"
        />

        <MessageBox type="info" v-if="Object.keys(playlists).length === 0 && !addPlaylist">
            {{ $gettext('Es gibt bisher keine Wiedergabelisten.') }}
        </MessageBox>

        <PlaylistAddNewCard v-if="addPlaylist"
            @done="closePlaylistAdd"
            @cancel="closePlaylistAdd"
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
        closePlaylistAdd() {
            this.$store.dispatch('addPlaylistUI', false);
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