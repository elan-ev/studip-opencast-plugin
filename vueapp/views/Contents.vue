<template>
    <div>
        <Teleport :to="toLayoutName">
            <VideosSidebar
                @uploadVideo="uploadDialog = true"
                @allowDownloadForPlaylist="allowDownload"
                @disallowDownloadForPlaylist="disallowDownload"
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
            'currentUser'
        ]),

        toLayoutName() {
            if (window.OpencastPlugin.STUDIP_VERSION >= 5.3) {
                return "#sidebar";
            }
            else {
                return "#layout-sidebar > section.sidebar";
            }
        }
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
        },

        allowDownload() {
            this.$store.dispatch('setAllowDownloadForPlaylist', true)
        },

        disallowDownload() {
            this.$store.dispatch('setAllowDownloadForPlaylist', false)
        },

        saveSort() {
            this.$store.dispatch('setVideoSortMode', 'commit')
        },

        cancelSort() {
            this.$store.dispatch('setVideoSortMode', false)
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
