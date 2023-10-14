<template>
    <div>
        <VideosTable></VideosTable>
    </div>
</template>

<script>
import { mapGetters } from "vuex";
import VideosTable from "@/components/Videos/VideosTable";


export default {
    name: "CourseVideos",
    components: {
      VideosTable
    },

    computed: {
        ...mapGetters(['cid', 'defaultPlaylist'])
    },

    mounted() {
        this.$store.dispatch('loadPlaylists').then(() => {
            this.$store.dispatch('setPlaylist', this.defaultPlaylist);
        });
    },
};
</script>
