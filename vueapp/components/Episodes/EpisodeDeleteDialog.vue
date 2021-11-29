<template>
    <div>
        <OpencastDialog @close="decline">
            <template v-slot:content>
                <p>Möchten Sie die Aufzeichnung wirklich entfernen?</p>
            </template>
            <template v-slot:buttons>
                <StudipButton icon="accept" type="button" 
                @click="accept" 
                class="ui-button ui-corner-all ui-widget"
                v-translate>
                    Akzeptieren
                </StudipButton>

                <StudipButton icon="cancel" type="button" 
                @click="decline" 
                class="ui-button ui-corner-all ui-widget"
                v-translate>
                    Abbrechen
                </StudipButton>
            </template>
        </OpencastDialog>
    </div>
</template>

<script>
import OpencastDialog from '@/components/OpencastDialog'
import StudipButton from '@/components/StudipButton'
import { dialog } from '@/common/dialog.mixins'

export default {
    name: 'EpisodeDeleteDialog',

    components: {
        OpencastDialog, StudipButton
    },

    mixins: [dialog],

    methods: {
        accept() {
            this.$emit('done');
        },

        decline() {
            this.$emit('cancel');
        }
    }
}
</script>