<template>
    <div>
        <StudipDialog v-if="showWarning"
            :title="dialogTitle"
            :confirmText="dialogConfirmText"
            :confirmClass="'accept'"
            :closeText="dialogCloseText"
            :closeClass="'cancel'"
            height="400"
            width="500"
            @close="decline"
            @confirm="accept"
        >
            <template v-slot:dialogContent>
                <p>
                    {{ dialogWarning }}
                </p>

                <VideoPlaylists
                    :event="event"
                    :removable="false"
                />
            </template>
        </StudipDialog>
    </div>
</template>

<script>
import StudipDialog from '@studip/StudipDialog'
import VideoPlaylists from "@/components/Videos/VideoPlaylists";
import { mapGetters } from "vuex";

export default {
    name: 'VideoChangeWarning',

    components: {
        StudipDialog,
        VideoPlaylists
    },

    props: {
        'event': Object,
        'title': String,
        'warning': String,
        'confirmText': String,
        'closeText': String,
        'showLinkedPlaylists': {
            type: Boolean,
            default: true
        }
    },

    emits: ['done', 'cancel'],

    computed: {
        ...mapGetters(['playlist']),

        dialogTitle() {
            if (this.title) {
                return this.title;
            }

            return this.$gettext('Auswirkungen der Videoänderungen');
        },

        dialogWarning() {
            if (this.warning) {
                return this.warning;
            }

            return this.$gettext('Wenn Sie dieses Video verändern, wirken sich diese Änderungen auf die folgenden ' +
                'Wiedergabelisten aus, zu denen das Video hinzugefügt wurde. Sollten Sie dies nicht beabsichtigen, ' +
                'laden Sie das Video erneut hoch und bearbeiten Sie diese Kopie.');
        },

        dialogConfirmText() {
            if (this.confirmText) {
                return this.confirmText;
            }

            return this.$gettext('Trotzdem bearbeiten');
        },

        dialogCloseText() {
            if (this.closeText) {
                return this.closeText;
            }

            return this.$gettext('Abbrechen');
        },

        showWarning() {
            // Video is in no playlists
            if (!Array.isArray(this.event.playlists) || this.event.playlists.length === 0) {
                return false;
            }

            // More than one playlist affected
            if (this.event.playlists.length > 1) {
                return true;
            }

            // User contents videos
            if (!this.playlist) {
                return true;
            }

            // Current playlist differs from linked playlist
            return this.event.playlists[0].token !== this.playlist.token;
        }
    },

    methods: {
        accept() {
            this.$emit('done');
        },

        decline() {
            this.$emit('cancel');
        },
    },

    created() {
        if (!this.showWarning) {
            this.$emit('done');
        }
    }
}
</script>
