<template>
    <div class="scrolling-component" ref="scrollComponent">
        <div v-if="Object.keys(videos).length" class="oc--episode-list">
            <VideoCard
                v-for="event in videos"
                v-bind:event="event"
                v-bind:key="event.id"
            ></VideoCard>
        </div>
        <div v-else class="oc--episode-list">
            <EmptyVideoCard />
            <EmptyVideoCard />
            <EmptyVideoCard />
            <EmptyVideoCard />
            <EmptyVideoCard />
        </div>
    </div>
</template>

<script>
import { mapGetters } from "vuex";
import VideoCard from './VideoCard.vue';
import EmptyVideoCard from './EmptyVideoCard.vue';

export default {
    name: "VideosList",

    components: {
        VideoCard, EmptyVideoCard
    },

    computed: {
        ...mapGetters(["videos"]),
    },

    mounted() {
        this.$store.dispatch('loadVideos');
    }
};
</script>
