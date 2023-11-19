<template>
    <div>
        <BulkVideoDelete
            @cancel="$emit('cancel')"
            @done="$emit('done', 'refresh')"
            :event="event"
            :dialogContent="$gettext('Möchten Sie die Aufzeichnungen wirklich unwiderruflich entfernen?')" />
    </div>
</template>

<script>
import BulkVideoDelete from '@/components/Videos/BulkActions/VideoDelete.vue';

export default {
    name: 'VideoDelete',

    components: {
        BulkVideoDelete
    },

    props: ['event'],

    emits: ['done', 'cancel'],
}
</script>