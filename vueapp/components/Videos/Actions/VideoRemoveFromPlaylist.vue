<template>
    <div>
        <StudipDialog
            :title="$gettext('Video aus Wiedergabeliste entfernen')"
            :confirmText="$gettext('Akzeptieren')"
            :confirmClass="'accept'"
            :closeText="$gettext('Abbrechen')"
            :closeClass="'cancel'"
            height="200"
            @close="decline"
            @confirm="removeVideo"
        >
            <template v-slot:dialogContent>
                {{ $gettext('Möchten Sie das Video wirklich aus der Wiedergabeliste entfernen?') }}
            </template>
        </StudipDialog>
    </div>
</template>

<script>
import { mapGetters } from "vuex";
import StudipDialog from '@studip/StudipDialog'

export default {
    name: 'VideoRemoveFromPlaylist',

    components: {
        StudipDialog
    },

    props: ['event'],

    emits: ['done', 'cancel'],

    computed: {
        ...mapGetters('opencast', ['cid']),
        ...mapGetters('playlists', ['playlist']),
    },

    methods: {
        async removeVideo() {
            await this.$store.dispatch('playlists/removeVideosFromPlaylist', {
                playlist:  this.playlist.token,
                videos:    [this.event.token],
                course_id: this.cid
            }).then(() => {
                this.$store.dispatch('messages/addMessage', {
                    type: 'success',
                    text: this.$gettext('Das Video wurde aus der Wiedergabeliste entfernt.')
                });
                this.$emit('done', 'refresh');
            }).catch(() => {
                this.$store.dispatch('messages/addMessage', {
                    type: 'error',
                    text: this.$gettext('Das Video konnte aus der Wiedergabeliste nicht entfernt werden.')
                });
                this.$emit('cancel');
            });
        },

        decline() {
            this.$emit('cancel');
        },
    },
}
</script>
