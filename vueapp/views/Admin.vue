<template>
    <div class="oc--admin--container">
        <MessageList />

        <div class="oc--admin--server-card-grid">
            <ServerCard v-for="config in config_list.server"
                :config="config" :key="config.id"/>

            <ServerCard isAddCard />
        </div>

        <AdminConfigs />
    </div>
</template>

<script>
import { mapGetters } from "vuex";

import StudipButton from "@studip/StudipButton";
import StudipIcon from "@studip/StudipIcon";
import MessageList from "@/components/MessageList";

import ServerCard from "@/components/Config/ServerCard";
import AdminConfigs from "@/components/Config/AdminConfigs";

export default {
    name: "Admin",
    components: {
        StudipButton,
        StudipIcon,
        MessageList,
        ServerCard,
        AdminConfigs
    },

    computed: {
        ...mapGetters(['config_list'])
    },

    methods: {

    },

    mounted() {
        this.$store.dispatch('configListRead');
    }
};
</script>
