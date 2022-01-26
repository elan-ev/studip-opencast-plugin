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

                <label>
                    <translate>Basis URL zur Opencast Installation</translate>
                    <input type="text"
                        v-model="config.url"
                        placeholder="https://opencast.url">
                </label>

                <label>
                    <translate>Nutzerkennung</translate>
                    <input type="text"
                        v-model="config.user"
                        placeholder="ENDPOINT_USER">
                </label>

                <label>
                    <translate>Passwort</translate>
                    <input type="password"
                        v-model="config.password"
                        placeholder="ENDPOINT_USER_PASSWORD">
                </label>

                <label>
                    <translate>LTI Consumerkey</translate>
                    <input type="text"
                        v-model="config.ltikey"
                        placeholder="CONSUMERKEY"
                        :class="{ 'invalid': lti_error }">
                </label>

                <label>
                    <translate>LTI Consumersecret</translate>
                    <input type="text"
                        v-model="config.ltisecret"
                        placeholder="CONSUMERSECRET"
                        :class="{ 'invalid': lti_error }">
                </label>

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
            </footer>
        </form>

        <MessageBox v-if="message" :type="message.type" @hide="message = ''">
            {{ message.text }}
        </MessageBox>
    </div>
</template>

<script>
import { mapGetters } from "vuex";
import store from "@/store";

import StudipButton from "@/components/StudipButton";
import StudipIcon from "@/components/StudipIcon";
import MessageBox from "@/components/MessageBox";

export default {
    name: "AdminEditServer",

    components: {
        StudipButton, StudipIcon,
        MessageBox
    },

    data() {
        return {
            message: null,
            lti_error: false,
            lti: {}
        }
    },

    computed: {
        ...mapGetters(['config'])
    },

    methods: {
        storeConfig() {
            this.message = { type: 'info', text: 'Überprüfe Konfiguration...'};
            this.config.checked = false;

            this.$store.dispatch('configCreate', this.config)
                .then(({ data }) => {
                    this.message = data.message;
                    this.checkLti(data.lti);
                    this.$store.commit('configSet', data.config);
                });
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
        nextStep() {
            this.$router.push({ name: 'admin_step2' });
        }
    },
    mounted() {
        store.dispatch('configRead', 1);
    }
};
</script>
