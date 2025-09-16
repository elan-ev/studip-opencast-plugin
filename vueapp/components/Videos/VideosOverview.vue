<template>
    <section class="oc--videos-overview">
        <template v-if="isEmpty">
            <EmptyState
                :title="$gettext('Hier ist Platz für spannende Inhalte')"
                :description="
                    $gettext(
                        'Sobald Videos hochgeladen oder aufgezeichnet werden, entsteht hier eine Sammlung spannender Inhalte.'
                    )
                "
            >
                <template v-if="canEdit || canUpload" #buttons>
                    <button class="button add" @click="addVideo('addVideoFromSystem')">
                        {{ $gettext('Video hochladen') }}
                    </button>
                    <button class="button add" @click="addVideo('addVideoFromContents')">
                        {{ $gettext('Aus Arbeitsplatz wählen') }}
                    </button>
                    <button class="button add" @click="addVideo('addVideoFromCourse')">
                        {{ $gettext('Aus Veranstaltung wählen') }}
                    </button>
                </template>
            </EmptyState>
        </template>
        <template v-else>
            <VideoHero v-if="heroVideo" :video="heroVideo" />
            <section class="oc--videos-teasers" v-if="defaultPlaylist">
                <template v-if="isPlaylistLoading(defaultPlaylist.token)">
                    <VideoTeaserSkeleton v-for="n in Math.min(4, defaultPlaylist.videos_count)" :key="n" />
                </template>
                <template v-else>
                    <VideoTeaser v-for="video in teaserVideos" :key="video.token" :video="video" />
                </template>
            </section>
            <section v-if="playlists && nonDefaultPlaylists.length">
                <PlaylistSection v-for="p in nonDefaultPlaylists" :key="p.token" :playlist="p" />
            </section>
        </template>
    </section>
</template>
<script setup>
import { computed, onMounted, watch, getCurrentInstance } from 'vue';
import VideoHero from './VideoHero.vue';
import VideoTeaser from './VideoTeaser.vue';
import VideoTeaserSkeleton from './VideoTeaserSkeleton.vue';
import PlaylistSection from './../Playlists/PlaylistSection.vue';
import EmptyState from '../Layouts/EmptyState.vue';
import { useStore } from 'vuex';

const { proxy } = getCurrentInstance();
const $gettext = proxy.$gettext;
const store = useStore();

const emit = defineEmits(['call-to-action']);

const cid = computed(() => {
    return store.getters['opencast/cid'];
});

const playlists = computed(() => {
    return store.getters['playlists/playlists'];
});

const isEmpty = computed(() => {
    return playlists.value.every((playlist) => playlist.videos_count === 0);
});

const isPlaylistLoading = (token) => {
    return store.getters['videos/isPlaylistLoading'](token);
};

const playlistVideos = (token) => {
    return store.getters['videos/playlistVideos'](token);
};

const defaultPlaylist = computed(() => {
    return playlists.value.find((playlist) => playlist.is_default);
});

const defaultPlaylistVideos = computed(() => {
    if (!defaultPlaylist.value) return [];
    return playlistVideos(defaultPlaylist.value.token);
});

const heroVideo = computed(() => {
    return defaultPlaylistVideos.value.find((video) => video.state === null) || null;
});

const teaserVideos = computed(() => {
    if (!heroVideo.value) return [];
    return defaultPlaylistVideos.value
        .filter((video) => video.state === null && video.token !== heroVideo.value?.token)
        .slice(0, 5);
});

const nonDefaultPlaylists = computed(() => {
    return playlists.value.filter((playlist) => !playlist.is_default);
});

const courseConfig = computed(() => {
    return store.getters['config/course_config'];
});

const canEdit = computed(() => {
    return courseConfig.value?.edit_allowed ?? false;
});

const canUpload = computed(() => {
    return courseConfig.value?.upload_allowed ?? false;
});

const loadAllPlaylistVideos = async () => {
    const loadPromises = playlists.value.map((playlist) => {
        if (playlist.videos_count > 0) {
            return store.dispatch('videos/loadVideosByPlaylist', {
                token: playlist.token,
                order: playlist.sort_order,
            });
        }
        return Promise.resolve();
    });

    await Promise.all(loadPromises);
};

const addVideo = (id) => {
    emit('call-to-action', { id: id });
};

onMounted(async () => {
    // await store.dispatch('opencast/authenticateLti');
    // loadAllPlaylistVideos();
});

watch(playlists, (newValue, oldValue) => {
    loadAllPlaylistVideos();
});
</script>
