<template>
    <div class="container" id="app-episodes">
        <Teleport :to="toLayoutName">
            <CoursesSidebar
                @uploadVideo="uploadDialog = true"
                @sortVideo="enableSortMode"
                @saveSortVideo="saveSort"
                @cancelSortVideo="cancelSort"
                @editPlaylist="editPlaylistDialog = true"
                @changeDefaultPlaylist="showChangeDefaultPlaylistDialog = true">
            </CoursesSidebar>
        </Teleport>

        <PlaylistAddCard v-if="addPlaylist"
            :is-default="!hasDefaultPlaylist"
            @done="closePlaylistAddDialog"
            @cancel="closePlaylistAddDialog"
        />

        <PlaylistAddVideos v-if="showPlaylistAddVideosDialog"
            :canEdit="canEdit"
            :canUpload="canUpload"
            @done="closePlaylistAddVideosDialog"
            @cancel="closePlaylistAddVideosDialog"
        />

        <PlaylistEditCard v-if="editPlaylistDialog"
            @done="editPlaylistDialog = false"
            @cancel="editPlaylistDialog = false"
        />

        <PlaylistsLinkCard v-if="showChangeDefaultPlaylistDialog"
            :is-default="true"
            :custom-title="$gettext('Kurswiedergabeliste wechseln')"
            @done="showChangeDefaultPlaylistDialog = false"
            @cancel="showChangeDefaultPlaylistDialog = false"
        />

        <MessageList />

        <router-view></router-view>
    </div>
</template>

<script>
import CoursesSidebar from "@/components/Courses/CoursesSidebar";
import PlaylistAddVideos from "@/components/Playlists/PlaylistAddVideos";
import VideoUpload from "@/components/Videos/VideoUpload";
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
        VideoUpload, PlaylistAddVideos,
        MessageList,
    },

    computed: {
        ...mapGetters([
            'currentUser',
            'showPlaylistAddVideosDialog',
            'addPlaylist',
            'cid',
            'course_config'
        ]),

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
            this.$store.dispatch('setVideoSortMode', true)
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

        closePlaylistAddDialog() {
            this.$store.dispatch('addPlaylistUI', false);
        },

    },

    mounted() {
        this.$store.dispatch('loadCurrentUser');
        this.$store.dispatch('loadCourseConfig', this.cid)
        .then((course_config) => {
            if (!course_config?.series?.series_id) {
                this.$store.dispatch('addMessage', {
                    type: 'warning',
                    text: this.$gettext('Die Kurskonfiguration konnte nicht vollständig abgerufen werden, daher ist das Hochladen von Videos momentan nicht möglich.')
                });
            }
        });
    }
};
</script>
