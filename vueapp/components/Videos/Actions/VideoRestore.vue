<template>
    <div>
        <StudipDialog
            :title="$gettext('Aufzeichnung Wiederherstellen')"
            :confirmText="$gettext('Akzeptieren')"
            :confirmClass="'accept'"
            :closeText="$gettext('Abbrechen')"
            :closeClass="'cancel'"
            height="175"
            @close="decline"
            @confirm="restoreVideo"
        >
            <template v-slot:dialogContent>
                <translate>Möchten Sie die Aufzeichnung wirklich wiederherstellen?</translate>
            </template>
        </StudipDialog>
    </div>
</template>

<script>
import StudipDialog from '@studip/StudipDialog'

export default {
    name: 'VideoRestore',

    components: {
        StudipDialog
    },

    props: ['event'],

    emits: ['done', 'cancel'],

    methods: {
        async restoreVideo() {
            await this.$store.dispatch('restoreVideo', this.event.token)
            .then(({ data }) => {
                this.$store.dispatch('addMessage', data.message);
                let emit_action = data.message.type == 'success' ? 'refresh' : '';
                this.$emit('done', emit_action);
            }).catch(() => {
                this.$emit('cancel');
            });
        },

        decline() {
            this.$emit('cancel');
        },
    },
}
</script>