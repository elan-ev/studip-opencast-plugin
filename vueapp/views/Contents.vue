<template>
    <div>
        <Teleport to="#layout-sidebar > section.sidebar">
            <VideosSidebar 
                @uploadVideo="uploadDialog = true"
                @sortVideo="sortDialog = true">
            </VideosSidebar>
        </Teleport>

        <VideoUpload v-if="uploadDialog"
            @done="uploadDialog = false"
            @cancel="uploadDialog = false"
            :currentUser="currentUser"
        />

        <VideoSort v-if="sortDialog"
            @done="sortDialog = false"
            @cancel="sortDialog = false"
        />

        <router-view></router-view>
    </div>
</template>

<script>
import VideosSidebar from "@/components/Videos/VideosSidebar";
import VideoUpload from "@/components/Videos/VideoUpload";
import VideoSort from "@/components/Videos/VideoSort";
import { mapGetters } from "vuex";

export default {
    name: "Contents",
    components: {
        VideosSidebar, VideoUpload, VideoSort
    },

    computed: {
        ...mapGetters(['currentUser'])
    },

    data() {
        return {
            uploadDialog: false,
            sortDialog: false
        }
    },

    mounted() {
        this.$store.dispatch('loadCurrentUser');
    }
};
</script>
