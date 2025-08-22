<template>
    <div>
        <StudipDialog
            :title="$gettext('Aufzeichnung zum Löschen markieren')"
            :confirmText="$gettext('Löschen')"
            :confirmClass="'accept'"
            :closeText="$gettext('Abbrechen')"
            :closeClass="'cancel'"
            height="275"
            @close="decline"
            @confirm="removeVideo"
        >
            <template v-slot:dialogContent>
                <span v-if="dialogContent">
                    {{ dialogContent }}
                </span>
                <span v-else>
                     <p>
                        {{ $gettext('Möchten Sie die Aufzeichnung wirklich zum Löschen markieren?') }}
                    </p>
                    <p>
                        {{ $gettext('Die Aufzeichnung wird damit in den "Gelöschte Videos" Bereich '
                            + 'Ihres Arbeitsplatzes verschoben und wird nach %{days} Tagen automatisch gelöscht. '
                            + 'Bis zu diesem Zeitpunkt können Sie die Aufzeichnung wiederherstellen.',
                            { days: simple_config_list.settings.OPENCAST_CLEAR_RECYCLE_BIN_INTERVAL })
                        }}
                    </p>
                </span>
            </template>
        </StudipDialog>
    </div>
</template>

<script>
import { mapGetters } from "vuex";

import StudipDialog from '@studip/StudipDialog'

export default {
    name: 'BulkVideoDelete',

    components: {
        StudipDialog
    },

    props: ['event', 'dialogContent'],

    emits: ['done', 'cancel'],

    computed: {
        ...mapGetters([
            'simple_config_list'
        ]),
    },

    methods: {
        removeVideo()
        {
            let promises = [];

            for (let id in this.event) {
                promises.push(this.$store.dispatch('deleteVideo', this.event[id]));
            }

            Promise.all(promises).then((values) => {
                let success  = 0;
                let errors   = 0;
                let warnings = 0;

                for (let i = 0; i < values.length; i++) {
                    if (values[i].data.message.type == 'error') errors++;
                    if (values[i].data.message.type == 'success') success++;
                    if (values[i].data.message.type == 'warning') warnings++;
                }

                let type = 'success';
                if (errors > 0 && success == 0) {
                    type = 'error';
                } else if ((errors > 0 && success > 0) || warnings > 0) {
                    type = 'warning';
                }

                this.$store.dispatch('addMessage', {
                    type: type,
                    text: this.$gettext('%{ num_success } Videos wurden gelöscht, bei %{ num_errors } Videos gab es Probleme.', {
                        num_success: success,
                        num_errors:  (errors + warnings)
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
