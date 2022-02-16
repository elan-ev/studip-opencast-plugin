<template>
    <div>
        <StudipDialog
            title="Episode hinzufügen"
            confirmText="Hochladen"
            :confirmClass="'accept'"
            closeText="Abbrechen"
            :closeClass="'cancel'"
            height="350"
            @close="decline"
            @confirm="accept"
        >
            <template v-slot:dialogContent>
                <form class="default"
                    style="max-width: 50em;"
                >
                    <fieldset>
                        <legend v-translate>
                            Formular
                        </legend>
                        <label class="col-3">
                            <span v-translate>ID</span>
                            <input type="text" v-model.trim="event['id']">
                        </label>
                        <label class="col-3">
                            <span v-translate>Title</span>
                            <input type="text" v-model.trim="event['title']">
                        </label>
                        <label class="col-3">
                            <span v-translate>Autor</span>
                            <input type="text" v-model.trim="event['author']">
                        </label>
                        <label class="col-3">
                            <span v-translate>Type</span>
                            <input type="text" v-model.trim="event['type']">
                        </label>
                    </fieldset>
                </form>
            </template>
        </StudipDialog>
    </div>
</template>

<script>
import OpencastDialog from '@/components/OpencastDialog'
import StudipButton from '@/components/StudipButton'
import StudipDialog from 'studip/components/StudipDialog'
import { dialog } from '@/common/dialog.mixins'

export default {
    name: 'EpisodeAdd',

    components: {
        OpencastDialog, StudipButton, StudipDialog
    },

    data () {
        return {
            showAddEpisodeDialog: false,
            event: {}
        }
    },

    methods: {
        accept() {
            this.$store.dispatch('addEvent',
                {
                    id: this.event['id'],
                    title: this.event['title'],
                    author: this.event['author'],
                    type: this.event['type']
                }
            );
            this.$emit('done');
        },

        decline() {
            this.$emit('cancel');
        }
    }
}
</script>
