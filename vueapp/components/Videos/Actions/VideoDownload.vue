<template>
    <div>
        <StudipDialog
            :title="$gettext('Medien herunterladen')"
            :closeText="$gettext('Schließen')"
            :closeClass="'cancel'"
            height="500"
            width="475"
            @close="this.$emit('cancel')"
        >
            <template #dialogContent>
                <div class="oc--download-list-container">
                    <div class="oc--download-list">
                        <form class="default">
                            <label>
                                {{ $gettext('Videoquelle') }}
                            </label>
                            <label
                                ><input
                                    type="radio"
                                    value="presenter"
                                    v-model="selectedSource"
                                    :disabled="!hasPresenterVideo || downloadInProgress"
                                />
                                {{ $gettext('Aufzeichnung der vortragenden Person') }}</label
                            >
                            <label
                                ><input
                                    type="radio"
                                    value="presentation"
                                    v-model="selectedSource"
                                    :disabled="!hasPresentationVideo || downloadInProgress"
                                />
                                {{ $gettext('Aufzeichnung des Bildschirms') }}</label
                            >
                            <label>
                                {{ $gettext('Videoqualität') }}
                                <select v-model="selectedMedia" :disabled="downloadInProgress">
                                    <template v-if="selectedSource === 'presenter'">
                                        <option v-for="(media, index) in tuned_presenters" :key="index" :value="media">
                                            {{ getMediaText(media) }}
                                        </option>
                                    </template>
                                    <template v-else>
                                        <option
                                            v-for="(media, index) in tuned_presentations"
                                            :key="index"
                                            :value="media"
                                        >
                                            {{ getMediaText(media) }}
                                        </option>
                                    </template>
                                </select>
                            </label>
                        </form>
                        <template v-if="downloadInProgress">
                            <span>{{ $gettext('Video wird heruntergeladen') }}</span>
                            <ProgressBar :progress="selectedMedia.progress" />
                        </template>
                    </div>
                    <div class="oc--download-messages">
                        <MessageList :float="true" :dialog="true" />
                    </div>
                </div>
            </template>
            <template #dialogButtons>
                <button
                    v-if="event.visibility == 'public'"
                    class="button"
                    :title="$gettext('Link zur Mediendatei in die Zwischenablage kopieren')"
                    @click="copyToClipboard()"
                >
                    {{ $gettext('Link kopieren') }}
                </button>
                <button v-if="!downloadInProgress" class="button" @click="downloadFile()">
                    {{ $gettext('Herunterladen') }}
                </button>
                <button v-else class="button" @click="abortDownload()">
                    {{ $gettext('Herunterladen abbrechen') }}
                </button>
            </template>
        </StudipDialog>
    </div>
</template>

<script>
import StudipDialog from '@studip/StudipDialog';
import MessageList from '@/components/MessageList';
import ProgressBar from '@/components/ProgressBar';

import axios from '@/common/axios.service';

