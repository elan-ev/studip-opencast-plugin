<template>
    <section v-if="!loading" class="oc--videos-overview">
        <template v-if="isEmpty || allRunning">
            <EmptyState
                v-if="isEmpty"
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
            <EmptyState
                v-if="allRunning"
                :title="$gettext('Videos werden verarbeitet')"
                :description="
                    $gettext(
                        'Die hochgeladenen Videos werden aktuell verarbeitet. Sobald der Vorgang abgeschlossen ist, erscheinen sie hier in der Übersicht.'
                    )
                "
            >
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
    <div v-else>
        <StudipProgressIndicator
            v-if="showLoadingIndicator"
            class="oc--videos-overview-loading"
            :description="$gettext('Lade Videos...')"
            :size="64"
        />
    </div>
</template>
<script setup>
import { computed, onMounted, watch, getCurrentInstance, ref } from 'vue';
import VideoHero from './VideoHero.vue';
import VideoTeaser from './VideoTeaser.vue';
import VideoTeaserSkeleton from './VideoTeaserSkeleton.vue';
import PlaylistSection from './../Playlists/PlaylistSection.vue';
import EmptyState from '../Layouts/EmptyState.vue';
import StudipProgressIndicator from '@studip/StudipProgressIndicator.vue';
import { useStore } from 'vuex';

const { proxy } = getCurrentInstance();
const $gettext = proxy.$gettext;
const store = useStore();

const emit = defineEmits(['call-to-action']);

const loading = ref(false);
const showLoadingIndicator = ref(false);
let timeoutId = null;

const cid = computed(() => {
    return store.getters['opencast/cid'];
});

const playlists = computed(() => {
    return store.getters['playlists/playlists'];
});

const allRunning = computed(() => {
    const videos = globalVideos.value;
    return videos?.length > 0 && videos.every((video) => video.state === 'running');
});

const globalVideos = computed(() => {
    return store.getters['videos/globalVideos'];
});

const isEmpty = computed(() => {
    if (globalVideos.value === null) {
        return false;
    }

    return globalVideos.value.length === 0;
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
    loading.value = true;
    const loadPromises = playlists.value.map((playlist) => {
        if (playlist.videos_count > 0) {
            return store.dispatch('videos/loadVideosByPlaylist', {
                token: playlist.token,
                playlist: playlist
            });
        }
        return Promise.resolve();
    });

    await Promise.all(loadPromises);
    loading.value = false;
};

const addVideo = (id) => {
    emit('call-to-action', { id: id });
};

onMounted(async () => {
    loading.value = true;
    await store.dispatch('opencast/authenticateLti');
    loadAllPlaylistVideos();
});

watch(playlists, (newValue, oldValue) => {
    loadAllPlaylistVideos();
});
watch(loading, (newValue, oldValue) => {
    if (newValue && !oldValue) {
        timeoutId = setTimeout(() => {
            showLoadingIndicator.value = true;
        }, 800);
    }
    if (!newValue) {
        if (timeoutId) {
            clearTimeout(timeoutId);
            timeoutId = null;
        }
        showLoadingIndicator.value = false;
    }
});
watch(showLoadingIndicator, (newValue, oldValue) => {
    if (newValue) {
        console.log('showLoadingIndicator active');
    }
});
</script>
