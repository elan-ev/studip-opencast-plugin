<template>
    <div>
        <Drawer
            v-if="attachTarget"
            :wrapperClass="wrapperClass"
            :visible="showDrawer"
            :attachTo="attachTarget"
            side="right"
            @close="close"
        >
            <article v-if="selectedVideo" class="oc--video-drawer-content">
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
                            <img
                                v-if="!playerUrl && selectedVideo.state !== null"
                                :src="preview"
                                class="video-drawer-preview"
                            />
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
                <section class="oc--video-metadata">
                    <header class="oc--video-metadata__header">
                        <div class="oc--video-metadata__header-wrapper">
                            <span class="oc--video-metadata__header__title">{{ videoTitle }}</span>
                            <span class="oc--video-metadata__header__title">{{ videoInfo }}</span>
                            <div class="oc--tags oc--tags-video">
                                <Tag v-for="tag in selectedVideo.tags" v-bind:key="tag.id" :tag="tag.tag" />
                            </div>
                            <div class="oc--video-metadata__owner-row">
                                <img :src="avatarUrl" class="oc--video-card__owner-avatar" />
                                <span class="oc--video-card__owner-name">{{ ownerName }}</span>
                            </div>
                        </div>
                        <ul class="oc--video-metadata__status">
                            <li>
                                <StudipIcon shape="date" role="info" />
                                <span :title="readableDate">{{ timeAgo(selectedVideo.created) }}</span>
                            </li>
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
                    </header>
                    <StudipActionMenu
                        v-if="canEdit"
                        class="video-drawer-menu"
                        :items="menuItems"
                        @performAction="performAction"
                    />
                </section>
                <section class="video-settings">
                    <Tabs :key="selectedVideo.id + '-settings'" :responsive="true">
                        <Tab selected :name="$gettext('Informationen')">
                            <p>{{ selectedVideo.description }}</p>
                            <strong v-if="selectedVideo.presenters !== ''">{{ $gettext('Vortragende') }}</strong>
                            <p>{{ selectedVideo.presenters }}</p>
                            <strong v-if="selectedVideo.contributors !== ''">{{ $gettext('Mitwirkende') }}</strong>
                            <p>{{ selectedVideo.contributors }}</p>
                            <template #footer>
                                <button
                                    v-if="canEdit && selectedVideo.state !== 'running'"
                                    class="button edit"
                                    @click="performAction('VideoEdit')"
                                >
                                    {{ $gettext('Bearbeiten') }}
                                </button>
                            </template>
                        </Tab>
                        <Tab
                            v-if="downloadAllowed && !isLivestream && selectedVideo.state !== 'running'"
                            :name="$gettext('Download')"
                        >
                            <VideoDownload :event="selectedVideo" />
                        </Tab>
                        <Tab v-if="canShare && isPublic" :name="$gettext('Einbettungscode')">
                            <VideoEmbeddingCode :event="selectedVideo" />
                        </Tab>
                        <template v-if="canEdit && selectedVideo.state !== 'running'">
                            <Tab v-if="inCourse" :name="$gettext('Sichtbarkeit')">
                                <VideoVisibility :event="selectedVideo" />
                            </Tab>
                            <Tab :name="$gettext('Verknüpfungen')">
                                <VideoLinkToPlaylists :event="selectedVideo" />
                            </Tab>
                        </template>

                        <Tab v-if="canShare" :name="$gettext('Freigaben')">
                            <VideoAccess :event="selectedVideo" />
                        </Tab>
                        <Tab
                            v-if="!isLivestream && simple_config_list.settings.OPENCAST_ALLOW_TECHNICAL_FEEDBACK"
                            :name="$gettext('Technische Rückmeldung')"
                        >
                            <VideoReport :event="selectedVideo" />
                        </Tab>
                    </Tabs>
                </section>
            </article>
            <section v-else>
                <header>
                    <h1>{{ $gettext('Es wurde kein Video ausgewählt') }}</h1>
                </header>
            </section>
        </Drawer>
        <template v-if="showActionDialog">
            <component :is="actionComponent" @cancel="clearAction" @done="doAfterAction" :event="selectedVideo">
            </component>
        </template>
    </div>
