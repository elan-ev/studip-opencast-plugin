<template>
    <div class="oc--embed-options">
        <form class="default">
            <label>
                {{ $gettext('Einbettungscode') }}
                <textarea :value="embeddingCode" readonly></textarea>
            </label>
            <label>
                {{ $gettext('Einbettungslink') }}
                <input type="text" :value="embeddingLink" readonly ref="embeddingLink" />
            </label>
        </form>
        <MessageList :dialog="true" :float="true" />
        <div class="oc--tab-footer">
            <button
                class="button"
                :disabled="!embeddingCode"
                :title="$gettext('Einbettungslink in die Zwischenablage kopieren')"
                @click="copyEmbeddingLink()"
            >
                {{ $gettext('Link kopieren') }}
            </button>
            <button
                class="button"
                :disabled="!embeddingCode"
                :title="$gettext('Einbettungscode in die Zwischenablage kopieren')"
                @click="copyEmbeddingCode()"
            >
                {{ $gettext('Code kopieren') }}
            </button>
        </div>
    </div>
</template>

<script>
import { mapGetters } from 'vuex';
import MessageList from '@/components/MessageList.vue';

export default {
    name: 'VideoEmbeddingCode',

    components: {
        MessageList,
    },

    props: ['event'],

    computed: {
        ...mapGetters('config', ['simple_config_list']),
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
            this.$store.dispatch('messages/clearMessages', true);
            navigator.clipboard
                .writeText(text)
                .then(() => {
                    this.$store.dispatch('messages/addMessage', {
                        type: 'success',
                        text: successText,
                        dialog: true,
                    });
                })
                .catch(() => {
                    this.$store.dispatch('messages/addMessage', {
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
