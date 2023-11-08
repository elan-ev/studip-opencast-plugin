<template>
    <div class="oc--admin--section">
        <fieldset>
            <legend>
                <OpencastIcon small/>
                {{ $gettext('Globale Einstellungen') }}
            </legend>

            <ConfigOption v-for="setting in global_settings"
                :key="setting.name" :setting="setting"
                :languages="config_list.languages"
                @updateValue="updateValue"
            />
        </fieldset>
    </div>
</template>

<script>
import { mapGetters } from "vuex";

import OpencastIcon from "@/components/OpencastIcon";
import ConfigOption from "@/components/Config/ConfigOption";

export default {
    name: "GlobalOptions",

    components: {
        OpencastIcon,
        ConfigOption
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
                else {
                    if (this.config_list.settings[id].name == 'OPENCAST_TOS') {
                        if (this.showTos) {
                            settings.push(this.config_list.settings[id]);
                        }
                    } else {
                        settings.push(this.config_list.settings[id]);
                    }
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
                    value: Number(server.id),
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

    },

    methods: {
        updateValue(setting, newValue) {
            for (let id in this.config_list.settings) {
                if (this.config_list.settings[id].name == setting.name) {

                    this.config_list.settings[id].value = newValue;
                    return;
                }
            }
        }
    }
}
</script>