</template>

<script setup>
import { computed, onMounted, onUnmounted, ref, getCurrentInstance } from 'vue';
import Drawer from '@components/Layouts/Drawer.vue';
import Tab from '@components/Layouts/Tab.vue';
import Tabs from '@components/Layouts/Tabs.vue';
import StudipIcon from '@studip/StudipIcon.vue';
import StudipActionMenu from '@studip/StudipActionMenu.vue';
import VideoEmbeddingCode from '@components/Videos/Actions/VideoEmbeddingCode.vue';
import VideoDownload from '@components/Videos/Actions/VideoDownload.vue';
import VideoVisibility from '@components/Videos/Actions/VideoVisibility.vue';
import VideoReport from '@components/Videos/Actions/VideoReport.vue';
import VideoEdit from '@/components/Videos/Actions/VideoEdit.vue';
import VideoCut from '@/components/Videos/Actions/VideoCut.vue';
import VideoRemoveFromPlaylist from '@/components/Videos/Actions/VideoRemoveFromPlaylist.vue';
import Tag from '@/components/Tag.vue';
import { useStore } from 'vuex';
import VideoAccess from './Actions/VideoAccess.vue';
import VideoLinkToPlaylists from './Actions/VideoLinkToPlaylists.vue';
import { useFormat } from '@/composables/useFormat';
import { useAvatar } from '@/composables/useAvatar';

const { formatISODateTime, timeAgo } = useFormat();
const { proxy } = getCurrentInstance();
const $gettext = proxy.$gettext;
const store = useStore();

const attachTarget = ref(null);
const tabSelectionVideo = ref(0);
const showActionDialog = ref(false);
const actionComponent = ref(null);
const componentMap = {
    VideoEdit,
    VideoCut,
    VideoRemoveFromPlaylist,
};
const menuItems = computed(() => {
    let menuItems = [];

    if (!isLivestream.value) {
        menuItems.push({
            id: 1,
            label: $gettext('Videoeditor öffnen'),
            icon: 'video2',
            emit: 'performAction',
            emitArguments: 'VideoCut',
        });
        menuItems.push({
            id: 2,
            label: $gettext('Untertitel bearbeiten'),
            icon: 'accessibility',
            emit: 'performAction',
            emitArguments: 'VideoCut',
        });
    }

    if (canEdit.value) {
        menuItems.push({
            id: 3,
            label: $gettext('Aus Wiedergabeliste entfernen'),
            icon: 'trash',
            emit: 'performAction',
            emitArguments: 'VideoRemoveFromPlaylist',
        });
    }

    return menuItems;
});
const showDrawer = computed(() => {
    return store.getters['videodrawer/showDrawer'];
});
const selectedVideo = computed(() => {
    return store.getters['videodrawer/selectedVideo'];
});
const readableDate = computed(() => {
    return selectedVideo.value ? formatISODateTime(selectedVideo.value.created) : '';
});
const ownerId = computed(() => {
    return selectedVideo.value ? selectedVideo.value.owner.id : null;
});
const ownerName = computed(() => {
    return selectedVideo.value ? selectedVideo.value.owner.fullname : '';
});
const { avatarUrl } = useAvatar(ownerId);
const preview = computed(() => {
    return selectedVideo.value
        ? STUDIP.ABSOLUTE_URI_STUDIP + 'plugins.php/opencastv3/redirect/preview/' + selectedVideo.value.token
        : '';
});
const videoTitle = computed(() => {
    return selectedVideo?.value?.title || '';
});
const videoInfo = computed(() => {
    const state = selectedVideo?.value?.state;
    const stateInfo = {
        running: $gettext('Dieses Video wird gerade von Opencast vearbeitet'),
        failed: $gettext('Dieses Video hatte einen Verarbeitungsfehler'),
        cutting: $gettext('Dieses Video wartet auf den Schnitt'),
    };

    return stateInfo[state] ? `(${stateInfo[state]})` : '';
});
const playerUrl = computed(() => {
    if (!selectedVideo.value) return '';
    return selectedVideo.value.publication?.track_link;
});
const presenterSources = computed(() => {
    if (!selectedVideo.value) return [];
    return extractSources(selectedVideo.value.publication?.downloads?.presenter);
});
const presentationSources = computed(() => {
    if (!selectedVideo.value) return [];
    return extractSources(selectedVideo.value.publication?.downloads?.presentation);
});

