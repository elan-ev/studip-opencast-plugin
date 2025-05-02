<template>
    <div>
        <StudipDialog
            :title="$gettext('Einbettungscode')"
            :closeText="$gettext('Schließen')"
            :closeClass="'cancel'"
            height="400"
            width="550"
            @close="this.$emit('cancel')"
        >
            <template v-slot:dialogContent>
                <textarea v-model="embeddingCode" rows="5" class="oc--embedding-code-text" readonly></textarea>

                <StudipButton
                    :disabled="!embeddingCode"
                    @click.prevent="copyEmbeddingCode()"
                >
                    {{ $gettext('Einbettungscode kopieren') }}
                </StudipButton>

                <MessageList :dialog="true" />
            </template>
        </StudipDialog>
    </div>
</template>

<script>
import { mapGetters } from 'vuex';
import StudipDialog from '@studip/StudipDialog'
import StudipButton from '@studip/StudipButton'
import MessageList from '@/components/MessageList.vue';

export default {
    name: 'VideoEmbeddingCode',

    components: {
        StudipDialog,
        StudipButton,
        MessageList,
    },

    props: ['event'],

    computed: {
        ...mapGetters([
            'simple_config_list'
        ]),
        url() {
            if (this.event.config_id === undefined) {
                return null;
            }

            if (this.simple_config_list?.server?.[this.event.config_id]?.play === undefined) {
                return null;
            }

            return this.simple_config_list.server[this.event.config_id].play + '/' + this.event.episode;
        },
        embeddingCode() {
            if (!this.url) {
                return null;
            }

            return `<iframe allowfullscreen src="${this.url}" style="border: 0; margin 0;" name="Player"></iframe>`;
        },
    },

    methods: {
        copyEmbeddingCode() {
            navigator.clipboard.writeText(this.embeddingCode).then(() => {
                this.$store.dispatch('addMessage', {
                    type: 'success',
                    text: this.$gettext('Der Einbettungscode wurde in die Zwischenablage kopiert.'),
                    dialog: true
                });
            }).catch(() => {
                this.$store.dispatch('addMessage', {
                    type: 'error',
                    text: this.$gettext('Der Einbettungscode konnte nicht in die Zwischenablage kopiert werden.'),
                    dialog: true
                });
            });
        },
    },
}
</script>
