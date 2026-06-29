<template>
    <div class="oc--ltiauth">
        <template
            v-for="server in simple_config_list.server"
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
export default {
    name: "LtiAuth",

    props: ['simple_config_list'],

    methods: {
        authUrl(config_id, num) {
            // check, if we are in a course
            if (this.simple_config_list.course_id) {
                return this.simple_config_list.auth_url + '/' + num + '?config_id=' + config_id + '&cid=' + this.simple_config_list.course_id;
            } else {
                return this.simple_config_list.auth_url + '/' + num + '?config_id=' + config_id;
            }
        }
    }
}
</script>
