<template>
    <div>
        <StudipDialog
            :title="$gettext('Aufzeichnung entfernen')"
            :confirmText="$gettext('Löschen')"
            :confirmClass="'accept'"
            :closeText="$gettext('Abbrechen')"
            :closeClass="'cancel'"
            height="500"
            @close="decline"
            @confirm="accept"
        >
            <template v-slot:dialogContent>
                <p>Möchten Sie die Aufzeichnung wirklich entfernen?</p>
            </template>
        </StudipDialog>
    </div>
</template>

<script>
import StudipDialog from '@studip/StudipDialog'
import { dialog } from '@/common/dialog.mixins'

export default {
    name: 'EpisodeDeleteDialog',

    components: {
        StudipDialog
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