export default {
    name: 'VideoDownload',

    components: {
        StudipDialog,
        MessageList,
        ProgressBar,
    },

    props: ['event'],

    data() {
        return {
            presentations: [],
            presenters: [],
            copied: null,
            selectedSource: '',
            selectedMedia: null,
        };
    },

    computed: {
        tuned_presentations() {
            let tuned = this.presentations.map((media) => {
                media.loading = false;
                media.download_controller = new AbortController();
                media.progress = 0;
                return media;
            });
            return tuned;
        },

        tuned_presenters() {
            let tuned = this.presenters.map((media) => {
                media.loading = false;
                media.download_controller = new AbortController();
                media.progress = 0;
                return media;
            });
            return tuned;
        },

        hasPresenterVideo() {
            return this.presenters.length > 0;
        },

        hasPresentationVideo() {
            return this.presentations.length > 0;
        },

        downloadInProgress() {
            return this.selectedMedia?.loading;
        },
    },

    methods: {
        abortDownload() {
            if (this.downloadInProgress) {
                this.selectedMedia.download_controller.abort();
                this.selectedMedia.progress = 0;
                this.selectedMedia.loading = false;
            }
        },
        async downloadFile() {
            const index = this.selectedMedia?.size;
            const type = this.selectedSource;
            if (this.selectedMedia?.loading === true) {
                return;
            }
            this.$store.dispatch('clearMessages', true);
            let view = this;

            let url = window.OpencastPlugin.REDIRECT_URL + '/download/' + this.event.token + '/' + type + '/' + index;
            this.selectedMedia.loading = true;
            this.selectedMedia.progress = 0;

            let dummy_download_link = null;

            axios
                .get(url, {
                    responseType: 'blob',
                    signal: this.selectedMedia.download_controller.signal,
                    onDownloadProgress: (progressEvent) => {
                        let percentage = Math.round((progressEvent.loaded * 100) / index);
                        if (percentage > 100) {
                            percentage = 100;
                        }
                        this.selectedMedia.progress = percentage;
                    },
                })
                .then((response) => {
                    const blob = new Blob([response.data]);
                    dummy_download_link = document.createElement('a');
                    dummy_download_link.href = URL.createObjectURL(blob);
                    dummy_download_link.download = this.getFileName(this.selectedMedia);
                    dummy_download_link.click();
                    view.$store.dispatch('addMessage', {
                        type: 'success',
                        text: this.$gettext('Herunterladen des Mediums abgeschlossen.'),
                        dialog: true,
                    });
                })
                .catch((err) => {
                    let message = {
                        type: 'error',
                        text: this.$gettext('Herunterladen des Mediums fehlgeschlagen!'),
                        dialog: true,
                    };
                    if (axios.isCancel(err)) {
                        message.type = 'warning';
                        message.text = this.$gettext('Herunterladen des Mediums abgebrochen!');
                    }
                    view.$store.dispatch('addMessage', message);
                })
                .finally(() => {
                    this.resetMediaDownloadProps();
                    if (dummy_download_link) {
                        URL.revokeObjectURL(dummy_download_link.href);
                        dummy_download_link.remove();
                        dummy_download_link = null;
                    }
                });
        },

        resetMediaDownloadProps() {
            this.selectedMedia.loading = false;
            this.selectedMedia.download_controller = new AbortController();
            this.selectedMedia.progress = 0;
        },

        getFileName(media) {
            let res = media.info;
            res = res.replace(' * ', ' x ').replace(/\s+/g, '');
            let ext = media.url.split('.').pop();
            return this.event.title + ' (' + res + ').' + ext;
        },

        extractDownloads() {
            let presentations = this.event?.publication?.downloads?.presentation || [];
            for (const size in presentations) {
                let presentation = presentations[size];
                presentation.size = size;
                this.presentations.push(presentation);
            }

            let presenters = this.event?.publication?.downloads?.presenter || [];
            for (const size in presenters) {
                let presenter = presenters[size];
                presenter.size = size;
                this.presenters.push(presenter);
            }
        },

        getMediaText(media) {
            let text = media?.info || '';
            text = text.replace(' * ', ' x ');
            let size = media?.size || 0;

            if (size == 0) {
                return text;
            }

            size = size / 1024;

            if (size > 1024) {
                size = Math.round((size / 1024) * 10) / 10;
                text = text + ' (' + size + ' MB)';
            } else {
                size = Math.round(size * 10) / 10;
                text = text + ' (' + size + ' KB)';
            }

            return text;
        },

        copyToClipboard() {
            const text = this.selectedMedia?.url;
            navigator.clipboard.writeText(text);
            this.$store.dispatch('clearMessages', true);
            let message = {
                type: 'info',
                text: this.$gettext('Link zur Mediendatei wurde in die Zwischenablage kopiert.'),
                dialog: true,
            };
            this.$store.dispatch('addMessage', message);
            setTimeout(() => {
                this.$store.dispatch('clearMessages', true);
            }, 3000);
        },
    },

    mounted() {
        this.extractDownloads();
        if (this.hasPresenterVideo) {
            this.selectedSource = 'presenter';
            this.selectedMedia = this.tuned_presenters[0];
        } else {
            this.selectedSource = 'presentation';
            this.selectedMedia = this.tuned_presentations[0];
        }
    },
};
</script>
