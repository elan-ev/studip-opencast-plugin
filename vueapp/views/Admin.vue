<template>
    <div>
        <MessageList />

        <div class="oc--admin--server-card-grid">
            <ServerCard v-for="config in config_list.server"
                :config="config" :key="config.id"/>

            <ServerCard isAddCard />
        </div>

        <GlobalOptions />
    </div>
</template>

<script>
import { mapGetters } from "vuex";

import StudipButton from "@studip/StudipButton";
import StudipIcon from "@studip/StudipIcon";
import MessageList from "@/components/MessageList";

import ServerCard from "@/components/Config/ServerCard";
import GlobalOptions from "@/components/Config/GlobalOptions";

export default {
    name: "Admin",
    components: {
        StudipButton,
        StudipIcon,
        MessageList,
        ServerCard,
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
