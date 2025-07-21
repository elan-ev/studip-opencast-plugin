<template>
    <section class="oc--videos-in-playlist">
        <header class="oc--videos-in-playlist__header">
            <p class="oc--videos-in-playlist__title">{{ playlist.title }}</p>
            <div class="oc--videos-in-playlist__header-actions">
                <ActionMenu
                    :items="menuItems"
                    @editPlaylist="showEditDialog = true"
                    @toggleAllowDownload="(val) => allowDownload = val"
                />
            </div>
            <PlaylistMetadataDialog
                v-if="showEditDialog"
                :playlist="playlist"
                @done="closeEditDialog"
                @cancel="closeEditDialog"
            />
        </header>

        <nav class="oc--videos-in-playlist__breadcrumb">
            <a href="#" @click.prevent="backToPlaylistOverview" class="back-link"
                >← {{ $gettext('Zurück zur Übersicht') }}</a
            >
        </nav>
        <section class="oc--videos-all">
            <VideoCard v-for="video in playlistVideos(playlist.token)" :key="video.token" :video="video" />
        </section>
    </section>
</template>

<script setup>
import VideoCard from './VideoCard.vue';
import ActionMenu from '../Layouts/ActionMenu.vue';
import PlaylistMetadataDialog from '../Playlists/PlaylistMetadataDialog.vue';
import { computed, getCurrentInstance, ref } from 'vue';
import { useStore } from 'vuex';
const { proxy } = getCurrentInstance();
const $gettext = proxy.$gettext;

const store = useStore();

const playlistVideos = (token) => {
    return store.getters['videos/playlistVideos'](token);
};

const showEditDialog = ref(false);

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
        emit: 'deletePlaylist',
    });

    return menuItems;
});

const backToPlaylistOverview = () => {
    store.dispatch('playlists/setSelectedPlaylist', null);
};

const closeEditDialog = () => {
    showEditDialog.value = false;
};
</script>
