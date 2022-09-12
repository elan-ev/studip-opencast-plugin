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
            @done="uploadDone"
            @cancel="uploadDialog = false"
            :currentUser="currentUser"
        />

        <MessageList />

        <router-view></router-view>
    </div>
</template>

<script>
import VideosSidebar from "@/components/Videos/VideosSidebar";
import VideoUpload from "@/components/Videos/VideoUpload";
import MessageList from "@/components/MessageList";

import { mapGetters } from "vuex";

export default {
    name: "Contents",

    components: {
        VideosSidebar,      VideoUpload,
        MessageList
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
        },

        uploadDone() {
            this.$store.dispatch('addMessage', {
                type: 'info',
                text: this.$gettext('Ihr Video wird nun verarbeitet. Sie erhalten eine Benachrichtigung, sobald die Verarbeitung abgeschlossen ist.')
            });
            this.uploadDialog = false
        }

    },

    mounted() {
        this.$store.dispatch('loadCurrentUser');
    }
};
</script>
