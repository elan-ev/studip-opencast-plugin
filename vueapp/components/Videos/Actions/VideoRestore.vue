<template>
    <div>
        <StudipDialog
            :title="$gettext('Aufzeichnung wiederherstellen')"
            :confirmText="$gettext('Akzeptieren')"
            :confirmClass="'accept'"
            :closeText="$gettext('Abbrechen')"
            :closeClass="'cancel'"
            :height="dialogHeight"
            width="500"
            @close="decline"
            @confirm="restoreVideo"
        >
            <template v-slot:dialogContent>
                <p>
                    {{ $gettext('Möchten Sie die Aufzeichnung wirklich wiederherstellen?') }}
                </p>

                <span v-if="event.playlists.length > 0">
                    <p>
                        {{ $gettext('Nach der Wiederherstellung erscheint die Aufzeichnung in den folgenden Wiedergabelisten.') }}
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
import StudipDialog from '@studip/StudipDialog'
import VideoPlaylists from "@/components/Videos/VideoPlaylists";

export default {
    name: 'VideoRestore',

    components: {
        StudipDialog,
        VideoPlaylists
    },

    props: ['event'],

    emits: ['done', 'cancel'],

    computed: {
        dialogHeight() {
            return this.event.playlists.length > 0 ? 350 : 200;
        },
    },

    methods: {
        async restoreVideo() {
            await this.$store.dispatch('videos/restoreVideo', this.event.token)
            .then(({ data }) => {
                this.$store.dispatch('messages/addMessage', data.message);
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