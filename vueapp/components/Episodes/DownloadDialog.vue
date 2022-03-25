<template>
    <div>
        <StudipDialog
            :title="$gettext('Medien herunterladen')"
            :closeText="$gettext('Abbrechen')"
            :closeClass="'cancel'"
            height="500"
            @close="decline"
        >
            <template v-slot:dialogContent>
                <div v-if="presenter.length">
                    <h2>
                        ReferentIn
                    </h2>
                    <a v-for="(media, index) in presenter" :key="index"
                            :href="media['url']" target="_blank">
                        <StudipButton>
                            {{ getMediaText(media) }}
                        </StudipButton>
                    </a>
                </div>
                <div v-if="presentation.length">
                    <h2>
                        Bildschirm
                    </h2>
                    <a v-for="(media, index) in presentation" :key="index"
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
import { dialog } from '@/common/dialog.mixins'

export default {
    name: 'DownloadDialog',

    components: {
        StudipDialog, StudipButton
    },

    mixins: [dialog],

    props: ['downloads'],

    data() {
        return {
            presenter: [],
            presentation: []
        }
    },

    methods: {
        decline() {
            this.$emit('cancel');
        },

        getMediaText(media) {
            var text = media['info']
            var size = media['size']/1000
            if (size > 1000) {
                size = Math.round(size/1000 * 10) / 10
                text = text + ' (' + size + ' MB)'
            }
            else {
                size = Math.round(size * 10) / 10
                text = text + ' (' + size + ' KB)'
            }
            return text
        }
    },

    mounted() {
        this.presentation = this.downloads.filter(function (e) {
            return e['type'].includes('presenter')
        })

        this.presenter = this.downloads.filter(function (e) {
            return e['type'].includes('presentation')
        })
    }

}
</script>
