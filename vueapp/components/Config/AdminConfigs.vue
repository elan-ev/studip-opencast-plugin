<template>
  <div>
    <form class="default">
        <MessageBox type="warning" v-if="is_scheduling_configured && !is_scheduling_enabled">
            {{ $gettext('Es wurden bisher keine Räume mit Aufzeichnungstechnik konfiguriert! Bitte konsultieren Sie die Hilfeseiten.') }}
            <a :href="$filters.helpurl('OpencastV3Administration#toc2')"
                target="_blank"
            >
                {{ $gettext('Aufzeichnungsplanung konfigurieren') }}
            </a>
        </MessageBox>

        <MessageBox type="info" v-if="canMigratePlaylists">
            {{ $gettext('Sie verwenden Opencast 16 oder höher und können die Wiedergabelisten mit zu Opencast übertragen und die automatische Synchronisation einschalten.') }}
            <br>
            <a @click.stop="migratePlaylists" style="cursor: pointer">
                {{ $gettext('Synchronisierung aktivieren und Wiedergabelisten übertragen') }}
            </a>
        </MessageBox>

        <GlobalOptions :config_list="config_list"/>

        <SchedulingOptions v-if="is_scheduling_enabled" :config_list="config_list"/>

        <footer>
            <StudipButton icon="accept" @click.prevent="storeAdminConfig($event)">
                <span>
                    {{ $gettext('Einstellungen speichern') }}
                </span>
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
        ...mapGetters('config', ['config_list', 'simple_config_list']),

        is_scheduling_enabled() {
            return this.config_list?.scheduling && this.is_scheduling_configured;
        },

        is_scheduling_configured() {
            for (let key in this.config_list.settings) {
                if (this.config_list.settings[key].name == 'OPENCAST_ALLOW_SCHEDULER') {
                    return (this.config_list.settings[key].value == true)
                }
            }
        },

        canMigratePlaylists()
        {
            return this.config_list.can_migrate_playlists !== undefined;
        }
    },

    methods: {
        storeAdminConfig(event) {
            event.preventDefault();
            let view = this;

            this.$store.dispatch('messages/clearMessages');
            let params = {};

            if (this.config_list?.settings) {
                params.settings = this.config_list.settings;
            }

            if (this.is_scheduling_enabled) {
                params.resources = this.config_list.scheduling.resources;
            }

            this.$store.dispatch('config/configListUpdate', params)
                .then(({ data }) => {
                    view.$store.dispatch('config/configListRead');
                    if (data.messages.length) {
                        for (let i = 0; i < data.messages.length; i++ ) {
                            view.$store.dispatch('messages/addMessage', data.messages[i]);
                        }
                    }
                }).catch(function (error) {
                    view.$store.dispatch('messages/addMessage', {
                        type: 'error',
                        text: view.$gettext('Einstellungen konnten nicht gespeichert werden!')
                    });
                });
        },

        migratePlaylists()
        {
            this.$store.dispatch('config/configMigratePlaylists')
            .then(() => {
                this.$store.dispatch('messages/addMessage', {
                    'type': 'success',
                    'text': this.$gettext('Die Wiedergabelisten wurden übertragen!')
                })

                this.config_list.can_migrate_playlists = undefined;
            });
        }
    },
}
</script>