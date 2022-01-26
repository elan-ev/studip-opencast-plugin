<template>
    <div>
        <ServerCard v-for="config in config_list"
            :config="config" :key="config.id"/>

        <ServerCard isAddCard />

        <div class="ui-helper-clearfix"></div>
        <br/>

        <AdminGlobalOptions />
    </div>
</template>

<script>
import { mapGetters } from "vuex";
import store from "@/store";

import StudipButton from "@/components/StudipButton";
import StudipIcon from "@/components/StudipIcon";
import MessageBox from "@/components/MessageBox";

import ServerCard from "@/components/Config/ServerCard";

export default {
    name: "Admin",
    components: {
        StudipButton, StudipIcon,
        MessageBox, ServerCard
    },
    data() {
        return {

        }
    },
    computed: {
        ...mapGetters(['config', 'config_list'])
    },
    methods: {

    },
    mounted() {
        store.dispatch('configListRead');
    }
};
</script>
