<template>
    <div>
        <ServerCard v-for="config in config_list.server"
            :config="config" :key="config.id"/>

        <ServerCard isAddCard />

        <div class="ui-helper-clearfix"></div>
        <br/>

        <GlobalOptions />
    </div>
</template>

<script>
import { mapGetters } from "vuex";

import StudipButton from "@studip/StudipButton";
import StudipIcon from "@studip/StudipIcon";
import MessageBox from "@/components/MessageBox";

import ServerCard from "@/components/Config/ServerCard";
import GlobalOptions from "@/components/Config/GlobalOptions";

export default {
    name: "AdminOverview",
    components: {
        StudipButton, StudipIcon,
        MessageBox, ServerCard,
        GlobalOptions
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
        this.$store.dispatch('configListRead');
    }
};
</script>
