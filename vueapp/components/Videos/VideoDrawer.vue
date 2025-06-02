<template>
    <Drawer
        v-if="attachTarget"
        :visible="showDrawer"
        :attachTo="attachTarget"
        side="right"
        width="75%"
        :maxWidth="900"
        @close="close"
    >
        <article v-if="selectedVideo" class="video-drawer-content">
            <section class="video-player">
                <Tabs :key="selectedVideo.id" v-model="tabSelectionVideo">
                    <Tab :name="$gettext('Video')" selected>
                        <iframe
                            v-if="playerUrl"
                            :src="playerUrl"
                            width="100%"
                            height="460"
                            frameborder="0"
                            allowfullscreen
                            title="Opencast Video Player"
                        ></iframe>
                    </Tab>
                    <Tab v-if="presenterSources" :name="$gettext('Presenter')">
                        <video width="100%" controls>
                            <source
                                v-for="(source, index) in presenterSources"
                                :key="index"
                                :src="source.url"
                                :type="source.type"
                            />
                            {{ $gettext('Dein Browser unterstützt dieses Videoformat nicht.') }}
                        </video>
                    </Tab>
                    <Tab v-if="presentationSources" :name="$gettext('Presentation')">
                        <video width="100%" controls>
                            <source
                                v-for="(source, index) in presentationSources"
                                :key="index"
                                :src="source.url"
                                :type="source.type"
                            />
                            {{ $gettext('Dein Browser unterstützt dieses Videoformat nicht.') }}
                        </video>
                    </Tab>
                </Tabs>
            </section>
            <section class="video-metadata">
                <header>
                    <div class="video-metadata-header-wrapper">
                        <h2>{{ videoTitle }}</h2>
                        <h3>{{ selectedVideo.presenters }}</h3>
                        <div class="oc--tags oc--tags-video">
                            <Tag v-for="tag in selectedVideo.tags" v-bind:key="tag.id" :tag="tag.tag" />
                        </div>
                    </div>
                    <ul class="video-metadata-status">
                        <li>
                            <StudipIcon shape="visibility-visible" role="info" />
                            <span
                                >{{ selectedVideo.views }}
                                {{ $ngettext('Aufruf', 'Aufrufe', selectedVideo.views) }}</span
                            >
                        </li>
                        <li v-if="isPublic">
                            <StudipIcon shape="globe" role="info" />
                            <span>{{ $gettext('Dieses Video ist öffentlich') }}</span>
                        </li>
                    </ul>
                    <StudipActionMenu v-if="canEdit" class="video-drawer-menu" :items="menuItems" />
                </header>
            </section>
            <section class="video-settings">
                <Tabs :key="selectedVideo.id + '-settings'">
                    <Tab selected :name="$gettext('Informationen')">
                        <p>{{ selectedVideo.description }}</p>
                        <strong v-if="selectedVideo.contributors !== ''">{{ $gettext('Mitwirkende') }}</strong>
                        <p>{{ selectedVideo.contributors }}</p>
                        <template #footer>
                            <button v-if="canEdit && selectedVideo.state !== 'running'" class="button edit">
                                {{ $gettext('Bearbeiten') }}
                            </button>
                        </template>
                    </Tab>
                    <Tab v-if="downloadAllowed" :name="$gettext('Download')">
                        <VideoDownload :event="selectedVideo" />
                    </Tab>
                    <Tab v-if="canShare && isPublic" :name="$gettext('Einbettungscode')"></Tab>
                    <template v-if="canEdit && selectedVideo.state !== 'running'">
                        <Tab :name="$gettext('Sichtbarkeit')"></Tab>
                        <Tab :name="$gettext('Verknüpfungen')"></Tab>
                    </template>

                    <Tab v-if="canShare" :name="$gettext('Freigaben')"></Tab>
                    <Tab :name="$gettext('Technisches Feedback')"></Tab>
                </Tabs>
            </section>
        </article>
        <section v-else>
            <header>
                <h1>{{ $gettext('Es wurde kein Video ausgewählt') }}</h1>
            </header>
        </section>
    </Drawer>
</template>

