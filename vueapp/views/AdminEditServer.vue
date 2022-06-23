<template>
    <div>
        <form class="default" v-if="currentConfig">
            <fieldset>
                <legend>
                    <translate>
                    Opencast Server Einstellungen
                    </translate>
                    <StudipIcon shape="accept" role="status-green" v-if="currentConfig.checked"/>
                </legend>

                <ConfigOption v-for="setting in settings"
                    :setting="setting" :key="setting.name"
                    @updateValue="updateValue" />
            </fieldset>

            <footer>
                <StudipButton icon="accept" @click="storeConfig" v-translate>
                    Einstellungen speichern und überprüfen
                </StudipButton>
                <StudipButton icon="cancel" @click.prevent="$router.push({name: 'admin'})" v-translate>
                    Abbrechen
                </StudipButton>
            </footer>
        </form>

        <MessageList />
    </div>
</template>

<script>
import { mapGetters } from "vuex";

import StudipButton from "@studip/StudipButton";
import StudipIcon from "@studip/StudipIcon";
import MessageList from "@/components/MessageList";
import ConfigOption from "@/components/Config/ConfigOption";
import { LtiService, LtiException } from "@/common/lti.service";

export default {
    name: "AdminEditServer",

    components: {
        StudipButton, StudipIcon,
        MessageList, ConfigOption
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
            currentConfig: null
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
                    value: this.currentConfig.settings.lti_consumerkey,
                    type: 'string',
                    placeholder: 'CONSUMERKEY',
                    required: true
                },
                {
                    description: this.$gettext('LTI Consumersecret'),
                    name: 'lti_consumersecret',
                    value: this.currentConfig.settings.lti_consumersecret,
                    type: 'password',
                    placeholder: 'CONSUMERSECRET',
                    required: true
                },
                {
                    description: this.$gettext('GET-Requests mit Adnvaced-Suche wie Lucene/Search Endpoints ausführen?'),
                    name: 'advance_search',
                    value: this.currentConfig.settings.advance_search,
                    type: 'boolean',
                    required: false
                },
                {
                    description: this.$gettext('Debugmodus einschalten?'),
                    name: 'debug',
                    value: this.currentConfig.settings.debug,
                    type: 'boolean',
                    required: false
                }
            ];
        }
    },

    methods: {
        storeConfig(event) {
            event.preventDefault();

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

        checkConfigResponse(data) {
            if (data.message !== undefined) {
                this.$store.dispatch('addMessage', {
                     type: data.message.type,
                     text: data.message.text
                });
            }

            this.checkLti(data.lti);
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
            for (let id in this.currentConfig) {
                if (id == setting.name) {
                    this.currentConfig[id] = newValue;
                    return;
                }
            }

            this.currentConfig.settings[setting.name] = newValue;
            return;
        },
    },

    mounted() {
        this.$store.dispatch('clearMessages');

        if (this.id != 'new') {
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
