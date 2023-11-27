<template>
    <div>
        <StudipDialog
            :title="$gettext('Video zur Wiedergabeliste hinzufügen')"
            :confirmText="$gettext('Akzeptieren')"
            :confirmClass="'accept'"
            :closeText="$gettext('Abbrechen')"
            :closeClass="'cancel'"
            height="200"
            @close="decline"
            @confirm="addVideo"
        >
            <template v-slot:dialogContent>
                {{ $gettext('Möchten Sie das Video wirklich zu der Wiedergabeliste hinzufügen?') }}
            </template>
        </StudipDialog>
    </div>
</template>

<script>
import { mapGetters } from "vuex";
import StudipDialog from '@studip/StudipDialog'

export default {
    name: 'VideoAddToPlaylist',

    components: {
        StudipDialog
    },

    props: ['event'],

    emits: ['done', 'cancel'],

    computed: {
        ...mapGetters(['playlist'])
    },

    methods: {
        async addVideo() {
            await this.$store.dispatch('addVideosToPlaylist', {
                playlist: this.playlist.token,
                videos: [this.event.token]
            }).then(() => {
                this.$store.dispatch('addMessage', {
                    type: 'success',
                    text: this.$gettext('Das Video wurde zu der Wiedergabeliste hinzugefügt.')
                });
                this.$emit('done', 'refresh');
            }).catch(() => {
                this.$emit('cancel');
            });
        },

        decline() {
            this.$emit('cancel');
        }
    },
}
</script>