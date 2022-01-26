<template>
    <div>
        <form class="default">
            <fieldset>
                <legend v-translate>
                    <OpencastIcon small/>
                    Globale Einstellungen
                </legend>
                <label>

                </label>
                <ConfigOption v-for="settings in config_list.settings" :key="settings.name" />
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

export default {
    name: "GlobalOptions",

    components: {
        StudipButton, StudipIcon,
        MessageBox, OpencastIcon,
        ConfigOption
    },

    computed: {
        ...mapGetters(['config', 'config_list'])
    }
}
</script>
