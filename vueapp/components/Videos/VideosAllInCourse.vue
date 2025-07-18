<template>
    <section class="oc--videos-all">
        <VideoCard v-for="video in videos" :key="video.token" :video="video" />
    </section>
</template>

<script setup>
import VideoCard from './VideoCard.vue';
import { computed, onMounted, watch } from 'vue';
import { useStore } from 'vuex';

const store = useStore();

const videos = computed(() => {
    return store.getters['videos/globalVideos'];
});
</script>
