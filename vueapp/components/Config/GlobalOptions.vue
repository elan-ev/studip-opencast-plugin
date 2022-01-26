<template>
    <div>
        <form class="default">
            <fieldset>
                <legend v-translate>
                    <OpencastIcon small/>
                    Globale Einstellungen
                </legend>

                <ConfigOption v-for="setting in global_settings"
                    :key="setting.name" :setting="setting"
                    @updateValue="updateValue"/>
            </fieldset>

            <fieldset v-if="showTos">
                <legend v-translate>
                    Terms of service
                </legend>

                <I18NText content="setting" />
            </fieldset>

            <footer>
                <StudipButton icon="accept" v-translate>
                    Einstellungen speichern
                </StudipButton>
            </footer>
        </form>
        <pre>
        {{ config_list.settings }}
        </pre>
    </div>
</template>

<script>
import { mapGetters } from "vuex";
import store from "@/store";

import StudipButton from "@/components/StudipButton";
import StudipIcon from "@/components/StudipIcon";
import MessageBox from "@/components/MessageBox";
import OpencastIcon from "@/components/OpencastIcon";
import ConfigOption from "@/components/Config/ConfigOption";
import I18NText from "@/components/Config/I18NText";

export default {
    name: "GlobalOptions",

    components: {
        StudipButton, StudipIcon,
        MessageBox, OpencastIcon,
        ConfigOption, I18NText
    },

    computed: {
        ...mapGetters(['config', 'config_list']),

        /**
         * The list of global settings, cleaned by settings which should not be displayed directly
         *
         * @return {Object} settings list
         */
        global_settings() {
            let settings = [];
            for (let id in this.config_list.settings) {
                if (this.config_list.settings[id].name != 'OPENCAST_TOS') {
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
        }
    }
}
</script>
