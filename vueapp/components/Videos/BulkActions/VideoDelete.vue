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
                <translate>
                    Möchten Sie die Aufzeichnungen wirklich zum Löschen markieren?<br/><br/>
                    Die Aufzeichnungen werden damit in den "Gelöschte Videos" Bereich Ihres Arbeitsplatzes verschoben und werden nach {{simple_config_list.settings.OPENCAST_CLEAR_RECYCLE_BIN_INTERVAL}} Tagen automatisch gelöscht.
                    Bis zu diesem Zeitpunkt können Sie die Aufzeichnungen wiederherstellen.
                </translate>
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

    props: ['event'],

    emits: ['done', 'cancel'],

    computed: {
        ...mapGetters([
            'simple_config_list'
        ]),
    },

    methods: {
        async removeVideo() {
            for (let id in this.event) {
                await this.$store.dispatch('deleteVideo', this.event[id])
                .then(({ data }) => {
                    this.$store.dispatch('addMessage', data.message);
                    let emit_action = data.message.type == 'success' ? 'refresh' : '';
                    this.$emit('done', emit_action);
                }).catch(() => {
                    this.$emit('cancel');
                });
            }
        },

        decline() {
            this.$emit('cancel');
        },
    },
}
</script>