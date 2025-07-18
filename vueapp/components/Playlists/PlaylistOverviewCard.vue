<template>
    <div class="oc--playlist-card">
        <div class="oc--playlist-card__thumbnail">
            <PlaylistThumbnailStack :videos="playlistVideos(playlist.token)" />
        </div>
        <div class="oc--playlist-card__info">
            <div class="oc--playlist-card__actions">
                <StudipActionMenu :items="menuItems" @editPlaylist="showEditDialog = true"/>
            </div>
            <h3 class="oc--playlist-card__title">{{ playlist.title }}</h3>
            <div class="oc--tags oc--tags-playlist">
                <Tag v-for="tag in playlist.tags" :key="tag.id" :tag="tag.tag" />
            </div>
            <p class="oc--playlist-card__description">{{ playlist.description }}</p>
            <div class="oc--playlist-card__meta">
                {{ playlist.videos_count }} {{ $ngettext('Video', 'Videos', playlist.videos_count) }}
            </div>
        </div>
        <PlaylistMetadataDialog v-if="showEditDialog" :playlist="playlist" @done="closeEditDialog" @cancel="closeEditDialog"/>
    </div>
</template>

<script setup>
import { computed, getCurrentInstance, ref } from 'vue';
import PlaylistThumbnailStack from './PlaylistThumbnailStack.vue';
import StudipActionMenu from '@studip/StudipActionMenu.vue';
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
        label: $gettext('Löschen'),
        icon: 'trash',
        emit: 'deletePlaylist',
    });

    return menuItems;
});


const closeEditDialog = () => {
    showEditDialog.value = false;
}
</script>
