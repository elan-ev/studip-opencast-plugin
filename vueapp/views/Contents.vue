<template>
    <div>
        <Teleport to="#layout-sidebar > section.sidebar">
            <VideosSidebar @uploadVideo="uploadDialog = true"></VideosSidebar>
        </Teleport>

        <VideoUpload v-if="uploadDialog"
            @done="uploadDialog = false"
            @cancel="uploadDialog = false"
            :currentUser="currentUser"
        />

        <router-view></router-view>
    </div>
</template>

<script>
import VideosSidebar from "@/components/Videos/VideosSidebar";
import VideoUpload from "@/components/Videos/VideoUpload";
import { mapGetters } from "vuex";

export default {
    name: "Contents",
    components: {
        VideosSidebar, VideoUpload
    },

    computed: {
        ...mapGetters(['currentUser'])
    },

    data() {
        return {
            uploadDialog: false
        }
    },

    mounted() {
        this.$store.dispatch('loadCurrentUser');
    }
};
</script>
