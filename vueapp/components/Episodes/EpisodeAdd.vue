<template>
    <div>
        <OpencastDialog :title="Episodehinzufügen" @close="decline">
            <template v-slot:content>
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
    name: 'EpisodeAdd',

    components: {
        OpencastDialog, StudipButton
    },

    mixins: [dialog],

    data () {
        return {
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