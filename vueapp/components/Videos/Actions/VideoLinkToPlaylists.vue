<template>
    <div>
        <StudipDialog
            :title="$gettext('Verknüpfungen')"
            :closeText="$gettext('Schließen')"
            :closeClass="'cancel'"
            height="600"
            width="800"
            @close="this.$emit('done', 'refresh')"
        >
            <template v-slot:dialogContent>
                <VideoPlaylists
                    :event="event"
                    @removePlaylist="removePlaylist"
                />

                <UserPlaylistSelectable
                    @add="addPlaylist"
                    :playlists="userPlaylists"
                    :selectedPlaylists="this.event.playlists"
                />
            </template>
        </StudipDialog>
    </div>
</template>

<script>
import { mapGetters } from "vuex";
import StudipDialog from '@studip/StudipDialog'

import UserPlaylistSelectable from '@/components/UserPlaylistSelectable';
import VideoPlaylists from "@/components/Videos/VideoPlaylists";

export default {
    name: 'VideoLinkToPlaylists',

    components: {
        StudipDialog,
        UserPlaylistSelectable,
        VideoPlaylists
    },

    props: ['event'],

    emits: ['done', 'cancel'],

    data() {
        return {
            add_playlist_error: {
                type: 'error',
                message: this.$gettext('Beim Hinzufügen der Verknüpfung ist ein Fehler aufgetreten.'),
                dialog: true
            },

            remove_playlist_error: {
                type: 'error',
                message: this.$gettext('Beim Entfernen der Verknüpfung ist ein Fehler aufgetreten.'),
                dialog: true
            },
        }
    },

    computed: {
        ...mapGetters(['userPlaylists', 'cid']),
    },

    methods: {
        addPlaylist(playlist) {
            this.event.playlists.push(playlist);

            this.$store.dispatch('addVideosToPlaylist', {
                playlist: playlist.token,
                videos: [this.event.token],
            })
            .catch(() => {
                // find the index of the playlist that was just added and remove it
                let index = this.event.playlists.findIndex(p => p.token == playlist.token);
                this.event.playlists.splice(index, 1);
                this.$store.dispatch('addMessage', this.add_playlist_error);
            });
        },

        removePlaylist(index) {
            if (!confirm(this.$gettext('Sind Sie sicher, dass Sie dieses Video aus der Wiedergabeliste entfernen möchten?'))) {
                return;
            }

            let playlist = this.event.playlists.splice(index, 1)[0];

            this.$store.dispatch('removeVideosFromPlaylist', {
                playlist:  playlist.token,
                videos:    [this.event.token],
                course_id: this.cid
            })
            .catch(() => {
                // add the playlist back to the list
                this.event.playlists.splice(index, 0, playlist);
                this.$store.dispatch('addMessage', this.remove_playlist_error);
            });
        },
    },

    mounted () {
        this.$store.dispatch('loadUserPlaylists', {
            limit: -1,
        });
    },
}
</script>