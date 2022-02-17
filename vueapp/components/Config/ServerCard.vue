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
        <div @click="addEditServer" class="oc--admin--server-data">
            <div v-if="isAddCard" class="oc--admin-server-add" v-translate>
                Neuen Server hinzufügen
            </div>
            <div v-else class="oc--admin--server-data">
                <div>
                    <span>Server:</span>
                    {{ config.service_url }}
                </div>
                <div>
                    <span>Opencast-Version:</span>
                    {{ config.service_version }}
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import OpencastIcon from "@/components/OpencastIcon";

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

    components: {
        OpencastIcon
    },

    methods: {
        addEditServer() {
            if (this.isAddCard) {
                this.$router.push({ name: 'add_server'})
            } else {
                this.$router.push({ name: 'edit_server', params: { config: this.config, id: this.config.id }})
            }
        }
    }
}
</script>
