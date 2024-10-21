<template>
    <div>
        <StudipDialog
            :title="$gettext('Aufzeichnung zum Löschen markieren')"
            :confirmText="$gettext('Löschen')"
            :confirmClass="'accept'"
            :closeText="$gettext('Abbrechen')"
            :closeClass="'cancel'"
            :height="dialogHeight"
            width="600"
            @close="decline"
            @confirm="removeVideo"
        >
            <template v-slot:dialogContent>
                <p>
                    {{ $gettext('Möchten Sie die Aufzeichnung wirklich zum Löschen markieren?') }}
                </p>
                <p>
                    {{ $gettext('Die Aufzeichnung wird damit in den "Gelöschte Videos" Bereich '
                        + 'Ihres Arbeitsplatzes verschoben und wird nach %{ days } Tagen automatisch gelöscht. '
                        + 'Bis zu diesem Zeitpunkt können Sie die Aufzeichnung wiederherstellen.',
                        { days: simple_config_list.settings.OPENCAST_CLEAR_RECYCLE_BIN_INTERVAL })
                    }}
                </p>

                <span v-if="event.playlists.length > 0">
                    <p>
                        {{ $gettext('Beim Löschen wird die Aufzeichnung aus den folgenden Wiedergabelisten entfernt.') }}
                    </p>
                    <VideoPlaylists
                        :event="event"
                        :removable="false"
                    />
                </span>
            </template>
        </StudipDialog>
    </div>
</template>

<script>
import { mapGetters } from "vuex";

import StudipDialog from '@studip/StudipDialog'
import VideoPlaylists from "@/components/Videos/VideoPlaylists";

export default {
    name: 'VideoDelete',

    components: {
        StudipDialog,
        VideoPlaylists
    },

    props: ['event'],

    emits: ['done', 'cancel'],

    computed: {
        ...mapGetters([
            'simple_config_list'
        ]),

        dialogHeight() {
            return this.event.playlists.length > 0 ? 400 : 275;
        },
    },

    methods: {
        async removeVideo() {
            await this.$store.dispatch('deleteVideo', this.event.token)
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