<script setup>
import { computed, onMounted, ref, getCurrentInstance } from 'vue';
import Drawer from '@components/Layouts/Drawer.vue';
import Tab from '@components/Layouts/Tab.vue';
import Tabs from '@components/Layouts/Tabs.vue';
import StudipIcon from '@studip/StudipIcon.vue';
import StudipActionMenu from '@studip/StudipActionMenu.vue';
import VideoDownload from '@components/Videos/Actions/VideoDownload.vue';
import Tag from '@/components/Tag.vue';
import { useStore } from 'vuex';

const { proxy } = getCurrentInstance();
const $gettext = proxy.$gettext;

const store = useStore();

const attachTarget = ref(null);
const tabSelectionVideo = ref(0);
const menuItems = ref([
    {
        id: 1,
        label: $gettext('Videoeditor öffnen'),
        icon: 'video2',
        emit: 'performAction',
        emitArguments: 'VideoCut',
    },
    {
        id: 2,
        label: $gettext('Untertitel bearbeiten'),
        icon: 'accessibility',
        emit: 'performAction',
        emitArguments: 'VideoCut',
    },
    {
        id: 3,
        label: $gettext('Aus Wiedergabeliste entfernen'),
        icon: 'trash',
        emit: 'performAction',
        emitArguments: 'VideoRemoveFromPlaylist',
    },
]);

const showDrawer = computed(() => {
    return store.getters.showDrawer;
});
const selectedVideo = computed(() => {
    return store.getters.selectedVideo;
});
const videoTitle = computed(() => {
    return selectedVideo?.value?.title || '';
});
const playerUrl = computed(() => {
    if (!selectedVideo.value) return '';
    return selectedVideo.value.publication.track_link;
});
const presenterSources = computed(() => {
    if (!selectedVideo.value) return [];
    return extractSources(selectedVideo.value.publication.downloads.presenter);
});
const presentationSources = computed(() => {
    if (!selectedVideo.value) return [];
    return extractSources(selectedVideo.value.publication.downloads.presentation);
});

const canEdit = computed(() => {
    const perm = selectedVideo.value.perm;

    return perm === 'owner' || perm === 'write';
});

const downloadSetting = computed(() => {
    return store.getters.downloadSetting;
});
const playlist = computed(() => {
    return store.getters.playlist;
});
const simple_config_list = computed(() => {
    return store.getters.simple_config_list;
});
const downloadAllowed = computed(() => {
    if (downloadSetting.value !== 'never') {
        if (canEdit.value) {
            return true;
        } else if (playlist.value && playlist.value['allow_download'] !== undefined) {
            return playlist.value['allow_download'];
        } else {
            return downloadSetting.value === 'allow';
        }
    }
    return false;
});

const canShare = computed(() => {
    if (
        !simple_config_list.value.settings.OPENCAST_ALLOW_SHARING &&
        !simple_config_list.value.settings.OPENCAST_ALLOW_PUBLIC_SHARING &&
        !simple_config_list.value.settings.OPENCAST_ALLOW_PERMISSION_ASSIGNMENT
    ) {
        return false;
    }

    if (selectedVideo.value.state === 'running') {
        return false;
    }

    return canEdit.value;
});

const isPublic = computed(() => {
    return selectedVideo.value.visibility === 'public';
});

onMounted(() => {
    attachTarget.value = document.querySelector('#content-wrapper');
});
const close = () => {
    store.dispatch('setShowDrawer', false);
    store.dispatch('setSelectedVideo', null);
};

const extractSources = (sourceData) => {
    const entries = Array.isArray(sourceData)
        ? sourceData
        : sourceData && typeof sourceData === 'object'
        ? Object.values(sourceData)
        : [];

    const sources = entries
        .filter((entry) => entry?.url)
        .map((entry) => ({
            url: entry.url,
            type: getMimeType(entry.url),
        }));

    return sources.length > 0 ? sources : null;
};
const getMimeType = (url) => {
    const ext = url.split('.').pop();
    switch (ext) {
        case 'mp4':
            return 'video/mp4';
        case 'webm':
            return 'video/webm';
        case 'ogg':
            return 'video/ogg';
        default:
            return '';
    }
};
</script>

// Link to Video href="#" @click.prevent="redirectAction(`/video/` + event.token)" target="_blank"
