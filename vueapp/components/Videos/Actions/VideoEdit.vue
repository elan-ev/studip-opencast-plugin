<template>
    <div>
        <StudipDialog
            :title="$gettext('Episode bearbeiten')"
            :closeText="$gettext('Abbrechen')"
            :confirmText="$gettext('Speichern')"
            confirmClass="accept"
            :closeClass="'cancel'"
            height="600"
            width="600"
            @close="decline"
            @confirm="accept"
        >
            <template v-slot:dialogContent>
                <form class="default" style="max-width: 50em;" @submit="editVideo">
                    <fieldset>
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

                        <label>
                            Tags
                            <TagBar :taggable="event" @update="updatedTags"/>
                        </label>
                    </fieldset>
                </form>
            </template>
        </StudipDialog>
    </div>
</template>

<script>
import StudipDialog from '@studip/StudipDialog';
import TagBar from '@/components/TagBar.vue';

export default {
    name: "VideoEdit",

    components: {
        StudipDialog, TagBar
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
            }).catch(() => {
                this.$emit('cancel');
            });
        },

        decline() {
            this.$emit('cancel');
        },

        updatedTags() {
            for (let i = 0; i < this.event.tags.length; i++) {
                if (typeof this.event.tags[i] !== 'object') {
                    // fix tag, because vue-select seems to have an incosistent behaviour
                    this.event.tags[i] = {
                        tag:  this.event.tags[i]
                    }
                }
            }
        }
    }
}
</script>