<template>
  <div>
    <form class="default">
        <MessageBox type="info" v-if="!is_scheduling_enabled && is_scheduling_configured">
            {{ $gettext('Es wurden bisher keine Räume mit Aufzeichnungstechnik konfiguriert! Bitte konsultieren Sie die Hilfeseiten.') }}
            <a :href="$filters.helpurl('OpencastV3Administration#toc2')"
                target="_blank"
            >
                {{ $gettext('Aufzeichnungsplanung konfigurieren') }}
            </a>
        </MessageBox>

        <GlobalOptions :config_list="config_list"/>

        <SchedulingOptions v-if="is_scheduling_enabled" :config_list="config_list"/>

        <footer>
            <StudipButton icon="accept" @click.prevent="storeAdminConfig($event)">
                <span v-translate>Einstellungen speichern</span>
            </StudipButton>
        </footer>
    </form>
  </div>
</template>

<script>
import { mapGetters } from "vuex";

import StudipButton from "@studip/StudipButton";
import StudipIcon from "@studip/StudipIcon";
import MessageList from "@/components/MessageList";
import GlobalOptions from "@/components/Config/GlobalOptions";
import SchedulingOptions from "@/components/Config/SchedulingOptions";
import MessageBox from "@/components/MessageBox";

export default {
    name: "AdminConfigs",
    components: {
        StudipButton,       StudipIcon,
        MessageList,        MessageBox,
        GlobalOptions,      SchedulingOptions
    },

    data() {
        return {

        }
    },

    computed: {
        ...mapGetters(['config_list', 'simple_config_list']),

        is_scheduling_enabled() {
            return this.config_list?.scheduling && this.is_scheduling_configured;
        },

        is_scheduling_configured() {
            for (let key in this.config_list.settings) {
                if (this.config_list.settings[key].name == 'OPENCAST_ALLOW_SCHEDULER') {
                    return (this.config_list.settings[key].value == true)
                }
            }
        }
    },

    methods: {
        storeAdminConfig(event) {
            event.preventDefault();
            this.$store.dispatch('clearMessages');
            let params = {};
            if (this.config_list?.settings) {
                params.settings = this.config_list.settings;
            }
            if (this.is_scheduling_enabled) {
                params.resources = this.config_list.scheduling.resources;
            }
            this.$store.dispatch('configListUpdate', params)
                .then(({ data }) => {
                    if (data.messages.length) {
                        for (let i = 0; i < data.messages.length; i++ ) {
                            this.$store.dispatch('addMessage', data.messages[i]);
                        }
                    }
                }).catch(function (error) {
                    this.$store.dispatch('addMessage', {
                        type: 'error',
                        text: this.$gettext('Einstellungen konnten nicht gespeichert werden!')
                    });
                });
        }
    },
}
</script>