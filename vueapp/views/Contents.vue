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

        <PlaylistAddVideos v-if="showPlaylistAddVideosDialog"
            :canUpload="!!currentUserSeries"
            @done="closePlaylistAddVideosDialog"
            @cancel="closePlaylistAddVideosDialog"
        />

        <MessageList />

        <router-view></router-view>
    </div>
</template>

<script>
import VideosSidebar from "@/components/Videos/VideosSidebar";
import VideoUpload from "@/components/Videos/VideoUpload";
import PlaylistAddVideos from "@/components/Playlists/PlaylistAddVideos";

import MessageList from "@/components/MessageList";
import { mapGetters } from "vuex";

export default {
    name: "Contents",

    components: {
        VideosSidebar,      VideoUpload,
        PlaylistAddVideos,  MessageList
    },

    computed: {
        ...mapGetters([
            'currentUser',
            'currentUserSeries',
            'showPlaylistAddVideosDialog'
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
            this.$store.dispatch('setVideoSortMode', 'cancel')
        },

        closePlaylistAddVideosDialog() {
            this.$store.dispatch('togglePlaylistAddVideosDialog', false);
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
        this.$store.dispatch('loadCurrentUserSeries')
        .then((series_id) => {
            if (!series_id) {
                this.$store.dispatch('addMessage', {
                    type: 'warning',
                    text: this.$gettext('Die Nutzerkonfiguration konnte nicht vollständig abgerufen werden, daher ist das Hochladen von Videos momentan nicht möglich.')
                });
            }
        });
        this.$store.dispatch('loadPlaylists');
    }
};
</script>
