<template>
    <div>
        <VideosTable
            v-if="playlist"
            :playlist="playlist"
            :cid="cid"
            :editable="canEdit"
        />
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
        ...mapGetters(['playlist', 'cid', 'defaultPlaylist', 'course_config']),

        canEdit() {
            return this.course_config?.edit_allowed ?? false;
        },
    },

    mounted() {
        this.$store.dispatch('loadPlaylists').then(() => {
            this.$store.dispatch('setPlaylist', this.defaultPlaylist);
        });
    },
};
</script>
