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

                <MessageBox v-if="lti_error" type="error" @hide="lti_error = false" v-translate>
                    Überprüfung der LTI Verbindung fehlgeschlagen! <br />
                    Kontrollieren Sie die eingetragenen Daten und stellen Sie
                    sicher, dass Cross-Origin Aufrufe von dieser Domain zur URL
                    {{ lti.launch_url }} möglich sind! <br />
                    Denken sie auch daran, in Opencast die korrekten
                    access-control-allow-* Header zu setzen.
                </MessageBox>
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

    data() {
        return {
            message: null,
            lti_error: false,
            lti: {}
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

            this.config.checked = false;

            if (this.id == 'new') {
                this.$store.dispatch('configCreate', this.config)
                .then(({ data }) => {
                    this.message = data.message;
                    this.checkLti(data.lti);
                });
            } else {
                this.$store.dispatch('configUpdate', this.config)
                .then(({ data }) => {
                    this.message = data.message;
                    this.checkLti(data.lti);
                });
            }
        },

        checkLti(lti) {
            let view = this;
            this.lti = lti;

             Vue.axios.post(lti.launch_url, lti.launch_data, {
                 crossDomain: true,
                 withCredentials: true
             })
            .then(() => {
                view.lti_error = false;
            }).catch(function (error) {
                view.lti_error = true;
            });
        },

        updateValue(setting, newValue) {
            for (let id in this.config) {
                if (id == setting.name) {
                    this.config[id] = newValue;
                    return;
                }
            }

            for (let id in this.config['settings']) {
                if (id == setting.name) {
                    this.config['settings'][id] = newValue;
                    return;
                }
            }
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
