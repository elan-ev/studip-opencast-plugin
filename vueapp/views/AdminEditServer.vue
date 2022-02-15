<template>
    <div>
        <form class="default" v-if="config">
            <fieldset>
                <legend>
                    <translate>
                    Opencast Server Einstellungen
                    </translate>
                    <StudipIcon icon="accept" role="status-green" v-if="config.checked"/>
                </legend>

                <ConfigOption v-for="setting in settings"
                    :setting="setting" :key="setting.name"
                    @updateValue="updateValue" />
            </fieldset>

            <footer>
                <StudipButton icon="accept" @click="storeConfig" v-translate>
                    Einstellungen speichern und überprüfen
                </StudipButton>
                <StudipButton icon="cancel" @click="$router.push('/admin')" v-translate>
                    Abbrechen
                </StudipButton>
            </footer>
        </form>

        <MessageList />
    </div>
</template>

<script>
import { mapGetters } from "vuex";
import store from "@/store";

import StudipButton from "@/components/StudipButton";
import StudipIcon from "@/components/StudipIcon";
import MessageList from "@/components/MessageList";
import ConfigOption from "@/components/Config/ConfigOption";
import LTIService from "@/common/lti.service";

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
        configProp : {
            type: Object,
            default: null
        }
    },

    computed: {
        ...mapGetters(['config']),

        settings() {
            return [
                {
                    description: this.$gettext('Basis URL zur Opencast Installation'),
                    name: 'service_url',
                    value: this.config.service_url,
                    type: 'string',
                    placeholder: 'https://opencast.url',
                    required: true
                },
                {
                    description: this.$gettext('Nutzerkennung'),
                    name: 'service_user',
                    value: this.config.service_user,
                    type: 'string',
                    placeholder: 'ENDPOINT_USER',
                    required: true
                },
                {
                    description: this.$gettext('Passwort'),
                    name: 'service_password',
                    value: this.config.service_password,
                    type: 'password',
                    placeholder: 'ENDPOINT_USER_PASSWORD',
                    required: true
                },
                {
                    description: this.$gettext('LTI Consumerkey'),
                    name: 'lti_consumerkey',
                    value: this.config.settings.lti_consumerkey,
                    type: 'string',
                    placeholder: 'CONSUMERKEY',
                    required: true
                },
                {
                    description: this.$gettext('LTI Consumersecret'),
                    name: 'lti_consumersecret',
                    value: this.config.settings.lti_consumersecret,
                    type: 'password',
                    placeholder: 'CONSUMERSECRET',
                    required: true
                }
            ];
        }
    },

    methods: {
        storeConfig(event) {
            event.preventDefault();

            this.$store.dispatch('clearMessages');

            this.config.checked = false;

            if (this.id == 'new') {
                this.$store.dispatch('configCreate', this.config)
                .then(({ data }) => {
                    this.checkConfigResponse(data);
                });
            } else {
                this.$store.dispatch('configUpdate', this.config)
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

            if (data.lti !== undefined) {
                this.checkLti(data.lti);
            }
        },

        checkLti(lti) {
            let view = this;

            LTIService.init(lti);
            LTIService.check()
            .then(({ data }) => {
                view.$store.dispatch('addMessage', {
                     type: 'success',
                     text: view.$gettext('Die LTI-Konfiguration wurde erfolgreich überprüft!')
                });
            }).catch(function (error) {
                view.$store.dispatch('addMessage', {
                     type: 'error',
                     text: lti.launch_url + ': ' + view.$gettext('Überprüfung der LTI Verbindung fehlgeschlagen! '
                         + 'Kontrollieren Sie die eingetragenen Daten und stellen Sie sicher, '
                         + 'dass Cross-Origin Aufrufe von dieser Domain aus möglich sind! '
                         + 'Denken sie auch daran, in Opencast die korrekten access-control-allow-* '
                         + 'Header zu setzen.'
                     )
                });
            });
        },

        updateValue(setting, newValue) {
            for (let id in this.config) {
                if (id == setting.name) {
                    this.config[id] = newValue;
                    return;
                }
            }

            this.config.settings[setting.name] = newValue;
            return;
        },
    },

    mounted() {
        if (this.id != 'new') {
            if (!this.configProp) {
                store.dispatch('configRead', this.id);
            } else {
                this.config = this.configProp;
            }
        }
    }
};
</script>
