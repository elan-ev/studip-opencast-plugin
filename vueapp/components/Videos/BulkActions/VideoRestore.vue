<template>
    <div>
        <StudipDialog
            :title="$gettext('Aufzeichnung wiederherstellen')"
            :confirmText="$gettext('Akzeptieren')"
            :confirmClass="'accept'"
            :closeText="$gettext('Abbrechen')"
            :closeClass="'cancel'"
            height="175"
            @close="decline"
            @confirm="restoreVideo"
        >
            <template v-slot:dialogContent>
                {{ $gettext('Möchten Sie die Aufzeichnungen wiederherstellen?') }}
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
        async restoreVideo()
        {
            let promises = [];

            for (let id in this.event) {
                promises.push(this.$store.dispatch('videos/restoreVideo', this.event[id]));
            }

            Promise.all(promises).then((values) => {
                let success = 0;
                let errors  = 0;

                for (let i = 0; i < values.length; i++) {
                    if (values[i].data.message.type == 'error') errors++;
                    if (values[i].data.message.type == 'success') success++;
                }

                let type = 'success';
                if (errors > 0 && success == 0) {
                    type = 'error';
                } else if (errors > 0 && success > 0) {
                    type = 'warning';
                }

                this.$store.dispatch('messages/addMessage', {
                    type: type,
                    text: this.$gettext('%{ num_success } Videos wurden wiederhergestellt, bei %{ num_errors } Videos gab es Probleme.', {
                        num_success: success,
                        num_errors:  errors
                    })
                });

                this.$emit('done', 'refresh');
            });
        },

        decline()
        {
            this.$emit('cancel');
        },
    },
}
</script>