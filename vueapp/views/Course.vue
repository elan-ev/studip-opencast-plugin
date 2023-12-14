<template>
    <div class="container" id="app-episodes">
        <Teleport :to="toLayoutName">
            <CoursesSidebar
                @uploadVideo="uploadDialog = true"
                @sortVideo="enableSortMode"
                @saveSortVideo="saveSort"
                @cancelSortVideo="cancelSort"
                @editPlaylist="editPlaylistDialog = true"
                @copyAll="copyAll">
            </CoursesSidebar>
        </Teleport>

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

        <MessageList />

        <router-view></router-view>
    </div>
</template>

<script>
import CoursesSidebar from "@/components/Courses/CoursesSidebar";
import PlaylistAddVideos from "@/components/Playlists/PlaylistAddVideos";
import VideoUpload from "@/components/Videos/VideoUpload";
import MessageList from "@/components/MessageList";
import PlaylistEditCard from '@/components/Playlists/PlaylistEditCard.vue';
import VideoCopyToSeminar from '@/components/Videos/Actions/VideoCopyToSeminar.vue';

import { mapGetters } from "vuex";

export default {
    name: "Course",

    components: {
        PlaylistEditCard, CoursesSidebar,
        VideoUpload, PlaylistAddVideos,
        MessageList, VideoCopyToSeminar
    },

    computed: {
        ...mapGetters([
            'currentUser',
            'showCourseCopyDialog',
            'showPlaylistAddVideosDialog',
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

        closeCopyDialog() {
            this.resetCopyParams();
        },

        copyDone() {
            this.resetCopyParams();
        },

        resetCopyParams() {
            this.$store.dispatch('toggleCourseCopyDialog', false);
            this.$store.dispatch('setCourseVideosToCopy', []);
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
