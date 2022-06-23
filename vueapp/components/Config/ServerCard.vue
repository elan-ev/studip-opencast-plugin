<template>
    <div class="oc--admin--server-card">
        <div class="oc--admin--server-image">
            <OpencastIcon />
            <span v-if="config" class="oc--admin--server-id">
                #{{ config.id }}
            </span>
            <span v-else class="oc--admin--server-id">
                +
            </span>
        </div>
        <div @click="showEditServer" class="oc--admin--server-data">
            <div v-if="isAddCard" class="oc--admin--server-data">
                <div class="oc--admin-server-add" v-translate>
                    Neuen Server hinzufügen
                </div>
            </div>
            <div v-else class="oc--admin--server-data">
                <div>
                    {{ config.service_url }}
                </div>
                <div v-if="config.service_version">
                    Opencast-Version: {{ config.service_version }}
                </div>
            </div>
        </div>
        <EditServer v-if="isShow"
            :id="config ? config.id : 'new'"
            :config="config"
            @close="isShow = false"
        />
    </div>
</template>

<script>
import OpencastIcon from "@/components/OpencastIcon";
import EditServer from "@/components/Config/EditServer";

export default {
    name: 'ServerCard',

    props: {
        config: {
            default: null
        },

        isAddCard: {
            type: Boolean,
            default: false
        }
    },

    data() {
        return {
            isShow: false
        }
    },

    components: {
        OpencastIcon,
        EditServer,
    },

    methods: {
        showEditServer() {
            this.isShow = true
        }
    }
}
</script>
