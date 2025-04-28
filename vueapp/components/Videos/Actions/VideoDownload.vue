<template>
    <div>
        <StudipDialog
            :title="$gettext('Medien herunterladen')"
            :closeText="$gettext('Abbrechen')"
            :closeClass="'cancel'"
            height="500"
            @close="this.$emit('cancel')"
        >
            <template v-slot:dialogContent>
                <div class="oc--download-list-container">
                    <div class="oc--download-list">
                        <div v-if="presenters.length">
                            <h2>
                                ReferentIn
                            </h2>
                            <VideoDownloadItem
                                v-for="(media, index) in tuned_presenters"
                                :key="index"
                                :media="media"
                                :event="event"
                                @performDownload="downloadFile(media, 'presenter', media.size)"
                                @performAbortDownload="abortDownload(media)"
                                @performCopyToClipboard="copyToClipboard(media.url)"
                            />
                        </div>
                        <br>
                        <div v-if="presentations.length">
                            <h2>
                                Bildschirm
                            </h2>
                            <VideoDownloadItem
                                v-for="(media, index) in tuned_presentations"
                                :key="index"
                                :media="media"
                                :event="event"
                                @performDownload="downloadFile(media, 'presentation', media.size)"
                                @performAbortDownload="abortDownload(media)"
                                @performCopyToClipboard="copyToClipboard(media.url)"
                            />
                        </div>
                    </div>
                    <div class="oc--download-messages">
                        <MessageList :float="true" :dialog="true" />
                    </div>
                </div>
            </template>
        </StudipDialog>
    </div>
</template>

<script>
import StudipDialog from '@studip/StudipDialog'
import StudipButton from '@studip/StudipButton'
import StudipIcon from '@studip/StudipIcon';
import VideoDownloadItem from '@/components/Videos/Actions/VideoDownloadItem.vue';
import MessageList from "@/components/MessageList";

import axios from "@/common/axios.service";

export default {
    name: 'VideoDownload',

    components: {
        StudipDialog,
        StudipButton,
        StudipIcon,
        VideoDownloadItem,
        MessageList
    },

    props: ['event'],

    data() {
        return {
            presentations: [],
            presenters: [],
            copied: null
        }
    },

    computed: {
        tuned_presentations() {
            let tuned = this.presentations.map((media) => {
                media.loading = false;
                media.download_controller = new AbortController();
                media.progress = 0;
                return media;
            });
            return tuned
        },

        tuned_presenters() {
            let tuned = this.presenters.map((media) => {
                media.loading = false;
                media.download_controller = new AbortController();
                media.progress = 0;
                return media;
            });
            return tuned
        }
    },

    methods: {
        abortDownload(media) {
            if (media?.loading == true) {
                media.download_controller.abort();
                media.loading = false;
            }
        },
        async downloadFile(media, type, index) {
            if (media?.loading == true) {
                return;
            }
            this.$store.dispatch('clearMessages', true);
            let view = this;

            let url = window.OpencastPlugin.REDIRECT_URL + '/download/' + this.event.token + '/' + type + '/' + index;
            media.loading = true;
            media.progress = 0;

            let dummy_download_link = null;

            axios.get(url, {
                responseType: 'blob',
                signal: media.download_controller.signal,
                onDownloadProgress: (progressEvent) => {
                    let percentage = Math.round(
                        (progressEvent.loaded * 100) / index
                    );
                    if (percentage > 100) {
                        percentage = 100;
                    }
                    media.progress = percentage;
                }
            }).then(response => {
                const blob = new Blob([response.data]);
                dummy_download_link = document.createElement('a');
                dummy_download_link.href = URL.createObjectURL(blob);
                dummy_download_link.download = this.getFileName(media);
                dummy_download_link.click();
                view.$store.dispatch('addMessage', {
                    'type': 'success',
                    'text': this.$gettext('Herunterladen des Mediums abgeschlossen.'),
                    'dialog': true
                })

            }).catch((err) => {
                let message = {
                    type: 'error',
                    text: this.$gettext('Herunterladen des Mediums fehlgeschlagen!'),
                    'dialog': true
                }
                if (axios.isCancel(err)) {
                    message.type = 'warning';
                    message.text = this.$gettext('Herunterladen des Mediums abgebrochen!');
                }
                view.$store.dispatch('addMessage', message)
            })
            .finally(() => {
                this.resetMediaDownloadProps(media);
                if (dummy_download_link) {
                    URL.revokeObjectURL(dummy_download_link.href);
                    dummy_download_link.remove();
                    dummy_download_link = null;
                }
            });
        },

        resetMediaDownloadProps(media) {
            media.loading = false;
            media.download_controller = new AbortController();
            media.progress = 0;
        },

        getFileName(media) {
            let res = media.info;
            res = res.replace(' * ', ' x ').replace(/\s+/g, '');
            let ext = media.url.split(".").pop();
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

        copyToClipboard(text)
        {
            navigator.clipboard.writeText(text);
            this.copied = text;
            setTimeout(() => {
                this.copied = '';
            }, 3000);
        }
    },

    mounted() {
        this.extractDownloads();
    }

}
</script>
