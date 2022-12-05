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
                        {{ $gettext('ReferentIn') }}
                    </h2>
                    <a v-for="(media, index) in presenters" :key="index"
                            :href="media['url']" target="_blank">
                        <StudipButton>
                            {{ getMediaText(media) }}
                        </StudipButton>
                    </a>
                </div>

                <div v-if="presentations.length">
                    <h2>
                        {{ $gettext('Bildschirm') }}
                    </h2>
                    <a v-for="(media, index) in presentations" :key="index"
                            :href="media['url']" target="_blank">
                        <StudipButton>
                            {{ getMediaText(media) }}
                        </StudipButton>
                    </a>
                </div>

                <div v-if="supplemental.length">
                    <h2>
                        {{ $gettext('Materialien') }}
                    </h2>
                    <a
                        v-for="material in supplemental"
                        v-bind:key="material.url"
                    >
                        <StudipButton>
                            {{ getMediaText(media) }}
                        </StudipButton>
                    </a>
                </div>
            </template>
        </StudipDialog>
    </div>
</template>

<script>
import StudipDialog from '@studip/StudipDialog'
import StudipButton from '@studip/StudipButton'

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
            presenters: [],
            supplemental: []
        }
    },

    methods: {
        getMediaText(media) {

            let text = media?.info || '';
            text = text.replace(' * ', ' x ');
            let size = media?.size || 0;

            if (size > 0) {
                if (size > 1000) {
                    size = Math.round(size / 1000 / 1000 * 10) / 10
                    text = text + ' (' + size + ' MB)'
                } else {
                    size = Math.round(size / 1000 * 10) / 10
                    text = text + ' (' + size + ' KB)'
                }
            }

            let pretext = {
                'captions'  : this.$gettext(`Untertitel (${text})`),
                'slides'    : this.$gettext(`Vortragsfolien ${text}`),
                'etherpad'  : this.$gettext('Geteilte Notizen (Etherpad Versionsgeschichte)'),
                'notes'     : this.$gettext('Geteilte Notizen'),
                'presenter'             : '',
                'presenter_audio'       : this.$gettext(`ReferentIn {$text}`),
                'presentation'          : '',
                'presentation_audio'    : this.$gettext(`Bildschirm {$text}`),
            };

            return pretext[media.type] ? pretext[media.type] : text;
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

            if (this.event?.publication?.downloads?.supplemental?.length > 0) {
                this.supplemental = this.event?.publication?.downloads?.supplemental
            }
        }
    },

    mounted() {
        this.extractDownloads();
    }

}
</script>
