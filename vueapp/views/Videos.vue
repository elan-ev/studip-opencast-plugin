<template>
    <div class="container" id="app-videos">
        <p>Videos</p>
        <VideosList></VideosList>
    </div>
</template>

<script>
import { mapGetters } from "vuex";
import VideosList from "@/components/Videos/VideosList";

export default {
    name: "Videos",
    components: {
        VideosList,
    },
};
</script>
