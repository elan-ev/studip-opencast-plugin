<template>
    <section class="oc--videos-overview">
        <VideoHero v-if="heroVideo" :video="heroVideo" />
        <section class="oc--videos-teasers" v-if="defaultPlaylist">
            <template v-if="isPlaylistLoading(defaultPlaylist.token)">
                <VideoTeaserSkeleton v-for="n in Math.min(4, defaultPlaylist.videos_count)" :key="n" />
            </template>
            <template v-else>
                <VideoTeaser
                    v-for="video in teaserVideos"
                    :key="video.token"
                    :video="video"
                />
            </template>
        </section>
        <section v-if="playlists && nonDefaultPlaylists.length">
            <PlaylistSection v-for="p in nonDefaultPlaylists" :key="p.token" :playlist="p" />
        </section>
    </section>
</template>
<script setup>
import VideoHero from './VideoHero.vue';
import VideoTeaser from './VideoTeaser.vue';
import VideoTeaserSkeleton from './VideoTeaserSkeleton.vue';
import PlaylistSection from './../Playlists/PlaylistSection.vue';
import { computed, onMounted, watch } from 'vue';
import { useStore } from 'vuex';

const store = useStore();

const cid = computed(() => {
    return store.getters['opencast/cid'];
});

const playlists = computed(() => {
    return store.getters['playlists/playlists'];
});

const isPlaylistLoading = (token) => {
    return store.getters['videos/isPlaylistLoading'](token);
};

const playlistVideos = (token) => {
    return store.getters['videos/playlistVideos'](token);
};

const defaultPlaylist = computed(() => {
    return playlists.value.find(playlist => playlist.is_default);
});

const defaultPlaylistVideos = computed(() => {
    if (!defaultPlaylist.value) return [];
    return playlistVideos(defaultPlaylist.value.token);
});

const heroVideo = computed(() => {
    return defaultPlaylistVideos.value.find(video => video.state === null) || null;
});

const teaserVideos = computed(() => {
    if (!heroVideo.value) return [];
    return defaultPlaylistVideos.value
        .filter(video => video.state === null && video.token !== heroVideo.value?.token)
        .slice(0, 5);
});

const nonDefaultPlaylists = computed(() => {
    return playlists.value.filter(playlist => !playlist.is_default);
});

const loadAllPlaylistVideos = async () => {
    const loadPromises = playlists.value.map((playlist) => {
        if (playlist.videos_count > 0) {
            return store.dispatch('videos/loadVideosByPlaylist', {
                token: playlist.token,
                order: playlist.sort_order
            });
        }
        return Promise.resolve();
    });

    await Promise.all(loadPromises);
};

onMounted(async () => {
    // await store.dispatch('opencast/authenticateLti');
    // loadAllPlaylistVideos();
});

watch(playlists, (newValue, oldValue) => {
    loadAllPlaylistVideos();
});
</script>
