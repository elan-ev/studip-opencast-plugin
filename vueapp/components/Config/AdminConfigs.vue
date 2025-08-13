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

            <SchedulingOptions v-if="is_scheduling_enabled"
                :config_list="config_list"
                @openEditModalInParent="openSchedulingEditModal"
            />

            <footer>
                <StudipButton icon="accept" @click.prevent="storeAdminConfig($event)">
                    <span>
                        {{ $gettext('Einstellungen speichern') }}
                    </span>
                </StudipButton>
            </footer>
        </form>

        <!-- Modals outside of form -->
        <SchedulingEditModal v-if="shouldSchedulingEditModalBeVisible !== false" />

        <StudipDialog
            v-if="shouldConfirmationModalBeVisible !== false"
            :title="confirmationModalObj.title"
            :confirmText="$gettext('Akzeptieren')"
            :confirmClass="'accept'"
            :closeText="$gettext('Abbrechen')"
            :closeClass="'cancel'"
            :height="confirmationModalObj?.height ?? '220'"
            :width="confirmationModalObj?.width ?? '500'"
            @close="closeAllModals"
            @confirm="confirmationModalObj.confirm"
            :alert="confirmationModalObj.text"
        />
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
import SchedulingEditModal from "@/components/Config/SchedulingEditModal";
import StudipDialog from '@studip/StudipDialog';

export default {
    name: "AdminConfigs",
    components: {
        StudipButton,       StudipIcon,
        MessageList,        MessageBox,
        GlobalOptions,      SchedulingOptions,
        SchedulingEditModal, StudipDialog
    },

    data() {
        return {

        }
    },

    computed: {
        ...mapGetters([
            'config_list',
            'simple_config_list',
            'shouldConfirmationModalBeVisible',
            'confirmationModalObj',
            'shouldSchedulingEditModalBeVisible'
        ]),

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
                    view.$store.dispatch('configListRead');
                    if (data.messages.length) {
                        for (let i = 0; i < data.messages.length; i++ ) {
                            view.$store.dispatch('addMessage', data.messages[i]);
                        }
                    }
                    view.$store.dispatch('toggleSchedulingUnsavedChanges', false);
                }).catch(function (error) {
                    view.$store.dispatch('addMessage', {
                        type: 'error',
                        text: view.$gettext('Einstellungen konnten nicht gespeichert werden!')
                    });
                });
        },

        migratePlaylists()
        {
            this.$store.dispatch('configMigratePlaylists')
            .then(() => {
                this.$store.dispatch('addMessage', {
                    'type': 'success',
                    'text': this.$gettext('Die Wiedergabelisten wurden übertragen!')
                })

                this.config_list.can_migrate_playlists = undefined;
            });
        },

        closeAllModals() {
            this.$store.dispatch('closeSchedulingEditModal');
            this.$store.dispatch('closeConfirmationModal');
        }
    },
}
</script>
