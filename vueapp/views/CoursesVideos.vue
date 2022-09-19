<template>
    <div>
        <h2>Videos</h2>
        <VideosList :filters="getFilters()"></VideosList>
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

    data() {
        return {
            filters: {
                cid: this.cid
            }
        }
    },

    computed: {
         ...mapGetters(['cid'])
    },

    methods: {
        getFilters() {
            return {
                cid: this.cid
            }
        }
    }
};
</script>
