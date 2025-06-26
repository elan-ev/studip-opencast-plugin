<template>
    <div v-if="maintenance_infos.length > 0">
        <MessageBox v-for="maintenance_info in maintenance_infos" type="warning" :html="maintenance_info" />
    </div>
</template>

<script>
import MessageBox from '@/components/MessageBox.vue';

import { mapGetters } from "vuex";

export default {
    name: "MaintenanceMessage",
    components: {
        MessageBox
    },
    computed: {
        ...mapGetters(['simple_config_list']),
    },
    data() {
        return {
            maintenance_infos: [],
            watcher_ran: false
        }
    },

    methods: {
        async printMaintenanceInfos() {
            for (let server_key in this.simple_config_list.server) {
                let server = this.simple_config_list.server[server_key];
                if (server?.maintenance_text && server?.maintenance_mode?.active) {
                    let text = server.maintenance_text ?? '';
                    if (text) {
                        let parsed_text = JSON.parse(text);
                        if (parsed_text?.[this.simple_config_list.user_language]) {
                            this.maintenance_infos.push(parsed_text[this.simple_config_list.user_language]);
                        }
                    }
                }
            }
        }
    },

    watch: {
        simple_config_list(newValue) {
            if (newValue?.server && !this.watcher_ran) {
                this.printMaintenanceInfos();
                this.watcher_ran = true;
            }
        }
    },
};
</script>
