<template>
    <div>
        <h2>Videos</h2>
        <VideosList></VideosList>
    </div>
</template>

<script>
import { mapGetters } from "vuex";
import VideosList from "@/components/Videos/VideosList";


export default {
    name: "CourseVideos",
    components: {
        VideosList
    },

    computed: {
        ...mapGetters(['cid', 'defaultPlaylist'])
    },

    mounted() {
        this.$store.dispatch('loadPlaylists').
            then(() => {
                this.$store.dispatch('setPlaylist', this.defaultPlaylist);
                this.$store.dispatch('setDefaultSortOrder', this.defaultPlaylist);
            });
    },
};
</script>
