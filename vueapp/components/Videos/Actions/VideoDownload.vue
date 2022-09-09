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
                    <a v-for="(media, index) in presenters" :key="index"
                            :href="media['url']" target="_blank">
                        <StudipButton>
                            {{ getMediaText(media) }}
                        </StudipButton>
                    </a>
                </div>
                <div v-if="presentations.length">
                    <h2>
                        Bildschirm
                    </h2>
                    <a v-for="(media, index) in presentations" :key="index"
                            :href="media['url']" target="_blank">
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
            presenters: []
        }
    },

    methods: {
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
