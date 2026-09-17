<template>
    <div class="oc--admin--server-card">
        <div class="oc--admin--server-image">
            <OpencastIcon />
            <span v-if="!isAddCard" class="oc--admin--server-id">
                <studip-icon
                    v-if="config.active"
                    @click="toogleServer(false)"
                    shape="checkbox-checked"
                    role="clickable"
                    :size="32"
                    style="cursor: pointer"
                />
                <studip-icon
                    v-else
                    @click="toogleServer(true)"
                    shape="checkbox-unchecked"
                    role="clickable"
                    :size="32"
                    style="cursor: pointer"/>
            </span>
            <span class="oc--admin--server-icons">
                <div data-tooltip class="tooltip" v-if="!isAddCard && checkFailed">
                    <span class="tooltip-content" style="display: none">
                        {{ $gettext('LTI Verbindungstest fehlgeschlagen.') }}
                    </span>
                    <studip-icon shape="exclaim-circle" role="status-red" :size="32"/>
                </div>
            </span>
            <span v-if="isAddCard" class="oc--admin--server-id">
                +
            </span>
        </div>
        <div @click="showEditServer" class="oc--admin--server-data">
            <div v-if="isAddCard" class="oc--admin--server-data">
                <div class="oc--admin-server-add">
                    {{ $gettext('Neuen Server hinzufügen') }}
                </div>
            </div>
            <div v-else class="oc--admin--server-data">
                <div>
                    {{ config.service_url }}
                </div>
                <div v-if="validOpencastVersion">
                    {{ $gettext('Opencast-Version:') }} {{ config.service_version }}
                </div>
            </div>
        </div>
        <EditServer v-if="isShow"
            :id="config ? config.id : 'new'"
            :config="config"
            @stored="handleStored"
            @close="handleClose"
        />
    </div>
</template>

<script>
import { mapGetters } from "vuex";

import OpencastIcon from "@/components/OpencastIcon";
import StudipIcon from '@studip/StudipIcon.vue';
import EditServer from "@/components/Config/EditServer";

export default {
    name: 'ServerCard',

    props: {
        config: {
            default: null
        },

        isAddCard: {
            type: Boolean,
            default: false
        }
    },

    data() {
        return {
            isShow: false,
            interval: null,
            interval_counter: 0,
            interval_limit: 10,
            checkAfterClose: false,
            storedConfigId: null,
            connection_info_msg: {
                type: 'info',
                text: this.$gettext('Überprüfung der LTI Verbindung fehlgeschlagen! '
                    + 'Kontrollieren Sie die eingetragenen Daten (LTI Consumerkey und LTI Consumersecret) und stellen Sie sicher, '
                    + 'dass Cross-Origin Aufrufe von dieser Domain aus möglich sind! '
                    + 'Denken Sie auch daran, in Opencast die korrekten access-control-allow-* '
                    + 'Header zu setzen.'
                ),
                dialog: false
            }
        }
    },

    components: {
        OpencastIcon,
        EditServer,
        StudipIcon
    },

    computed: {
        ...mapGetters([
            'isLTIAuthenticated',
            'simple_config_list'
        ]),

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
        }
    },

    methods: {
        toogleServer(active) {
            this.config.active = active;
            this.$store.dispatch('configSetActivation', {id: this.config.id, active: active})
            .then(({ data }) => {
                this.$store.dispatch('configListRead', data.config)
                .then(() => {
                    if (this.config.active) {
                        this.$store.dispatch("addMessage", {
                            type: "success",
                            text: this.$gettext("Server wurde erfolgreich aktiviert")
                        });
                    }
                    else {
                        this.$store.dispatch("addMessage", {
                            type: "success",
                            text: this.$gettext("Server wurde erfolgreich deaktiviert")
                        });
                    }
                });
            });
        },

        showEditServer() {
            this.isShow = true;
        },

        handleStored(config) {
            const configId = config?.id ?? this.config?.id;
            if (!configId) {
                return;
            }

            this.stopConnectionCheck();
            this.storedConfigId = configId;
            this.checkAfterClose = true;
            this.$store.dispatch('resetLTIAuthentication', configId);
            this.$store.dispatch('removeMessage', this.connection_info_msg);
        },

        handleClose() {
            this.isShow = false;

            if (!this.checkAfterClose) {
                return;
            }

            this.checkAfterClose = false;
            this.$nextTick(() => this.startConnectionCheck(this.storedConfigId));
        },

        async startConnectionCheck(configId) {
            await this.$store.dispatch('authenticateLti');

            const server = this.simple_config_list?.server?.[configId];
            if (!server) {
                return;
            }

            const check = async () => {
                await this.$store.dispatch('checkLTIAuthentication', server);
                this.interval_counter++;

                if (this.isLTIAuthenticated[configId] || this.interval_counter >= this.interval_limit) {
                    this.stopConnectionCheck();
                }
            };

            // The new LTI iframe must finish its signed launch before checking the
            // session. An immediate request can still see the previous session and
            // incorrectly stop the check before the updated credentials are used.
            this.interval = setTimeout(async () => {
                await check();
                if (!this.isLTIAuthenticated[configId] && this.interval_counter < this.interval_limit) {
                    this.interval = setInterval(check, 2000);
                }
            }, 2000);
        },

        stopConnectionCheck() {
            if (this.interval) {
                clearInterval(this.interval);
                this.interval = null;
            }
            this.interval_counter = 0;
        },
    },

    watch: {
        checkFailed: function (newVal) {
            if (newVal && !this.isShow) {
                this.$store.dispatch('addMessage', this.connection_info_msg);
            } else {
                this.$store.dispatch('removeMessage', this.connection_info_msg);
            }
        }
    },

    beforeUnmount() {
        this.stopConnectionCheck();
    }
}
</script>
