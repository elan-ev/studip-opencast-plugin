<template>
    <div class="container" id="app-episodes">
        <Teleport to="#layout-sidebar > section.sidebar">
            <CoursesSidebar
                @uploadVideo="uploadDialog = true">
            </CoursesSidebar>
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
import CoursesSidebar from "@/components/Courses/CoursesSidebar";
import VideoUpload from "@/components/Videos/VideoUpload";
import MessageList from "@/components/MessageList";

import { mapGetters } from "vuex";

export default {
    name: "Course",

    components: {
        CoursesSidebar,     VideoUpload,
        MessageList
    },

    computed: {
        ...mapGetters([
            'currentUser'
        ])
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
        }
    },

    mounted() {
        this.$store.dispatch('loadCurrentUser');
    }
};
</script>
