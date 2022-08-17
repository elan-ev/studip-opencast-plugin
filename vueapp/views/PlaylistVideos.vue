<template>
    <div>
        <h2>Wiedergabeliste - {{ playlists[currentPlaylist].title }}</h2>
        <button v-on:click="$router.push('/contents/playlists')">Back button</button>
        <VideosList></VideosList>
    </div>
</template>

<script>
import { mapGetters } from "vuex";
import VideosList from "@/components/Videos/VideosList";

export default {
    name: "Videos",
    components: {
        VideosList
    },

    computed: {
        ...mapGetters([
            "playlists",
            "currentPlaylist"
            ]),
    },
};
</script>