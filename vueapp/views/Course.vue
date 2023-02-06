<template>
    <div class="container" id="app-episodes">
        <Teleport :to="toLayoutName">
            <CoursesSidebar
                @uploadVideo="uploadDialog = true"
                @copyAll="copyAll">
            </CoursesSidebar>
        </Teleport>

        <VideoUpload v-if="uploadDialog"
            @done="uploadDone"
            @cancel="uploadDialog = false"
            :currentUser="currentUser"
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
import VideoUpload from "@/components/Videos/VideoUpload";
import MessageList from "@/components/MessageList";
import VideoCopyToSeminar from '@/components/Videos/Actions/VideoCopyToSeminar.vue';

import { mapGetters } from "vuex";

export default {
    name: "Course",

    components: {
        CoursesSidebar,     VideoUpload,
        MessageList, VideoCopyToSeminar
    },

    computed: {
        ...mapGetters([
            'currentUser',
            'showCourseCopyDialog'
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
            uploadDialog: false
        }
    },

    methods: {
         uploadDone() {
            this.$store.dispatch('addMessage', {
                type: 'info',
                text: this.$gettext('Ihr Video wird nun verarbeitet. Sie erhalten eine Benachrichtigung, sobald die Verarbeitung abgeschlossen ist.')
            });
            this.uploadDialog = false
        },

        copyAll() {
            this.$store.dispatch('toggleCourseCopyDialog', true);
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
    }
};
</script>
