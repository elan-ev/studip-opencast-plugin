<template>
    <section class="oc--videos-in-playlist">
        <header class="oc--videos-in-playlist__header">
            <div class="oc--videos-in-playlist__info">
                <p class="oc--videos-in-playlist__title">{{ playlist.title }}</p>
                <div class="oc--tags oc--tags-playlist">
                    <Tag v-for="tag in playlist.tags" :key="tag.id" :tag="tag.tag" />
                </div>
                <p v-if="playlist.description" class="oc--videos-in-playlist__description">
                    {{ playlist.description }}
                </p>
            </div>
            <div class="oc--videos-in-playlist__sub-header">
                <div class="oc--videos-in-playlist__meta">
                    <a href="#" @click.prevent="backToPlaylistOverview" class="oc--link">
                        <StudipIcon shape="arr_1left" :size="16" />
                        <span>{{ $gettext('Zurück zur Übersicht') }}</span>
                    </a>
                    <span v-if="playlist.is_default" class="oc--videos-in-playlist__meta-item">
                        <StudipIcon shape="star" :size="16" role="info" />
                        {{ $gettext('Standard-Wiedergabeliste') }}
                    </span>
                    <span class="oc--videos-in-playlist__meta-item">
                        <StudipIcon shape="video2" :size="16" role="info" />
                        {{ playlistVideoCounter }}
                    </span>
                </div>
                <div class="oc--videos-in-playlist__header-actions">
                    <ActionMenu
                        v-if="canEdit"
                        :items="menuItems"
                        @editPlaylist="showDialog = 'edit'"
                        @setDefaultPlaylist="setDefaultPlaylist"
                        @toggleAllowDownload="(val) => (allowDownload = val)"
                        @removePlaylist="showDialog = 'remove'"
                        @sortPlaylist="showDialog = 'sort'"
                    />
                </div>
            </div>
        </header>
        <PlaylistMetadataDialog
            v-if="showDialog === 'edit'"
            :playlist="playlist"
            @done="closeDialog"
            @cancel="closeDialog"
        />
        <StudipDialog
            v-if="showDialog === 'remove'"
            :title="$gettext('Wiedergabeliste entfernen')"
            :question="$gettext('Möchten Sie Wiedergabeliste unwiderruflich entfernen?')"
            height="200"
            @close="closeDialog"
            @confirm="removePlaylist"
        >
        </StudipDialog>
        <PlaylistSortDialog v-if="showDialog === 'sort'" @close="closeDialog" @done="closeDialog" />
        <section v-if="videos.length > 0" class="oc--videos-all">
            <VideoCard v-for="video in videos" :key="video.token" :video="video" />
        </section>
        <EmptyState
            v-if="videos.length === 0 && searchActive"
            :title="$gettext('Keine Treffer gefunden')"
            :description="$gettext('Die gewählten Filter und Suchbegriffe haben leider keine Treffer ergeben.')"
        >
            <template #buttons>
                <button class="button" @click="emit('reset-search')">
                    {{ $gettext('Suche zurücksetzen') }}
                </button>
            </template>
        </EmptyState>
    </section>
</template>

<script setup>
import VideoCard from './VideoCard.vue';
import ActionMenu from '../Layouts/ActionMenu.vue';
import EmptyState from '../Layouts/EmptyState.vue';
import PlaylistMetadataDialog from '../Playlists/PlaylistMetadataDialog.vue';
import PlaylistSortDialog from '../Playlists/PlaylistSortDialog.vue';
import { useFormat } from '@/composables/useFormat';
import { computed, getCurrentInstance, ref } from 'vue';
import { useStore } from 'vuex';
const { proxy } = getCurrentInstance();
const $gettext = proxy.$gettext;
const $ngettext = proxy.$ngettext;
import StudipIcon from '@studip/StudipIcon';
import StudipDialog from '@studip/StudipDialog.vue';
import Tag from '@/components/Tag.vue';

