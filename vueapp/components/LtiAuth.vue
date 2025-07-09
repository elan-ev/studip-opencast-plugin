<template>
    <div class="oc--ltiauth">
        <template
            v-for="server in simple_config_list.server"
            v-bind:key="server.id"
        >
            <!-- iterate over all opencast nodes for this server as well -->
            <iframe
                v-for="i in server.lti_num"
                v-bind:key="i"
                :src="authUrl(server.id, i - 1)">
            </iframe>
        </template>
    </div>
</template>

<script>

import { mapGetters } from "vuex";

export default {
    name: "LtiAuth",

    data() {
        return {
            interval: null,
            interval_counter: 0,
            interval_limit: 10
        }
    },

    computed: {
        ...mapGetters(['simple_config_list', 'isLTIAuthenticated', 'cid']),
    },

    methods: {
        authUrl(config_id, num) {
            // check, if we are in a course
            if (this.cid) {
                return window.OpencastPlugin.AUTH_URL + '/' + num + '?config_id=' + config_id + '&cid=' + this.cid;
            } else {
                return window.OpencastPlugin.AUTH_URL + '/' + num + '?config_id=' + config_id;
            }
        },

        checkLTIPeriodically() {
            let view = this;

            const server_ids = Object.keys(view.simple_config_list['server']);

            // periodically check, if lti is authenticated
            view.interval = setInterval(async () => {
                // Create an array of promises for checking each server in parallel
                const promises = server_ids.map(async (id) => {
                    await view.$store.dispatch('checkLTIAuthentication', view.simple_config_list['server'][id]);
                    // Remove server from list, if authenticated
                    if (view.isLTIAuthenticated[id]) {
                        server_ids.splice(server_ids.indexOf(id), 1);
                    }
                });
                // Wait for all checks to finish
                await Promise.all(promises);

                view.interval_counter++;
                if (view.interval_counter > view.interval_limit) {
                    clearInterval(view.interval);
                }
            }, 2000);
        }
    },

    mounted() {
        this.$store.dispatch('simpleConfigListRead').then(() => {
            this.checkLTIPeriodically();
        });
    }
}
</script>
