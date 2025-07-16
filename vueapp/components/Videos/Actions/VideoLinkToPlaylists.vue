<template>
    <div>
        <VideoPlaylists :event="event" @removePlaylist="removePlaylist" />

        <UserPlaylistSelectable
            @add="addPlaylist"
            :playlists="userPlaylists"
            :selectedPlaylists="this.event.playlists"
        />
    </div>
</template>

<script>
import { mapGetters } from 'vuex';

import UserPlaylistSelectable from '@/components/UserPlaylistSelectable';
import VideoPlaylists from '@/components/Videos/VideoPlaylists';

export default {
    name: 'VideoLinkToPlaylists',

    components: {
        UserPlaylistSelectable,
        VideoPlaylists,
    },

    props: ['event'],

    emits: ['done', 'cancel'],

    data() {
        return {
            add_playlist_error: {
                type: 'error',
                message: this.$gettext('Beim Hinzufügen der Verknüpfung ist ein Fehler aufgetreten.'),
                dialog: true,
            },

            remove_playlist_error: {
                type: 'error',
                message: this.$gettext('Beim Entfernen der Verknüpfung ist ein Fehler aufgetreten.'),
                dialog: true,
            },
        };
    },

    computed: {
        ...mapGetters('opencast', ['cid']),
        ...mapGetters('playlists', ['userPlaylists']),
    },

    methods: {
        addPlaylist(playlist) {
            this.event.playlists.push(playlist);

            this.$store
                .dispatch('playlists/addVideosToPlaylist', {
                    playlist: playlist.token,
                    videos: [this.event.token],
                })
                .catch(() => {
                    // find the index of the playlist that was just added and remove it
                    let index = this.event.playlists.findIndex((p) => p.token == playlist.token);
                    this.event.playlists.splice(index, 1);
                    this.$store.dispatch('messages/addMessage', this.add_playlist_error);
                });
        },

        removePlaylist(index) {
            if (
                !confirm(
                    this.$gettext('Sind Sie sicher, dass Sie dieses Video aus der Wiedergabeliste entfernen möchten?')
                )
            ) {
                return;
            }

            let playlist = this.event.playlists.splice(index, 1)[0];

            this.$store
                .dispatch('playlists/removeVideosFromPlaylist', {
                    playlist: playlist.token,
                    videos: [this.event.token],
                    course_id: this.cid,
                })
                .catch(() => {
                    // add the playlist back to the list
                    this.event.playlists.splice(index, 0, playlist);
                    this.$store.dispatch('messages/addMessage', this.remove_playlist_error);
                });
        },
    },

    mounted() {
        this.$store.dispatch('playlists/loadUserPlaylists', {
            limit: -1,
        });
    },
};
</script>
