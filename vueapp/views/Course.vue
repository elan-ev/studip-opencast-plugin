<template>
    <div class="container" id="app-episodes">
        <MessageList />
        <router-view></router-view>
    </div>
</template>

<script>
import CoursesSidebar from "@/components/Courses/CoursesSidebar";
import EpisodesDefaultVisibilityDialog from "@/components/Courses/EpisodesDefaultVisibilityDialog";
import PlaylistAddVideos from "@/components/Playlists/PlaylistAddVideos";
import MessageList from "@/components/MessageList";
import PlaylistsLinkCard from '@/components/Playlists/PlaylistsLinkCard.vue';
import PlaylistAddCard from '@/components/Playlists/PlaylistAddCard.vue';
import PlaylistEditCard from '@/components/Playlists/PlaylistEditCard.vue';

import { mapGetters } from "vuex";

export default {
    name: "Course",

    components: {
        PlaylistsLinkCard, PlaylistAddCard,
        PlaylistEditCard, CoursesSidebar,
        PlaylistAddVideos,
        MessageList,
        EpisodesDefaultVisibilityDialog
    },

    computed: {
        ...mapGetters('opencast', ['cid', 'currentUser']),
        ...mapGetters('playlists', ['addPlaylist', 'showPlaylistAddVideosDialog']),
        ...mapGetters('config', ['course_config']),
        ...mapGetters('videos', ['showEpisodesDefaultVisibilityDialog']),

        canEdit() {
            if (!this.course_config) {
                return false;
            }

            return this.course_config.edit_allowed;
        },

        canUpload() {
            if (!this.course_config || !this.course_config?.series?.series_id) {
                return false;
            }

            return this.course_config.upload_allowed;
        },

        hasDefaultPlaylist() {
            return this.course_config?.has_default_playlist;
        },

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
            editPlaylistDialog: false,
            showChangeDefaultPlaylistDialog: false,
        }
    },

    methods: {
        enableSortMode() {
            this.$store.dispatch('videos/setVideoSortMode', true)
        },

        saveSort() {
            this.$store.dispatch('videos/setVideoSortMode', 'commit')
        },

        cancelSort() {
            this.$store.dispatch('videos/setVideoSortMode', 'cancel')
        },

        closePlaylistAddVideosDialog() {
            this.$store.dispatch('playlists/togglePlaylistAddVideosDialog', false);
        },

        closePlaylistAddDialog() {
            this.$store.dispatch('playlists/addPlaylistUI', false);
        },

        changeDefaultVisibility() {
            this.$store.dispatch('videos/toggleShowEpisodesDefaultVisibilityDialog', true);
        },

        closeChangeDefaultVisibility() {
            this.$store.dispatch('videos/toggleShowEpisodesDefaultVisibilityDialog', false);
        }
    },

    mounted() {
        this.$store.dispatch('opencast/loadCurrentUser');
        this.$store.dispatch('config/loadCourseConfig', this.cid)
        .then((course_config) => {
            if (!course_config?.series?.series_id) {
                this.$store.dispatch('messages/addMessage', {
                    type: 'warning',
                    text: this.$gettext('Die Kurskonfiguration konnte nicht vollständig abgerufen werden, daher ist das Hochladen von Videos momentan nicht möglich.')
                });
            }
        });
    }
};
</script>
