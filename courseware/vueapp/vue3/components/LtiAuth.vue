<template>
    <div class="oc--ltiauth">
        <template
            v-for="server in configServers"
        >
            <!-- iterate over all opencast nodes for this server as well -->
            <iframe
                v-for="i in server.lti_num"
                v-bind:key="i"
                :src="generateAuthUrl(server.id, i - 1)">
            </iframe>
        </template>
    </div>
</template>

<script setup>
import { computed } from "vue";

const props = defineProps({
    simple_config_list: Object
});

const configServers = computed(() => {
    return props.simple_config_list?.server ?? {};
});

const configAuthUrl = computed(() => {
    return props.simple_config_list?.auth_url ?? null;
});

const configCourseId = computed(() => {
    return props.simple_config_list?.course_id ?? null;
});

// Methods.
const generateAuthUrl = (config_id, num) => {
    const url = new URL(configAuthUrl.value + '/' + num);
    url.searchParams.append('config_id', config_id);
    if (configCourseId.value) {
        url.searchParams.append('cid', configCourseId.value);
    }
    return url.toString();
}
</script>
