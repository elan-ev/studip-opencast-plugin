<template>
    <div v-if="playlist">
        <h2>
            {{ playlist.title }}
            <StudipIcon shape="edit" role="clickable" />

            <PlaylistVisibility css="oc--playlist-visibility" :visibility="playlist.visibility"/>
        </h2>

        <PlaylistTags :playlist="playlist" @update="updateTags"/>
    </div>
</template>

<script>
import StudipIcon from '@studip/StudipIcon.vue';

import PlaylistTags from '@/components/Playlists/PlaylistTags.vue';
import PlaylistVisibility from '@/components/Playlists/PlaylistVisibility.vue';

import { mapGetters } from "vuex";

export default {
    name: "PlaylistEdit",

    props:['token'],

    components: {
        StudipIcon, PlaylistTags, PlaylistVisibility
    },

    computed: {
        ...mapGetters(['playlists']),

        playlist() {
            return this.playlists[this.token];
        }
    },

    mounted() {
        if (!this.playlists[this.token]) {
            this.$store.dispatch('loadPlaylist', this.token);
        }
        console.log(this.playlists);
    },

    methods: {
        updateTags(newTags) {
            // TODO: Tags zurück in die DB schreiben
            this.playlist.tags = newTags;
        }
    }

}
</script>
