<template>
    <Drawer
        v-if="attachTarget"
        :visible="showDrawer"
        :attachTo="attachTarget"
        side="right"
        width="75%"
        :maxWidth="1200"
        @close="close"
    >
        <article v-if="selectedVideo" class="video-drawer-content">
            <section class="video-player">
                <Tabs :minHeight="510" v-model="tabSelectionVideo">
                    <Tab :name="$gettext('Video')" selected>
                        <iframe
                            v-if="playerUrl"
                            :src="playerUrl"
                            width="100%"
                            height="500"
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
                <section class="video-metadata-infos">
                    <header>
                        {{ selectedVideo.presenters }}
                    </header>
                    <h2>{{ videoTitle }}</h2>
                    <p>{{ selectedVideo.description }}</p>
                    <strong v-if="selectedVideo.contributors !== ''">{{ $gettext('Mitwirkende') }}</strong>
                    <p>{{ selectedVideo.contributors }}</p>
                </section>
                <ul class="video-metadata-status">
                    <li>
                        <StudipIcon shape="visibility-visible" role="info" />
                        <span>{{ selectedVideo.views }} {{ $ngettext('Aufruf', 'Aufrufe', selectedVideo.views) }}</span>
                    </li>
                    <li>
                        <StudipIcon shape="globe" role="info" />
                        <span>{{ $gettext('Dieses Video ist öffentlich') }}</span>
                    </li>
                    <li>
                        <StudipIcon shape="info-circle" role="info" />
                        <span>{{ $gettext('Some infos') }}</span>
                    </li>
                </ul>
            </section>
            <section class="video-settings">
                <Tabs>
                    <Tab selected :name="$gettext('Einstellungen')"> </Tab>
                    <Tab v-if="tabSelectionVideo > 0" :name="$gettext('Einbettungscode')">
                        <h2>{{ $gettext('Einbettungscode') }}</h2>
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
</template>

<script setup>
import { computed, onMounted, ref, watch } from 'vue';
import Drawer from '@components/Layouts/Drawer.vue';
import Tab from '@components/Layouts/Tab.vue';
import Tabs from '@components/Layouts/Tabs.vue';
import StudipIcon from '@studip/StudipIcon.vue';
import { useStore } from 'vuex';
const store = useStore();

const attachTarget = ref(null);
const tabSelectionVideo = ref(0);

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
