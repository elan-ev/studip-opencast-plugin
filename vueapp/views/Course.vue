<template>
    <div class="container" id="app-episodes">
        <Teleport :to="toLayoutName">
            <CoursesSidebar
                @uploadVideo="uploadDialog = true"
                @sortVideo="enableSortMode"
                @saveSortVideo="saveSort"
                @cancelSortVideo="cancelSort"
                @editPlaylist="editPlaylistDialog = true"
                @changeDefaultPlaylist="showChangeDefaultPlaylistDialog = true"
                @copyAll="copyAll"
                @changeDefaultVisibility="changeDefaultVisibility">
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

        <VideoCopyToSeminar v-if="showCourseCopyDialog"
            @done="copyDone"
            @cancel="closeCopyDialog"
        />

        <PlaylistsLinkCard v-if="showChangeDefaultPlaylistDialog"
            :is-default="true"
            :custom-title="$gettext('Kurswiedergabeliste wechseln')"
            @done="showChangeDefaultPlaylistDialog = false"
            @cancel="showChangeDefaultPlaylistDialog = false"
        />

        <EpisodesDefaultVisibilityDialog v-if="showEpisodesDefaultVisibilityDialog"
            @done="closeChangeDefaultVisibility"
            @cancel="closeChangeDefaultVisibility"
        />

        <MessageList />

        <router-view></router-view>
    </div>
</template>

<script>
import CoursesSidebar from "@/components/Courses/CoursesSidebar";
import EpisodesDefaultVisibilityDialog from "@/components/Courses/EpisodesDefaultVisibilityDialog";
import PlaylistAddVideos from "@/components/Playlists/PlaylistAddVideos";
import VideoUpload from "@/components/Videos/VideoUpload";
import MessageList from "@/components/MessageList";
import PlaylistsLinkCard from '@/components/Playlists/PlaylistsLinkCard.vue';
import PlaylistAddCard from '@/components/Playlists/PlaylistAddCard.vue';
import PlaylistEditCard from '@/components/Playlists/PlaylistEditCard.vue';
import VideoCopyToSeminar from '@/components/Videos/Actions/VideoCopyToSeminar.vue';

import { mapGetters } from "vuex";

export default {
    name: "Course",

    components: {
        PlaylistsLinkCard, PlaylistAddCard,
        PlaylistEditCard, CoursesSidebar,
        VideoUpload, PlaylistAddVideos,
        MessageList, VideoCopyToSeminar,
        EpisodesDefaultVisibilityDialog
    },

    computed: {
        ...mapGetters([
            'currentUser',
            'showCourseCopyDialog',
            'showPlaylistAddVideosDialog',
            'addPlaylist',
            'cid',
            'course_config',
            'showEpisodesDefaultVisibilityDialog',
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

        copyAll() {
            this.$store.dispatch('toggleCourseCopyDialog', true);
        },

        closePlaylistAddVideosDialog() {
            this.$store.dispatch('togglePlaylistAddVideosDialog', false);
        },

        closePlaylistAddDialog() {
            this.$store.dispatch('addPlaylistUI', false);
        },

        closeCopyDialog() {
            this.resetCopyParams();
        },

        copyDone() {
            this.resetCopyParams();
        },

        resetCopyParams() {
            this.$store.dispatch('toggleCourseCopyDialog', false);
            this.$store.dispatch('setCourseVideosToCopy', []);
        },

        changeDefaultVisibility() {
            this.$store.dispatch('toggleShowEpisodesDefaultVisibilityDialog', true);
        },

        closeChangeDefaultVisibility() {
            this.$store.dispatch('toggleShowEpisodesDefaultVisibilityDialog', false);
        }
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
