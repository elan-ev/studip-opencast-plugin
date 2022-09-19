<template>
    <div>
        <h2>Videos</h2>
        <VideosList></VideosList>
    </div>
</template>

<script>
import { mapGetters } from "vuex";
import VideosList from "@/components/Videos/VideosList";


export default {
    name: "CourseVideos",
    components: {
        VideosList
    },

    computed: {
         ...mapGetters(['cid'])
    },

    mounted() {
        this.$store.commit('setFilters', {
            cid: this.cid
        })
    }
};
</script>
