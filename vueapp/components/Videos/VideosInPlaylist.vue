<template>
    <section class="oc--videos-in-playlist">
        <header class="oc--videos-in-playlist__header">
            <div class="oc--videos-in-playlist__info">
                <p class="oc--videos-in-playlist__title">{{ playlist.title }}</p>
                <p v-if="playlist.description" class="oc--videos-in-playlist__description">
                    {{ playlist.description }}
                </p>
            </div>
            <div class="oc--videos-in-playlist__sub-header">
                <div class="oc--videos-in-playlist__meta">
                    <a href="#" @click.prevent="backToPlaylistOverview" class="oc--link">
                        <StudipIcon shape="arr_1left" :size="16" />
                        {{ $gettext('Zurück zur Übersicht') }}
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
                        :items="menuItems"
                        @editPlaylist="showDialog = 'edit'"
                        @toggleAllowDownload="(val) => (allowDownload = val)"
                        @removePlaylist="showDialog = 'remove'"
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
        <section class="oc--videos-all">
            <VideoCard v-for="video in playlistVideos(playlist.token)" :key="video.token" :video="video" />
        </section>
    </section>
</template>

<script setup>
import VideoCard from './VideoCard.vue';
import ActionMenu from '../Layouts/ActionMenu.vue';
import PlaylistMetadataDialog from '../Playlists/PlaylistMetadataDialog.vue';
import { useFormat } from '@/composables/useFormat';
import { computed, getCurrentInstance, ref } from 'vue';
import { useStore } from 'vuex';
const { proxy } = getCurrentInstance();
const $gettext = proxy.$gettext;
const $ngettext = proxy.$ngettext;
import StudipIcon from '@studip/StudipIcon';
import StudipDialog from '@studip/StudipDialog.vue';

const store = useStore();
const { formatDuration, formatISODateTime, timeAgo } = useFormat();

const playlistVideos = (token) => {
    return store.getters['videos/playlistVideos'](token);
};

const showDialog = ref(null);

const playlist = computed(() => {
    return store.getters['playlists/selectedPlaylist'];
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
        label: $gettext('Metadaten bearbeiten'),
        icon: 'edit',
        emit: 'editPlaylist',
    });
    menuItems.push({
        id: 2,
        label: $gettext('Downloads erlauben'),
        icon: 'accept',
        type: 'toggle',
        value: allowDownload.value,
        emit: 'toggleAllowDownload',
    });
    menuItems.push({
        id: 3,
        label: $gettext('Videos sortieren'),
        icon: 'arr_1sort',
        emit: 'sortPlaylist',
    });

    menuItems.push({
        id: 4,
        label: $gettext('Löschen'),
        icon: 'trash',
        emit: 'removePlaylist',
    });

    return menuItems;
});

const playlistVideoCounter = computed(() => {
    const count = playlist.value.videos_count;

    return `${count} ${$ngettext('Video', 'Videos', count)}`;
});

const backToPlaylistOverview = () => {
    store.dispatch('playlists/setSelectedPlaylist', null);
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