const canEdit = computed(() => {
    const perm = selectedVideo.value.perm;

    return perm === 'owner' || perm === 'write';
});
const cid = computed(() => {
    return store.getters['opencast/cid'];
});
const inCourse = computed(() => {
    return cid.value ? true : false;
});

const downloadSetting = computed(() => {
    return store.getters['config/downloadSetting'];
});
const playlist = computed(() => {
    return store.getters['playlists/playlist'];
});
const simple_config_list = computed(() => {
    return store.getters['config/simple_config_list'];
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

const livestream = computed(() => {
    return selectedVideo.value?.livestream ?? null;
});

const isLivestream = computed(() => {
    return livestream.value !== null;
});

const wrapperClass = computed(() => [
    'oc--video-drawer',
    'video-drawer-wrapper',
    isFixed.value ? 'video-drawer-wrapper--fixed' : 'video-drawer-wrapper--absolute',
]);

const isFixed = ref(false);

const updateLayout = () => {
    const header = document.querySelector('#main-header');
    const topBar = document.querySelector('#site-title');
    const responsiveContentBar = document.querySelector('#responsive-contentbar');
    isFixed.value = document.body.classList.contains('fixed');

    if (isFixed.value && topBar) {
        document.documentElement.style.setProperty('--main-header-height', `${topBar.offsetHeight}px`);
    } else if (header) {
        document.documentElement.style.setProperty('--main-header-height', `${header.offsetHeight}px`);
    }

    if (responsiveContentBar) {
        document.documentElement.style.setProperty(
            '--main-header-height',
            `${header.offsetHeight + responsiveContentBar.offsetHeight}px`
        );
    }
};

const updateIsFixed = () => {
    isFixed.value = document.body.classList.contains('fixed');
};

const handleKeydown = (event) => {
    if (event.key === 'Escape') {
        close();
    }
};

onMounted(() => {
    attachTarget.value = document.querySelector('#content-wrapper');
    updateLayout();

    // Body class ändern beobachten (für fixed toggeln)
    const mutationObserver = new MutationObserver(updateLayout);
    mutationObserver.observe(document.body, { attributes: true, attributeFilter: ['class'] });

    // Header Größe beobachten (falls sich Höhe ändert)
    const header = document.querySelector('#main-header');
    const resizeObserver = new ResizeObserver(updateLayout);
    if (header) resizeObserver.observe(header);

    window.addEventListener('keydown', handleKeydown);
});
onUnmounted(() => {
    mutationObserver.disconnect();
    resizeObserver.disconnect();

    window.removeEventListener('keydown', handleKeydown);
});
const close = () => {
    store.dispatch('videodrawer/setShowDrawer', false);
    store.dispatch('videodrawer/setSelectedVideo', null);
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

const performAction = (action) => {
    actionComponent.value = componentMap[action] || null;
    showActionDialog.value = !!actionComponent.value;
};
const clearAction = () => {
    showActionDialog.value = false;
    actionComponent.value = null;
};
const doAfterAction = async (args) => {
    clearAction();
    if (args == 'refresh') {
        close();
        // this.loadVideos(); -> TODO !!!
    }
};
</script>

// Link to Video href="#" @click.prevent="redirectAction(`/video/` + event.token)" target="_blank"
