<template>
    <section class="oc--playlist-section">
        <header>
            <div>
            <h2 class="oc--playlist-section__title">{{ playlist.title }}{{ playlist.is_default ? '*' : '' }}</h2>
            <p>{{ playlist.description }}</p>
            </div>
            <a v-if="playlist.videos_count > 5" href="#" class="oc--playlist-section__see-more">{{ $gettext('Mehr entdecken →') }}</a>
        </header>
        <div class="oc--playlist-section__videos">
            <template v-if="isPlaylistLoading(playlist.token)">
                <VideoCardSkeleton v-for="c in Math.min(5, playlist.videos_count)" :key="c" />
            </template>
            <template v-else>
                <VideoCard
                    v-for="video in playlistVideos(playlist.token).slice(0, 5)"
                    :key="video.token"
                    :video="video"
                />
            </template>
        </div>
    </section>
</template>

<script setup>
import VideoCard from './../Videos/VideoCard.vue';
import VideoCardSkeleton from '../Videos/VideoCardSkeleton.vue';
import { useStore } from 'vuex';
const store = useStore();

defineProps({
    playlist: {
        type: Object,
        required: true,
    },
});
const playlistVideos = (token) => {
    return store.getters['videos/playlistVideos'](token);
};
const isPlaylistLoading = (token) => {
    return store.getters['videos/isPlaylistLoading'](token);
};
</script>
