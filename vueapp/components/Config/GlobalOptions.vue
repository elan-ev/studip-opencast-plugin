<template>
    <div class="oc--admin--section">
        <fieldset>
            <legend>
                <OpencastIcon small/>
                {{ $gettext('Globale Einstellungen') }}
            </legend>

            <ConfigOption v-for="setting in global_settings"
                :key="setting.name" :setting="setting"
                @updateValue="updateValue"/>
        </fieldset>

        <fieldset v-if="showTos">
            <legend v-translate>
                Terms of service
            </legend>

            <I18NText :text="opencastTos"
                :languages="config_list.languages"
                @input="updateTos"
            />
        </fieldset>
    </div>
</template>

<script>
import { mapGetters } from "vuex";

import StudipButton from "@studip/StudipButton";
import StudipIcon from "@studip/StudipIcon";
import MessageBox from "@/components/MessageBox";
import OpencastIcon from "@/components/OpencastIcon";
import ConfigOption from "@/components/Config/ConfigOption";
import I18NText from "@/components/Config/I18NText";

export default {
    name: "GlobalOptions",

    components: {
        StudipButton,
        StudipIcon,
        MessageBox,
        OpencastIcon,
        ConfigOption,
        I18NText,
    },

    props: ['config_list'],

    computed: {
        ...mapGetters(['config']),

        /**
         * The list of global settings, cleaned by settings which should not be displayed directly
         *
         * @return {Object} settings list
         */
        global_settings() {
            let settings = [];
            for (let id in this.config_list.settings) {
                if (this.config_list.settings[id].name == 'OPENCAST_MEDIADOWNLOAD') {
                    let setting = this.config_list.settings[id];
                    setting.options = this.downloadOptions;
                    settings.push(setting);
                }
                else if (this.config_list.settings[id].name == 'OPENCAST_DEFAULT_SERVER') {
                    let setting = this.config_list.settings[id];
                    setting.options = this.defaultServerOptions;
                    settings.push(setting);
                }
                else if (this.config_list.settings[id].name != 'OPENCAST_TOS') {
                    settings.push(this.config_list.settings[id]);
                }
            }

            return settings
        },

        showTos() {
            for (let id in this.config_list.settings) {
                if (this.config_list.settings[id].name == 'OPENCAST_SHOW_TOS') {
                    return this.config_list.settings[id].value == true
                }
            }

            return false;
        },

        downloadOptions() {
            return [
                {
                    value: 'never',
                    description: 'Mediendownloads werden niemals angeboten'
                }, {
                    value: 'allow',
                    description: 'Mediendownloads sind erlaubt - für einzelne Wiedergabelisten und Videos abschaltbar'
                }, {
                    value: 'disallow',
                    description: 'Mediendownloads sind verboten - für einzelne Wiedergabelisten und Videos einschaltbar'
                }
            ];
        },

        defaultServerOptions() {
            let options = [];

            this.config_list.server.forEach(server => {
                options.push({
                    value: server.id,
                    description: '[#' + server.id + '] ' + server.service_url + ''
                })
            });

            if (options.length === 0) {
                options.push({
                    value: -1,
                    description: 'Es sind keine Server eingerichtet'
                })
            }

            return options;
        },

        opencastTos() {
            for (let id in this.config_list.settings) {
                if (this.config_list.settings[id].name == 'OPENCAST_TOS') {
                    try {
                        if (typeof JSON.parse(this.config_list.settings[id].value) !== 'object')
                        {
                            return {}
                        } else {
                            return JSON.parse(this.config_list.settings[id].value);
                        }
                    } catch (e) {
                        console.log(e);
                    }

                    return {}
                }
            }
        }
    },

    methods: {
        updateValue(setting, newValue) {
            for (let id in this.config_list.settings) {
                if (this.config_list.settings[id].name == setting.name) {

                    this.config_list.settings[id].value = newValue;
                    return;
                }
            }
        },

        updateTos(text) {
            for (let id in this.config_list.settings) {
                if (this.config_list.settings[id].name == 'OPENCAST_TOS') {
                    this.config_list.settings[id].value = JSON.stringify(text);
                }
            }
        },
    }
}
</script>
