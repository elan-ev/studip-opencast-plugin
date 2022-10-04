<template>
    <div class="oc--litauth">
        <iframe v-for="lti in authQueue" v-bind:key="lti.lti.launch_url"
            :src="authUrl(lti)">
        </iframe>
    </div>
</template>

<script>

import { mapGetters } from "vuex";

export default {
    name: "LtiAuth",

    computed: {
        ...mapGetters(['authQueue'])
    },

    methods: {
        authUrl(lti) {
            console.log(JSON.parse(JSON.stringify(lti)));
            return window.OpencastPlugin.AUTH_URL + '?launch_url=' + lti.lti.launch_url + '&launch_data=' + JSON.stringify(lti.lti.launch_data);
        }
    }
}
</script>
