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

    computed: {
        ...mapGetters(['simple_config_list', 'cid']),
    },

    methods: {
        authUrl(config_id, num) {
            // check, if we are in a course
            if (this.cid) {
                return window.OpencastPlugin.AUTH_URL + '/' + num + '?config_id=' + config_id + '&cid=' + this.cid;
            } else {
                return window.OpencastPlugin.AUTH_URL + '/' + num + '?config_id=' + config_id;
            }
        }
    },

    mounted() {
        this.$store.dispatch('simpleConfigListRead');
    }
}
</script>
