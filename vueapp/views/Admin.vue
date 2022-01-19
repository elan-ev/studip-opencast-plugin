<template>
    <div>
        <OpencastServer v-for="config in config_list"
            :config="config" :key="config.id"/>

        <OpencastAddServer />

        <div class="ui-helper-clearfix"></div>
        <br/>

        <form class="default">
            <fieldset>
                <legend v-translate>
                    Globale Einstellungen
                </legend>
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

import OpencastServer from "@/components/OpencastServer";
import OpencastAddServer from "@/components/OpencastAddServer";

export default {
    name: "AdminBasic",
    components: {
        StudipButton, StudipIcon,
        MessageBox,
        OpencastServer,OpencastAddServer
    },
    data() {
        return {

        }
    },
    computed: {
        ...mapGetters(['config', 'config_list'])
    },
    methods: {

    },
    mounted() {
        store.dispatch('configListRead');
    }
};
</script>
