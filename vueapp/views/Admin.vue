<template>
    <div class="oc--admin--container">
        <MessageList />

        <div class="oc--admin--server-card-grid">
            <ServerCard v-for="config in config_list.server" :config="config" :key="config.id" />
            <ServerCardAdd />
        </div>

        <AdminConfigs />
    </div>
</template>

<script>
import { mapGetters } from 'vuex';

import MessageList from '@/components/MessageList';

import ServerCard from '@/components/Config/ServerCard';
import ServerCardAdd from '@/components/Config/ServerCardAdd';
import AdminConfigs from '@/components/Config/AdminConfigs';

export default {
    name: 'Admin',
    components: {
        MessageList,
        ServerCard,
        ServerCardAdd,
        AdminConfigs,
    },

    computed: {
        ...mapGetters('config', ['config_list']),
    },

    mounted() {
        this.$store.dispatch('config/configListRead');
    },
};
</script>
