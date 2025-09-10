<template>
    <div class="oc--admin--server-card">
        <div class="oc--admin--server-card-add-wrapper">
            <button class="button add" @click="isShow = true">{{ $gettext('Server hinzufügen') }}</button>
        </div>
        <EditServer v-if="isShow" :id="'new'" :config="null" :withoutFieldset="true" @close="isShow = false" />
    </div>
</template>

<script>
import EditServer from '@/components/Config/EditServer';

export default {
    components: {
        EditServer,
    },
    data() {
        return {
            isShow: false,
        };
    },
};
</script>
