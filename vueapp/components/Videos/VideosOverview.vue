<template>
    <section class="oc--videos-overview">
        <header class="oc--videos-overview-teaser"></header>
        <ul class="oc--videos-overview-playlists">
            <li v-for="p in playlists" :key="p.index">
                <h2>{{ p.title }}{{ p.is_default ? '*' : '' }}</h2>
                <h3>Videos: {{ p.videos_count }}</h3>
                <p>Beschreibung: {{ p.description || '...' }}</p>
            </li>
        </ul>
    </section>
</template>
<script setup>
import { computed, onMounted, watch } from 'vue';
import { useStore } from 'vuex';

const store = useStore();

const cid = computed(() => {
    return store.getters['opencast/cid'];
});

const playlists = computed(() => {
    return store.getters['playlists/playlists'];
});

const videos = computed(() => {
    return store.getters['videos/videos'];
});

const defaultPlaylist = computed(() => {
    return playlists.value.filter((playlist) => playlist.is_default)[0];
});

const loadFirstPlaylistVideos = (playlist) => {
    store.dispatch('videos/loadPlaylistVideos', {
        filters: [],
        offset: 0,
        order: playlist.sort_order,
        cid: cid.value,
        token: playlist.token,
        limit: -1,
    });
};
const loadAllPlaylistVideos = () => {
    playlists.value.forEach((playlist) => {
        console.log(playlist);
        loadFirstPlaylistVideos(playlist);
    });
};

onMounted(async() => {
    // await store.dispatch('opencast/authenticateLti');
    // loadAllPlaylistVideos();
});

watch(playlists, (newValue, oldValue) => {
    loadAllPlaylistVideos();
});
</script>
