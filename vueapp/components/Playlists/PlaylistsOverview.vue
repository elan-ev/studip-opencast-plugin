<template>
    <section class="oc--videos-playlists-overview">
        <PlaylistOverviewCard v-for="playlist in playlists" :key="playlist.token" :playlist="playlist" />
    </section>
</template>

<script setup>
import { computed, onMounted, watch } from 'vue';
import PlaylistOverviewCard from './PlaylistOverviewCard.vue';
import { useStore } from 'vuex';

const store = useStore();
const playlists = computed(() => {
    return store.getters['playlists/playlists'];
});
</script>