<template>
    <div>
        <StudipDialog
            :title="$gettext('Opencast Server Einstellungen')"
            :confirmText="$gettext('Einstellungen speichern und überprüfen')"
            :closeText="$gettext('Schließen')"
            height="600"
            width="600"
            @confirm="storeConfig"
            @close="close"
        >
            <template v-slot:dialogContent ref="editServer-dialog">
                <form class="default" v-if="currentConfig">
                    <fieldset>
                        <ConfigOption v-for="setting in settings"
                            :setting="setting" :key="setting.name"
                            @updateValue="updateValue" />
                    </fieldset>
                </form>
            </template>

            <template v-slot:dialogButtons>
                <button class="button trash" type="button" @click="deleteConfig">
                    Löschen
                </button>
            </template>

            <MessageList />

        </StudipDialog>
    </div>
</template>

<script>
import { mapGetters } from "vuex";

import StudipDialog from '@studip/StudipDialog'
import StudipButton from "@studip/StudipButton";
import StudipIcon from "@studip/StudipIcon";
import MessageList from "@/components/MessageList";
import ConfigOption from "@/components/Config/ConfigOption";
import { LtiService, LtiException } from "@/common/lti.service";

export default {
    name: "EditServer",

    components: {
        StudipButton,
        StudipIcon,
        StudipDialog,
        ConfigOption,
        MessageList,
    },

    props: {
        id : {
            default: 'new'
        },
        config : {
            type: Object,
            default: null
        }
    },

    data() {
        return {
            currentConfig: {}
        }
    },

    computed: {
        ...mapGetters({
            configStore: 'config'
        }),

        settings() {
            return [
                {
                    description: this.$gettext('Basis URL zur Opencast Installation'),
                    name: 'service_url',
                    value: this.currentConfig.service_url,
                    type: 'string',
                    placeholder: 'https://opencast.url',
                    required: true
                },
                {
                    description: this.$gettext('Nutzerkennung'),
                    name: 'service_user',
                    value: this.currentConfig.service_user,
                    type: 'string',
                    placeholder: 'ENDPOINT_USER',
                    required: true
                },
                {
                    description: this.$gettext('Passwort'),
                    name: 'service_password',
                    value: this.currentConfig.service_password,
                    type: 'password',
                    placeholder: 'ENDPOINT_USER_PASSWORD',
                    required: true
                },
                {
                    description: this.$gettext('LTI Consumerkey'),
                    name: 'lti_consumerkey',
                    value: this.currentConfig.lti_consumerkey,
                    type: 'string',
                    placeholder: 'CONSUMERKEY',
                    required: true
                },
                {
                    description: this.$gettext('LTI Consumersecret'),
                    name: 'lti_consumersecret',
                    value: this.currentConfig.lti_consumersecret,
                    type: 'password',
                    placeholder: 'CONSUMERSECRET',
                    required: true
                },
                {
                    description: this.$gettext('GET-Requests mit Adnvaced-Suche wie Lucene/Search Endpoints ausführen?'),
                    name: 'advance_search',
                    value: this.currentConfig.advance_search,
                    type: 'boolean',
                    required: false
                },
                {
                    description: this.$gettext('Debugmodus einschalten?'),
                    name: 'debug',
                    value: this.currentConfig.debug,
                    type: 'boolean',
                    required: false
                }
            ];
        }
    },

    methods: {
        close() {
            this.$emit('close');
        },

        storeConfig() {
            this.$store.dispatch('clearMessages');

            this.currentConfig.checked = false;

            if (this.id == 'new') {
                this.$store.dispatch('configCreate', this.currentConfig)
                .then(({ data }) => {
                    this.checkConfigResponse(data);
                });
            } else {
                this.$store.dispatch('configUpdate', this.currentConfig)
                .then(({ data }) => {
                    this.checkConfigResponse(data);
                });
            }
        },

        deleteConfig() {
            if (confirm('Sind sie, dass sie diese Serverkonfiguration löschen möchten?')) {
                if (this.id == 'new') {
                    this.currentConfig = {}
                } else {
                    this.$store.dispatch('configDelete', this.id);
                }

                this.close();
            }
        },

        checkConfigResponse(data) {
            if (data.lti !== undefined) {
                this.checkLti(data.lti);
            }
            if (data.message !== undefined) {
                this.$store.dispatch('addMessage', {
                     type: data.message.type,
                     text: data.message.text
                });

                if(data.message.type == 'success'){
                    this.$emit('close');
                }
            }
        },

        async checkLti(data) {
            let view = this;

            let check_successful = true;

            for (let i = 0; i < data.length; i++) {
                let lti = new LtiService(this.currentConfig.id, data.endpoints);
                lti.setLaunchData(data[i]);
                await lti.authenticate();
                if (!lti.isAuthenticated()) {
                    check_successful = false;
                }
            }

            if (check_successful) {
                view.$store.dispatch('addMessage', {
                     type: 'success',
                     text: view.$gettext('Die LTI-Konfiguration wurde erfolgreich überprüft!')
                });
            } else {
                view.$store.dispatch('addMessage', {
                     type: 'error',
                     text: view.$gettext('Überprüfung der LTI Verbindung fehlgeschlagen! '
                         + 'Kontrollieren Sie die eingetragenen Daten und stellen Sie sicher, '
                         + 'dass Cross-Origin Aufrufe von dieser Domain aus möglich sind! '
                         + 'Denken sie auch daran, in Opencast die korrekten access-control-allow-* '
                         + 'Header zu setzen.'
                     )
                });
            };
        },

        updateValue(setting, newValue) {
            this.currentConfig[setting.name] = newValue;
        },
    },

    mounted() {
        this.$store.dispatch('clearMessages');

        if (this.id !== 'new') {
            if (!this.config) {
                this.$store.dispatch('configRead', this.id)
                .then(() => {
                    this.currentConfig = this.configStore;
                });
            } else {
                this.currentConfig = this.config;
            }
        }

    }
};
</script>