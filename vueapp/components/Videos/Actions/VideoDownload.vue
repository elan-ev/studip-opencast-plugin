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
                        <StudipButton @click.prevent="downloadFile(media)">
                            {{ getMediaText(media) }}
                        </StudipButton>
                    </a>
                    <template v-if="event.visibility == 'public'">
                        <br>
                        <label>
                            {{ $gettext('Link zur Mediendatei') }}
                            <input type="text" style="width: 100%"
                                v-for="(media, index) in presenters"
                                :key="index" :value="media.url"
                            >
                        </label>
                    </template>
                </div>
                <br>
                <div v-if="presentations.length">
                    <h2>
                        Bildschirm
                    </h2>
                    <a v-for="(media, index) in presentations" :key="index">
                        <StudipButton @click.prevent="downloadFile(media)">
                            {{ getMediaText(media) }}
                        </StudipButton>
                    </a>
                    <template v-if="event.visibility == 'public'">
                    <br>
                    <label>
                        {{ $gettext('Link zur Mediendatei') }}
                        <input type="text" style="width: 100%"
                            v-for="(media, index) in presentations"
                            :key="index" :value="media.url"
                        >
                    </label>
                    </template>
                </div>
            </template>
        </StudipDialog>
    </div>
</template>

<script>
import StudipDialog from '@studip/StudipDialog'
import StudipButton from '@studip/StudipButton'

import axios from "@/common/axios.service";

export default {
    name: 'VideoDownload',

    components: {
        StudipDialog,
        StudipButton
    },

    props: ['event'],

    data() {
        return {
            presentations: [],
            presenters: []
        }
    },

    methods: {
        async downloadFile(media) {
            axios.get(media.url, {
                crossDomain: true,
                withCredentials: true,
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
        }
    },

    mounted() {
        this.extractDownloads();
    }

}
</script>
