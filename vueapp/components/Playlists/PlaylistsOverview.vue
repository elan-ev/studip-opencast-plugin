<template>
    <section v-if="!hasSelectedPlaylist" class="oc--videos-playlists-overview">
        <PlaylistsOverviewCard v-for="playlist in playlists" :key="playlist.token" :playlist="playlist" />
    </section>
    <section v-else class="oc--videos-playlists-playlist">
        <VideosInPlaylist />
    </section>
</template>

<script setup>
import { computed, watch } from 'vue';
import PlaylistsOverviewCard from './PlaylistsOverviewCard.vue';
import VideosInPlaylist from '../Videos/VideosInPlaylist.vue';
import { useStore } from 'vuex';

const store = useStore();
const playlists = computed(() => {
    return store.getters['playlists/playlists'];
});
const hasSelectedPlaylist = computed(() => {
    return store.getters['playlists/hasSelectedPlaylist'];
});
watch(hasSelectedPlaylist, (newVal) => {
    if (newVal) {
        store.dispatch('videos/setSearchAvailable', true);
    } else {
        store.dispatch('videos/setSearchAvailable', false);
    }
});
</script>
