<template>
    <StudipDialog
        :title="$gettext('Videoreihenfolge bearbeiten')"
        :confirmText="$gettext('Speichern')"
        :confirmClass="'accept'"
        :closeText="$gettext('Abbrechen')"
        :closeClass="'cancel'"
        height="800"
        width="600"
        @close="$emit('cancel')"
        @confirm="storeSort"
    >
        <template v-slot:dialogContent>
            <form class="default">
                <label>
                    {{ $gettext('Sortierung') }}
                    <select v-model="sortOrder">
                        <option value="created_desc">{{ $gettext('Erstellungsdatum') }}</option>
                        <option value="order_asc">{{ $gettext('Benutzerdefinierte Sortierung') }}</option>
                    </select>
                </label>
                <label for="playlist-sort">{{ $gettext('Videos') }}</label>
                <draggable
                    id="playlist-sort"
                    v-model="videoList"
                    item-key="id"
                    tag="ol"
                    handle=".oc--playlist-video-sort__item"
                    ghost-class="oc--ghost"
                    class="oc--playlist-video-sort"
                    :disabled="sortOrder === 'created_desc'"
                >
                    <template #item="{ element: video, index }">
                        <li
                            class="oc--playlist-video-sort__item"
                            :class="{ 'oc--playlist-video-sort__item-disabled': sortOrder === 'created_desc' }"
                        >
                            <span>
                                <img class="oc--drag-handle" :src="dragHandle" height="24" />
                            </span>
                            <div class="oc--playlist-video-sort__wrapper">
                                <span class="oc--playlist-video-sort__title">{{ video.title }}</span>
                                <div class="oc--playlist-video-sort__meta">
                                    <span class="oc--playlist-video-sort__creator">{{ video.owner.fullname }}</span>
                                    <span class="oc--playlist-video-sort__duration">
                                        <StudipIcon shape="video" role="info" />
                                        {{ formatDuration(video.duration) }}
                                    </span>
                                    <span class="oc--playlist-video-sort__created">
                                        <StudipIcon shape="date" role="info" />
                                        {{ timeAgo(video.created) }}
                                    </span>
                                </div>
                            </div>
                        </li>
                    </template>
                </draggable>
            </form>
        </template>
    </StudipDialog>
</template>

<script setup>
import { computed, getCurrentInstance, onMounted, ref, watch } from 'vue';
import draggable from 'vuedraggable';
import StudipDialog from '@studip/StudipDialog.vue';
import StudipIcon from '@studip/StudipIcon.vue';
import { useFormat } from '@/composables/useFormat';
import { useStore } from 'vuex';

const store = useStore();
const { formatDuration, formatISODateTime, timeAgo } = useFormat();

const { proxy } = getCurrentInstance();
const $gettext = proxy.$gettext;

const emit = defineEmits(['done']);

const sortOrder = ref('');
const videoList = ref([]);

const dragHandle = window.OpencastPlugin.PLUGIN_ASSET_URL + '/images/grabber_grey.svg';

const playlist = computed(() => {
    return store.getters['playlists/selectedPlaylist'];
});

const videos = computed(() => {
    return playlistVideos(playlist.value.token);
});

const playlistVideos = (token) => {
    return store.getters['videos/playlistVideos'](token);
};

const storeSort = () => {
    if (sortOrder.value === 'order_asc') {
        store.dispatch('playlists/setPlaylistSort', {
            token: playlist.value.token,
            sort: {
                field: 'order',
                order: 'asc',
            },
        });
        store.commit('videos/setVideos', videoList.value);

        store.dispatch('videos/uploadSortPositions', {
            playlist_token: playlist.value.token,
            sortedVideos: videoList.value.map((video) => video.token),
        });
    } else {
        store.dispatch('playlists/setPlaylistSort', {
            token: playlist.value.token,
            sort: {
                field: 'created',
                order: 'desc',
            },
        });
        store.commit('videos/setVideos', videoList.value);
    }

    emit('done');
};

onMounted(() => {
    sortOrder.value = playlist.value.sort_order;
    videoList.value = [...videos.value];
});

watch(sortOrder, (newOrder) => {
    if (newOrder === 'created_desc') {
        videoList.value = [...videos.value].sort((a, b) => new Date(b.created) - new Date(a.created));
    } else if (newOrder === 'order_asc') {
        videoList.value = [...videos.value];
    }
});
</script>
