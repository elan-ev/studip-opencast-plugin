<template>
    <div>
        <Teleport to="#layout-sidebar > section.sidebar">
            <VideosSidebar 
                @uploadVideo="uploadDialog = true"
                @sortVideo="enableSortMode"
                @saveSortVideo="saveSort"
                @cancelSortVideo="cancelSort">
            </VideosSidebar>
        </Teleport>

        <VideoUpload v-if="uploadDialog"
            @done="uploadDialog = false"
            @cancel="uploadDialog = false"
            :currentUser="currentUser"
        />

        <router-view></router-view>
    </div>
</template>

<script>
import VideosSidebar from "@/components/Videos/VideosSidebar";
import VideoUpload from "@/components/Videos/VideoUpload";
import { mapGetters } from "vuex";

export default {
    name: "Contents",
    components: {
        VideosSidebar, VideoUpload
    },

    computed: {
        ...mapGetters([
            'currentUser',
            'currentPlaylist'
        ])
    },

    data() {
        return {
            uploadDialog: false,
            sortDialog: false
        }
    },

    methods: {
        enableSortMode() {
            this.$store.dispatch('setVideoSortMode', true)
            this.$store.dispatch('loadVideos')
        },

        saveSort() {
            this.$store.dispatch('uploadSortPositions', this.currentPlaylist)
            this.$store.dispatch('setVideoSortMode', false)
            this.$store.dispatch('loadVideos')
        },

        cancelSort() {
            this.$store.dispatch('setVideoSortMode', false)
            this.$store.dispatch('loadVideos')
        }
    },

    mounted() {
        this.$store.dispatch('loadCurrentUser');
    }
};
</script>
