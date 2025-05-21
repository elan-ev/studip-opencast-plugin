<template>
    <div>
        <StudipDialog
            :title="$gettext('Einbettungsoptionen')"
            :closeText="$gettext('Schließen')"
            :closeClass="'cancel'"
            height="450"
            width="550"
            @close="this.$emit('cancel')"
        >
            <template v-slot:dialogContent>
                <form class="default oc--video-actions-embedding">
                    <label>
                        {{ $gettext('Einbettungscode') }}
                        <textarea :value="embeddingCode" rows="5" readonly></textarea>
                    </label>
                    <legend>
                        {{ $gettext('Einbettungslink') }}
                        <input type="text" :value="embeddingLink" readonly ref="embeddingLink" />
                    </legend>
                </form>
                <MessageList :dialog="true" />
            </template>
            <template #dialogButtons>
                <button class="button" :disabled="!embeddingCode" :title="$gettext('Einbettungslink in die Zwischenablage kopieren')" @click="copyEmbeddingLink()">
                    {{ $gettext('Link kopieren') }}
                </button>
                <button class="button" :disabled="!embeddingCode" :title="$gettext('Einbettungscode in die Zwischenablage kopieren')" @click="copyEmbeddingCode()">
                    {{ $gettext('Code kopieren') }}
                </button>
            </template>
        </StudipDialog>
    </div>
</template>

<script>
import { mapGetters } from 'vuex';
import StudipDialog from '@studip/StudipDialog';
import MessageList from '@/components/MessageList.vue';

export default {
    name: 'VideoEmbeddingCode',

    components: {
        StudipDialog,
        MessageList,
    },

    props: ['event'],

    computed: {
        ...mapGetters(['simple_config_list']),
        url() {
            if (this.event.config_id === undefined) {
                return null;
            }

            if (this.simple_config_list?.server?.[this.event.config_id]?.play === undefined) {
                return null;
            }

            return this.simple_config_list.server[this.event.config_id].play + '/' + this.event.episode;
        },
        embeddingLink() {
            return this.url;
        },
        embeddingCode() {
            if (!this.url) {
                return null;
            }

            return `<iframe allowfullscreen src="${this.url}" style="border: 0; margin 0;" name="Player"></iframe>`;
        },
    },

    methods: {
        copyToClipboard(text, successText, errorText) {
            this.$store.dispatch('clearMessages', true);
            navigator.clipboard
                .writeText(text)
                .then(() => {
                    this.$store.dispatch('addMessage', {
                        type: 'success',
                        text: successText,
                        dialog: true,
                    });
                })
                .catch(() => {
                    this.$store.dispatch('addMessage', {
                        type: 'error',
                        text: errorText,
                        dialog: true,
                    });
                });
        },

        copyEmbeddingCode() {
            this.copyToClipboard(
                this.embeddingCode,
                this.$gettext('Der Einbettungscode wurde in die Zwischenablage kopiert.'),
                this.$gettext('Der Einbettungscode konnte nicht in die Zwischenablage kopiert werden.')
            );
        },

        copyEmbeddingLink() {
            this.copyToClipboard(
                this.embeddingLink,
                this.$gettext('Der Einbettungslink wurde in die Zwischenablage kopiert.'),
                this.$gettext('Der Einbettungslink konnte nicht in die Zwischenablage kopiert werden.')
            );
        },
    },
};
</script>
