<template>
    <div class="oc--admin--section">
        <fieldset>
            <legend>
                <OpencastIcon small/>
                {{ $gettext('Globale Einstellungen') }}
            </legend>

            <ConfigOption v-for="setting in settings.global"
                :key="setting.name" :setting="setting"
                :languages="config_list.languages"
                :disabled="deactivatedOptions[setting.name]"
                @updateValue="updateValue"
            />
        </fieldset>
    </div>

    <div class="oc--admin--section">
        <fieldset>
            <legend>
                <OpencastIcon small/>
                {{ $gettext('Funktionen') }}
            </legend>

            <ConfigOption v-for="setting in settings.functions"
                :key="setting.name" :setting="setting"
                :languages="config_list.languages"
                :disabled="deactivatedOptions[setting.name]"
                @updateValue="updateValue"
            />
        </fieldset>
    </div>

    <div class="oc--admin--section">
        <fieldset>
            <legend>
                <OpencastIcon small/>
                {{ $gettext('Rechte') }}
            </legend>

            <ConfigOption v-for="setting in settings.perms"
                :key="setting.name" :setting="setting"
                :languages="config_list.languages"
                :disabled="deactivatedOptions[setting.name]"
                @updateValue="updateValue"
            />
        </fieldset>
    </div>

    <div class="oc--admin--section">
        <fieldset>
            <legend>
                <OpencastIcon small/>
                {{ $gettext('Oberfläche / Texte') }}
            </legend>

            <ConfigOption v-for="setting in settings.ui"
                :key="setting.name" :setting="setting"
                :languages="config_list.languages"
                :disabled="deactivatedOptions[setting.name]"
                @updateValue="updateValue"
            />
        </fieldset>
    </div>

    <div class="oc--admin--section">
        <fieldset>
            <legend>
                <OpencastIcon small/>
                {{ $gettext('Automatisierte Aufzeichnungen') }}
            </legend>

            <ConfigOption v-for="setting in settings.scheduling"
                :key="setting.name" :setting="setting"
                :languages="config_list.languages"
                :disabled="deactivatedOptions[setting.name]"
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

    data() {
        return {
            deactivatedOptions: {}
        }
    },

    computed: {
        ...mapGetters(['config']),

        /**
         * The list of global settings, cleaned by settings which should not be displayed directly
         *
         * @return {Object} settings list
         */
        settings() {
            let settings = {
                global: [],
                functions: [],
                perms: [],
                scheduling: [],
                ui: []
            };

            for (let id in this.config_list.settings) {
                let option = this.config_list.settings[id];
                if (option.name == 'OPENCAST_MEDIADOWNLOAD') {
                    let setting = option;
                    setting.options = this.downloadOptions;
                    settings[option.tag].push(setting);
                }
                else if (option.name == 'OPENCAST_DEFAULT_SERVER') {
                    let setting = option;
                    setting.options = this.defaultServerOptions;
                    settings[option.tag].push(setting);
                }
                else if (
                    option.name == 'OPENCAST_MANAGE_ALL_OC_EVENTS'
                    || option.name == 'OPENCAST_ALLOW_ALTERNATE_SCHEDULE'
                    || option.name == 'OPENCAST_RESOURCE_PROPERTY_ID'
                ) {
                    this.deactivatedOptions[option.name] =
                        !this.getOption('OPENCAST_ALLOW_SCHEDULER').value;

                    settings[option.tag].push(option);
                }
                else if (option.name == 'OPENCAST_TOS') {
                    this.deactivatedOptions[option.name] =
                        !this.getOption('OPENCAST_SHOW_TOS').value;

                    settings[option.tag].push(option);

                } else {
                    settings[option.tag].push(option);
                }
            }

            return settings;
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
                if(server.active) {
                    options.push({
                        value: Number(server.id),
                        description: server.service_url + ''
                    });
                }
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
        },

        getOption(option)
        {
            for (let id in this.config_list.settings) {
                if (this.config_list.settings[id].name == option) {
                    return this.config_list.settings[id]
                }
            }

            return false;
        }
    }
}
</script>
