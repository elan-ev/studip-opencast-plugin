<template>
    <div>
        <StudipDialog
            :title="title"
            :confirmText="$gettext('Erstellen')"
            :confirmClass="'accept'"
            :closeText="$gettext('Abbrechen')"
            :closeClass="'cancel'"
            height="500"
            @close="$emit('cancel')"
            @confirm="createPlaylist"
            @keyup.enter="createPlaylist"
        >
            <template v-slot:dialogContent>
                <form class="default" ref="playlistAddNewCard-form" @submit.prevent="createPlaylist">
                    <label v-if="simple_config_list && simple_config_list['server'] && simple_config_list['server'].length > 1">
                            <span class="required">
                                {{ $gettext('Server auswählen:') }}'
                            </span>

                        <select v-model="selectedServer" required>
                            <option v-for="server in simple_config_list['server']"
                                    :key="server.id"
                                    :value="server"
                            >
                                #{{ server.id }} - {{ server.name }} (Opencast V {{ server.version }}.X)
                            </option>

                        </select>
                    </label>

                    <label>
                        <span class="required">Titel</span>
                        <input type="text"
                                ref="autofocus"
                                maxlength="255"
                                :placeholder="$gettext('Titel der Wiedergabeliste')"
                                v-model="playlist.title"
                                required
                        >
                    </label>
                </form>
            </template>
        </StudipDialog>
    </div>
</template>

<script>
import StudipButton from "@studip/StudipButton";
import StudipDialog from '@studip/StudipDialog'
import { mapGetters } from "vuex";

export default {
    name: "PlaylistAddNewCard",

    components: {
        StudipButton,
        StudipDialog
    },

    props: {
        isDefault: {
            type: Boolean,
            default: false
        },
    },

    emits: ['done', 'cancel'],

    computed: {
        ...mapGetters(['simple_config_list', 'currentUser', 'currentLTIUser']),

        title() {
            return this.isDefault ? this.$gettext('Kurswiedergabeliste anlegen') : this.$gettext('Wiedergabeliste anlegen');
        }
    },

    data() {
        return {
            selectedServer: false,
            playlist: {
                title: '',
                description: '',  // TODO: Use description
                creator: '',
                config_id: null,
                visibility: 'internal',
                is_default: false
            }
        }
    },

    methods: {
        createPlaylist() {
            if (!this.$refs['playlistAddNewCard-form'].reportValidity()) {
                return false;
            }

            this.playlist.config_id = this.selectedServer.id;
            this.playlist.is_default = this.isDefault;
            this.playlist.creator = this.currentUser.fullname;

            this.$store.dispatch('addPlaylist', this.playlist)
                .then(() => {
                    this.$emit('done');
                })
                .catch(() => {
                    this.$store.dispatch('addMessage', {
                        type: 'error',
                        text: this.$gettext('Die Wiedergabeliste konnte nicht erstellt werden.')
                    });
                    this.$emit('cancel');
                });
        }
    },

    mounted() {
        this.$refs.autofocus.focus();
    },

    watch: {
        simple_config_list(newValue) {
            if (newValue?.server && newValue?.settings) {
                this.selectedServer = newValue['server'][newValue.settings['OPENCAST_DEFAULT_SERVER']];
            }
        }
    },
}
</script>
