<template>
    <div class="container" id="app-episodes">
        <Teleport to="#layout-sidebar > section.sidebar">
            <CoursesSidebar></CoursesSidebar>
        </Teleport>

        <router-view></router-view>

    </div>
</template>

<script>
import { mapGetters } from "vuex";

import CoursesSidebar from "@/components/Courses/CoursesSidebar";

export default {
    name: "Course",

    components: {
        CoursesSidebar
    },

    methods: {
        uploadVideo() {
            this.uploadDialog = true;
        }
    },

    mounted() {
        this.$store.dispatch('loadCurrentUser');
    }
};
</script>
