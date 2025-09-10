<template>
    <div class="oc--admin--server-card">
        <div class="oc--admin-server-card-header">
            <OpencastIcon />
            <div>
                <span>{{ config.service_url }}</span>
                <span>{{ $gettext('Opencast-Version:') }} {{ config.service_version }}</span>
            </div>
        </div>
        <div class="oc--admin--server-data">
            <div class="oc--admin-server-switch">
                <label>
                    <LayoutSwitch
                        :model-value="localActive"
                        @update:model-value="onToggleActive"
                        :disabled="toggling"
                        aria-label="Server aktiv"
                    />
                    {{ $gettext('Server aktiv') }}
                </label>
            </div>
            <div class="oc--admin--server-status">
                <template v-if="checkFailed">
                    <StudipIcon shape="exclaim-circle" role="attention" :size="24" />
                    {{ $gettext('Server nicht erreichbar') }}
                </template>
                <template v-else>
                    <StudipIcon shape="check-circle" role="accept" :size="24" />
                    {{ $gettext('Server verbunden') }}
                </template>
            </div>

            <footer>
                <button class="button edit" @click="showEditServer">{{ $gettext('Einstellungen') }}</button>
            </footer>
            
        </div>
        <EditServer v-if="isShow" :id="config ? config.id : 'new'" :config="config" @close="isShow = false" />
    </div>
</template>

<script>
import { mapGetters } from 'vuex';

import OpencastIcon from '@/components/OpencastIcon';
import StudipIcon from '@studip/StudipIcon';
import EditServer from '@/components/Config/EditServer';
import LayoutSwitch from '@/components/Layouts/LayoutSwitch';

export default {
    name: 'ServerCard',

    props: {
        config: {
            default: null,
        },

        isAddCard: {
            type: Boolean,
            default: false,
        },
    },

    data() {
        return {
            isShow: false,
            interval: null,
            interval_counter: 0,
            error_msg: {
                type: 'error',
                text: this.$gettext(
                    'Überprüfung der Verbindung fehlgeschlagen! ' +
                        'Kontrollieren Sie die eingetragenen Daten und stellen Sie sicher, ' +
                        'dass Cross-Origin Aufrufe von dieser Domain aus möglich sind! ' +
                        'Denken Sie auch daran, in Opencast die korrekten access-control-allow-* ' +
                        'Header zu setzen.'
                ),
                dialog: true,
            },
            toggling: false,
            localActive: this.config.active,
        };
    },

    components: {
        OpencastIcon,
        EditServer,
        StudipIcon,
        LayoutSwitch,
    },

    computed: {
        ...mapGetters('opencast', ['isLTIAuthenticated']),

        checkFailed() {
            if (this.isAddCard) {
                return false;
            }

            return this.isLTIAuthenticated[this.config.id] === false;
        },

        validOpencastVersion() {
            const version = this.config?.service_version;
            if (typeof version !== 'string') return false;

            const regex = /^\d+\.\d+(?:\.\d+)?(?:[-.][a-zA-Z0-9]+)*$/;
            return regex.test(version);
        },
    },

    methods: {
        showEditServer() {
            this.isShow = true;

            if (this.checkFailed) {
                this.$store.dispatch('messages/addMessage', this.error_msg);
            }
        },
        async onToggleActive(nextValue) {
            if (this.toggling || nextValue === this.config.active) return;

            const prev = this.config.active;
            this.localActive = nextValue; // optimistic UI
            this.toggling = true;

            try {
                const { data } = await this.$store.dispatch('config/configSetActivation', {
                    id: this.config.id,
                    active: nextValue,
                });

                await this.$store.dispatch('config/configListRead', data.config);
            } catch (e) {
                // Revert bei Fehler
                this.localActive = prev;
                this.$store.dispatch('messages/addMessage', {
                    type: 'error',
                    text: this.$gettext('Aktualisierung fehlgeschlagen. Bitte erneut versuchen.'),
                });
            } finally {
                this.toggling = false;
            }
        },
    },

    watch: {
        checkFailed: function (newVal) {
            if (newVal && this.isShow) {
                this.$store.dispatch('messages/addMessage', this.error_msg);
            } else {
                this.$store.dispatch('messages/removeMessage', this.error_msg);
            }
        },
    },
};
</script>
