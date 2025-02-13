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
                        {{ $gettext('Verbindungstest fehlgeschlagen.') }}
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
                <div v-if="config.service_version">
                    {{ $gettext('Opencast-Version:') }} {{ config.service_version }}
                </div>
            </div>
        </div>
        <EditServer v-if="isShow"
            :id="config ? config.id : 'new'"
            :config="config"
            @close="isShow = false;"
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
            error_msg: {
                type: 'error',
                text: this.$gettext('Überprüfung der Verbindung fehlgeschlagen! '
                    + 'Kontrollieren Sie die eingetragenen Daten und stellen Sie sicher, '
                    + 'dass Cross-Origin Aufrufe von dieser Domain aus möglich sind! '
                    + 'Denken Sie auch daran, in Opencast die korrekten access-control-allow-* '
                    + 'Header zu setzen.'
                ),
                dialog: true
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
            'isLTIAuthenticated'
        ]),

        checkFailed() {
            if (this.isAddCard) {
                return false;
            }

            return this.isLTIAuthenticated[this.config.id] === false;
        }
    },

    methods: {
        toogleServer(active) {
            this.config.active = active;
            this.$store.dispatch('configUpdate', {id: this.config.id, active: active})
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

            if (this.checkFailed) {
                this.$store.dispatch('addMessage', this.error_msg);
            }
        },
    },

    watch: {
        checkFailed: function (newVal) {
            if (newVal && this.isShow) {
                this.$store.dispatch('addMessage', this.error_msg);
            } else {
                this.$store.dispatch('removeMessage', this.error_msg);
            }
        }
    }
}
</script>
