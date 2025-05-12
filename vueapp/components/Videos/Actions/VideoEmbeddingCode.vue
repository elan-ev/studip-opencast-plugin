<template>
    <div>
        <StudipDialog
            :title="$gettext('Einbettungscode und -link')"
            :closeText="$gettext('Schließen')"
            :closeClass="'cancel'"
            height="520"
            width="550"
            @close="this.$emit('cancel')"
        >
            <template v-slot:dialogContent>
                <form class="default">
                    <fieldset>
                        <legend>
                            {{ $gettext('Einbettungscode') }}
                        </legend>

                        <textarea v-model="embeddingCode"
                            ref="embeddingCode"
                            rows="5"
                            class="oc--embedding-code-text"
                            readonly>
                        </textarea>

                        <StudipButton
                            :disabled="!embeddingCode"
                            @click.prevent="copyEmbeddingCode"
                        >
                            {{ $gettext('Einbettungscode kopieren') }}
                        </StudipButton>
                    </fieldset>

                    <fieldset>
                        <legend>
                            {{ $gettext('Einbettungslink') }}
                        </legend>

                        <div class="oc--embedding-link">
                            <input type="text" readonly :value="embeddingLink" ref="embeddingLink"/>
                            <studip-icon
                                shape="clipboard"
                                role="clickable"
                                @click="copyEmbeddingLink"
                                :title="$gettext('Einbettungslink kopieren')"
                                style="cursor: pointer;"/>
                        </div>
                    </fieldset>
                </form>
                <MessageList :dialog="true" :float="true" />
            </template>
        </StudipDialog>
    </div>
</template>

<script>
import { mapGetters } from 'vuex';
import StudipDialog from '@studip/StudipDialog'
import StudipButton from '@studip/StudipButton'
import MessageList from '@/components/MessageList.vue';
import StudipIcon from '@studip/StudipIcon.vue';

export default {
    name: 'VideoEmbeddingCode',

    components: {
        StudipIcon,
        StudipDialog,
        StudipButton,
        MessageList,
    },

    props: ['event'],

    computed: {
        ...mapGetters([
            'simple_config_list'
        ]),
        embeddingLink() {
            if (this.event.config_id === undefined) {
                return null;
            }

            if (this.simple_config_list?.server?.[this.event.config_id]?.play === undefined) {
                return null;
            }

            return this.simple_config_list.server[this.event.config_id].play + '/' + this.event.episode;
        },
        embeddingCode() {
            if (!this.embeddingLink) {
                return null;
            }

            return `<iframe allowfullscreen src="${this.embeddingLink}" style="border: 0; margin 0;" name="Player"></iframe>`;
        },
    },

    methods: {
        copyEmbeddingCode() {
            this.$store.dispatch('clearMessages', true);
            this.$refs.embeddingCode.select();

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

        copyEmbeddingLink() {
            this.$store.dispatch('clearMessages', true);
            this.$refs.embeddingLink.select();

            navigator.clipboard.writeText(this.embeddingLink).then(() => {
                this.$store.dispatch('addMessage', {
                    type: 'success',
                    text: this.$gettext('Der Einbettungslink wurde in die Zwischenablage kopiert.'),
                    dialog: true
                });
            }).catch(() => {
                this.$store.dispatch('addMessage', {
                    type: 'error',
                    text: this.$gettext('Der Einbettungslink konnte nicht in die Zwischenablage kopiert werden.'),
                    dialog: true
                });
            });
        }
    },
}
</script>
