<template>
    <div>
        <StudipDialog
            :title="$gettext('Verknüpfungen')"
            :closeText="$gettext('Schließen')"
            :closeClass="'cancel'"
            height="600"
            width="600"
            @close="this.$emit('done', 'refresh')"
        >
            <template v-slot:dialogContent>
                <table class="default" v-if="event.playlists.length > 0">
                    <thead>
                        <tr>
                            <th>
                                {{ $gettext('Wiedergabeliste') }}
                            </th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="(playlist, index) in event.playlists" v-bind:key="playlist.id">
                            <td>
                                <router-link :to="{ name: 'playlist' , params: { token: playlist.token }}" target="_blank">
                                    {{ playlist.title }}
                                </router-link>
                            </td>
                            <td>
                                <studip-icon shape="trash" role="clickable" @click="removePlaylist(index)" style="cursor: pointer"/>
                            </td>
                        </tr>
                    </tbody>
                </table>

                <UserPlaylistSelectable
                    @add="addPlaylist"
                    :playlists="playlists"
                    :selectedPlaylists="this.event.playlists"
                />

            </template>
        </StudipDialog>
    </div>
</template>

<script>
import { mapGetters } from "vuex";
import StudipDialog from '@studip/StudipDialog'
import StudipIcon from '@studip/StudipIcon';

import UserPlaylistSelectable from '@/components/UserPlaylistSelectable';

export default {
    name: 'VideoLinkToPlaylists',

    components: {
        StudipDialog, StudipIcon,
        UserPlaylistSelectable
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
    ...mapGetters(['playlists'])
    },

    methods: {
        addPlaylist(playlist) {
            this.event.playlists.push(playlist);

            this.$store.dispatch('updateVideoPlaylists', {
                token: this.event.token,
                playlists: this.event.playlists,
            })
            .catch(() => {
                // find the index of the playlist that was just added and remove it
                let index = this.event.playlists.findIndex(p => p.token == playlist.token);
                this.event.playlists.splice(index, 1);
                this.$store.dispatch('addMessage', add_playlist_error);
            });
        },

        removePlaylist(index) {
            if (!confirm(this.$gettext('Sind sie sicher, dass sie dieses Video aus der Wiedergabeliste entfernen möchten?'))) {
                return;
            }

            let link = this.event.playlists.splice(index, 1)[0];

            this.$store.dispatch('updateVideoPlaylists', {
                token: this.event.token,
                playlists: this.event.playlists,
            })
            .catch(() => {
                // add the playlist back to the list
                this.event.playlists.splice(index, 0, link);
                this.$store.dispatch('addMessage', remove_playlist_error);
            });
        },
    },

    mounted () {
        this.$store.dispatch('loadPlaylists');
    },
}
</script>