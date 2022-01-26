<template>
    <div>
        <form class="default">
            <fieldset>
                <legend v-translate>
                    Globale Einstellungen
                </legend>
                {{ config_list }}
                <ConfigOption v-for="settings in config.settings" :key="settings" />
            </fieldset>

            <footer>
                <StudipButton icon="accept" v-translate>
                    Einstellungen speichern
                </StudipButton>
            </footer>
        </form>
    </div>
</template>

<script>
import { mapGetters } from "vuex";
import store from "@/store";

import StudipButton from "@/components/StudipButton";
import StudipIcon from "@/components/StudipIcon";
import MessageBox from "@/components/MessageBox";

export default {
    name: "GlobalOptions",

    components: {
        StudipButton, StudipIcon,
        MessageBox
    },

    computed: {
        ...mapGetters(['config', 'config_list'])
    }
}
</script>
