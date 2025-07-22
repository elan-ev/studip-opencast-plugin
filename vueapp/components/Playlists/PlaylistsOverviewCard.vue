<template>
    <div class="oc--playlist-card-wrapper">
        <button class="oc--playlist-card" @click="selectPlaylist">
            <div class="oc--playlist-card__thumbnail">
                <PlaylistThumbnailStack :videos="playlistVideos(playlist.token)" />
            </div>
            <div class="oc--playlist-card__info">
                <span class="oc--playlist-card__title">
                    {{ playlist.title }}
                    <StudipIcon
                        v-if="isDefault"
                        shape="star"
                        role="info"
                        :title="$gettext('Standard-Wiedergabeliste')"
                    />
                </span>
                <div class="oc--tags oc--tags-playlist">
                    <Tag v-for="tag in playlist.tags" :key="tag.id" :tag="tag.tag" />
                </div>
                <p class="oc--playlist-card__description">{{ playlist.description }}</p>
                <div class="oc--playlist-card__meta">
                    {{ playlist.videos_count }} {{ $ngettext('Video', 'Videos', playlist.videos_count) }}
                </div>
            </div>
        </button>
        <PlaylistMetadataDialog
            v-if="showEditDialog"
            :playlist="playlist"
            @done="closeEditDialog"
            @cancel="closeEditDialog"
        />
        <div class="oc--playlist-card__actions">
            <ActionMenu
                :items="menuItems"
                @editPlaylist="showEditDialog = true"
                @toggleAllowDownload="(val) => (allowDownload = val)"
                @setDefaultPlaylist="setDefaultPlaylist"
            />
        </div>
    </div>
</template>

<script setup>
import { computed, getCurrentInstance, ref } from 'vue';
import PlaylistThumbnailStack from './PlaylistThumbnailStack.vue';
import ActionMenu from '../Layouts/ActionMenu.vue';
import StudipIcon from '@studip/StudipIcon.vue';
import Tag from '@/components/Tag.vue';
import { useStore } from 'vuex';
import PlaylistMetadataDialog from './PlaylistMetadataDialog.vue';
const { proxy } = getCurrentInstance();
const $gettext = proxy.$gettext;

const store = useStore();
const props = defineProps({
    playlist: { type: Object, required: true },
});

const showEditDialog = ref(false);

const playlistVideos = (token) => {
    return store.getters['videos/playlistVideos'](token);
};

const isDefault = computed(() => props.playlist.is_default);

const allowDownload = computed({
    get: () => props.playlist.allow_download,
    set: (val) => {
        const playlist = { ...props.playlist, allow_download: val };
        store.dispatch('playlists/setAllowDownloadForPlaylists', playlist);
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
        label: $gettext('Als Standard festlegen'),
        icon: 'star',
        emit: 'setDefaultPlaylist',
    });
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
        label: $gettext('Löschen'),
        icon: 'trash',
        emit: 'deletePlaylist',
    });
    return menuItems;
});

const selectPlaylist = () => {
    store.dispatch('playlists/setSelectedPlaylist', props.playlist);
};

const closeEditDialog = () => {
    showEditDialog.value = false;
};

const setDefaultPlaylist = async () => {
    const params = {
        course: store.getters['opencast/cid'],
        token: props.playlist.token,
        playlist: { ...props.playlist, is_default: true },
    };
    await store.dispatch('playlists/updatePlaylistOfCourse', params);
    store.dispatch('playlists/loadPlaylists');
};
</script>
