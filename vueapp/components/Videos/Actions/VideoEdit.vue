<template>
    <div>
        <StudipDialog
            :title="$gettext('Episode bearbeiten')"
            :closeText="$gettext('Abbrechen')"
            :confirmText="$gettext('Bearbeiten')"
            confirmClass="accept"
            :closeClass="'cancel'"
            height="600"
            width="600"
            @close="decline"
            @confirm="accept"
        >
            <template v-slot:dialogContent>
                <form class="default" style="max-width: 50em;" @submit="editVideo">
                    <fieldset v-if="!uploadProgress">
                        <label>
                            <span class="required" v-translate>
                                Titel
                            </span>

                            <input type="text" maxlength="255"
                                name="title" v-model="event.title" required>
                        </label>

                        <label>
                            <span v-translate>
                                Mitwirkende
                            </span>
                            <input type="text" maxlength="255" name="contributor" v-model="event.contributors">
                        </label>

                        <label>
                            <span v-translate>
                                Thema
                            </span>
                            <input type="text" maxlength="255" name="subject" v-model="event.subject">
                        </label>

                        <label>
                            <span v-translate>
                                Beschreibung
                            </span>
                            <textarea cols="50" rows="5" name="description" v-model="event.description"></textarea>
                        </label>
                    </fieldset>
                </form>
            </template>
        </StudipDialog>
    </div>
</template>

<script>
import StudipDialog from '@studip/StudipDialog';

export default {
    name: "VideoEdit",

    components: {
        StudipDialog
    },

    props: ['event'],

    emits: ['done', 'cancel'],

    methods: {
        async accept() {
            await this.$store.dispatch('updateVideo', this.event)
            .then(({ data }) => {
                this.$store.dispatch('addMessage', data.message);
                let emit_action = data.message.type == 'success' ? 'refresh' : '';
                this.$emit('done', emit_action);
            })
        },

        decline() {
            this.$emit('cancel');
        }
    },
}
</script>