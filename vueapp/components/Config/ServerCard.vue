<template>
    <div class="oc--admin--server-card">
        <div class="oc--admin--server-image">
            <OpencastIcon />
            <span v-if="!isAddCard" class="oc--admin--server-id">
                #{{ config.id }}
            </span>
            <span v-if="!isAddCard && checkFailed" class="oc--admin--server-icons">
                <studip-icon shape="exclaim-circle" role="status-red" :size="32"/>
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
            @close="closeEditServer"
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
            checkFailed: false,
            interval: null,
            interval_counter: 0,
            error_msg: this.$gettext('Überprüfung der LTI Verbindung fehlgeschlagen! '
                + 'Kontrollieren Sie die eingetragenen Daten und stellen Sie sicher, '
                + 'dass Cross-Origin Aufrufe von dieser Domain aus möglich sind! '
                + 'Denken sie auch daran, in Opencast die korrekten access-control-allow-* '
                + 'Header zu setzen.'
            )
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
            'errors'
        ])
    },

    methods: {
        showEditServer() {
            this.isShow = true;

            if (this.checkFailed && !this.errors.find((e) => e === this.error_msg)) {
                this.$store.dispatch('errorCommit', this.error_msg);
            }
        },

        closeEditServer() {
            this.$store.dispatch('errorRemove', this.error_msg);
            this.isShow = false;
        },

        checkLTIPeriodically() {
            let view = this;

            // periodically check, if lti is authenticated
            view.interval = setInterval(() => {
                view.$store.dispatch('checkLTIAuthentication', {id: view.config.id, name: view.config.service_url})
                .then(() => {
                    // Make sure error is removed when authenticated
                    if (view.isLTIAuthenticated[view.config.id]) {
                        view.$store.dispatch('errorRemove', this.error_msg);
                        view.checkFailed = false;
                        clearInterval(view.interval);
                    } else {
                        view.checkFailed = true;
                    }

                    view.interval_counter++;
                    if (view.interval_counter > 10) {
                        clearInterval(view.interval);
                    }
                });
            }, 2000);
        }
    },

    mounted() {
        if (!this.isAddCard) {
            this.checkLTIPeriodically();
        }
    }
}
</script>
