<template>
    <div class="oc--ltiauth">
        <iframe
            v-for="server in simple_config_list.server"
            v-bind:key="server.id"
            :src="authUrl(server.id)">
        </iframe>
    </div>
</template>

<script>

import { mapGetters } from "vuex";

export default {
    name: "LtiAuth",

    computed: {
        ...mapGetters(['simple_config_list', 'cid', 'ltiReauthenticate']),
    },

    methods: {
        authUrl(config_id) {
            console.log('authUrl', config_id, this.simple_config_list);

            // check, if we are in a course
            if (this.cid) {
                return window.OpencastPlugin.AUTH_URL + '?config_id=' + config_id + '&cid=' + this.cid;
            } else {
                return window.OpencastPlugin.AUTH_URL + '?config_id=' + config_id;
            }
        }
    },

    mounted() {
        this.$store.dispatch('simpleConfigListRead');
    }
}
</script>