const store = useStore();
const { formatDuration, formatISODateTime, timeAgo } = useFormat();

const emit = defineEmits(['reset-search']);

const props = defineProps({
    filter: {
        type: Array,
        default: () => {
            return [];
        },
    },
    needle: {
        type: String,
        default: '',
    },
});

const playlistVideos = (token) => {
    return store.getters['videos/playlistVideos'](token);
};

const searchActive = computed(() => {
    return props.filter.length > 0 || props.needle !== '';
});

const videos = computed(() => {
    if ((!props.filter || props.filter.length === 0) && !props.needle) {
        return allVideos.value;
    }

    return allVideos.value.filter((video) => {
        let tagMatch = false;
        if (props.filter.length > 0 && Array.isArray(video.tags)) {
            tagMatch = video.tags.some((vTag) => props.filter.some((fTag) => fTag.id === vTag.id));
        }

        let needleMatch = false;
        if (props.needle) {
            const needle = props.needle.toLowerCase();
            needleMatch =
                video.title?.toLowerCase().includes(needle) ||
                false ||
                video.description?.toLowerCase().includes(needle) ||
                false;
        }

        return tagMatch || needleMatch;
    });
});

const showDialog = ref(null);

const allVideos = computed(() => {
    return playlistVideos(playlist.value.token) || [];
});

const playlist = computed(() => {
    return store.getters['playlists/selectedPlaylist'];
});

const courseConfig = computed(() => {
    return store.getters['config/course_config'];
});

const canEdit = computed(() => {
    return courseConfig.value?.edit_allowed ?? false;
});

const allowDownload = computed({
    get: () => playlist.value?.allow_download ?? false,
    set: (val) => {
        const p = { ...playlist.value, allow_download: val };
        store.dispatch('playlists/setAllowDownloadForPlaylists', p);
    },
});

const menuItems = computed(() => {
    let menuItems = [];

    menuItems.push({
        id: 1,
        label: $gettext('Bearbeiten'),
        icon: 'edit',
        emit: 'editPlaylist',
    });
    if (!playlist.value.is_default) {
        menuItems.push({
            id: 2,
            label: $gettext('Als Standard festlegen'),
            icon: 'star',
            emit: 'setDefaultPlaylist',
        });
    }
    menuItems.push({
        id: 3,
        label: $gettext('Downloads erlauben'),
        icon: 'accept',
        type: 'toggle',
        value: allowDownload.value,
        emit: 'toggleAllowDownload',
    });
    menuItems.push({
        id: 4,
        label: $gettext('Videos sortieren'),
        icon: 'arr_1sort',
        emit: 'sortPlaylist',
    });

    if (!playlist.value.is_default) {
        menuItems.push({
            id: 5,
            label: $gettext('Löschen'),
            icon: 'trash',
            emit: 'removePlaylist',
        });
    }
    return menuItems;
});

const playlistVideoCounter = computed(() => {
    const count = playlist.value.videos_count;

    return `${count} ${$ngettext('Video', 'Videos', count)}`;
});

const backToPlaylistOverview = () => {
    store.dispatch('playlists/setSelectedPlaylist', null);
};

const setDefaultPlaylist = async () => {
    const params = {
        course: store.getters['opencast/cid'],
        token: playlist.value.token,
        playlist: { ...playlist.value, is_default: true },
    };
    await store.dispatch('playlists/updatePlaylistOfCourse', params);
    store.dispatch('playlists/loadPlaylists');
};

const closeDialog = () => {
    showDialog.value = null;
};
const removePlaylist = async () => {
    closeDialog();
    const params = {
        course: store.getters['opencast/cid'],
        token: playlist.value.token,
    };

    await store.dispatch('playlists/removePlaylistFromCourse', params);
    store.dispatch('playlists/loadPlaylists');
    backToPlaylistOverview();
};
</script>
