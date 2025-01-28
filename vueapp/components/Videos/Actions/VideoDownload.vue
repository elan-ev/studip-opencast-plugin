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
                <div v-if="presenters.length">
                    <h2>
                        ReferentIn
                    </h2>
                    <a v-for="(media, index) in presenters" :key="index">
                        <StudipButton @click.prevent="downloadFile(media, 'presenter', media.size)">
                            {{ getMediaText(media) }}
                        </StudipButton>

                        <div class="oc--tooltip--copy">
                            <div class="oc--tooltip--copy-success"
                                :class="{
                                    'oc--display--block': copied == media.url
                                }"
                            >
                                {{ $gettext('Kopiert!') }}
                            </div>

                            <studip-icon
                                v-if="event.visibility == 'public'"
                                :title="$gettext('Link zur Mediendatei in die Zwischenablage kopieren')"
                                @click="copyToClipboard(media.url)"
                                :shape="copied == media.url ? 'accept' : 'copy'"
                                :role="copied == media.url ? 'status-green' : 'clickable'"
                            />
                        </div>
                    </a>
                </div>
                <br>
                <div v-if="presentations.length">
                    <h2>
                        Bildschirm
                    </h2>
                    <a v-for="(media, index) in presentations" :key="index">
                        <StudipButton @click.prevent="downloadFile(media, 'presentation', media.size)">
                            {{ getMediaText(media) }}
                        </StudipButton>

                        <div class="oc--tooltip--copy">
                            <div class="oc--tooltip--copy-success"
                                :class="{
                                    'oc--display--block': copied == media.url
                                }"
                            >
                                {{ $gettext('Kopiert!') }}
                            </div>

                            <studip-icon
                                v-if="event.visibility == 'public'"
                                :title="$gettext('Link zur Mediendatei in die Zwischenablage kopieren')"
                                @click="copyToClipboard(media.url)"
                                :shape="copied == media.url ? 'accept' : 'copy'"
                                :role="copied == media.url ? 'status-green' : 'clickable'"
                            />
                        </div>
                    </a>
                </div>
            </template>
        </StudipDialog>
    </div>
</template>

<script>
import StudipDialog from '@studip/StudipDialog'
import StudipButton from '@studip/StudipButton'
import StudipIcon from '@studip/StudipIcon';

import axios from "@/common/axios.service";

export default {
    name: 'VideoDownload',

    components: {
        StudipDialog,
        StudipButton,
        StudipIcon
    },

    props: ['event'],

    data() {
        return {
            presentations: [],
            presenters: [],
            copied: null
        }
    },

    methods: {
        async downloadFile(media, type, index) {
            let url = window.OpencastPlugin.REDIRECT_URL + '/download/' + this.event.token + '/' + type + '/' + index;

            axios.get(url, {
                responseType: 'blob'
            }).then(response => {
                const blob = new Blob([response.data]);
                const link = document.createElement('a');
                link.href = URL.createObjectURL(blob);
                link.download = this.getFileName(media);
                link.click();
                URL.revokeObjectURL(link.href);
            }).catch(console.error);
        },

        getFileName(media) {
            let res = media.info;
            res = res.replace(' * ', ' x ').replace(/\s+/g, '');
            let ext = media.url.split(".").pop();
            return this.event.title + ' (' + res + ').' + ext;
        },

        getMediaText(media) {
            var text = media?.info || '';
            text = text.replace(' * ', ' x ');
            var size = media?.size || 0;

            if (size == 0) {
                return text;
            }

            if (size > 1000) {
                size = Math.round(size/1000 * 10) / 10
                text = text + ' (' + size + ' MB)'
            } else {
                size = Math.round(size * 10) / 10
                text = text + ' (' + size + ' KB)'
            }

            return text
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
