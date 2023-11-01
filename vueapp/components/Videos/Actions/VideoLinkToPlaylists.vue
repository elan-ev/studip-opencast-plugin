<template>
    <div>
        <StudipDialog
            :title="$gettext('Verknüpfungen')"
            :confirmText="$gettext('Speichern')"
            :confirmClass="'accept'"
            :closeText="$gettext('Abbrechen')"
            :closeClass="'cancel'"
            height="600"
            width="600"
            @close="decline"
            @confirm="addToCourse"
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
                                <router-link :to="{ name: 'playlist_edit' , params: { token: playlist.token }}" target="_blank">
                                    {{ playlist.title }}
                                </router-link>
                            </td>
                            <td>
                                <studip-icon shape="trash" role="clickable" @click="confirmDelete(index)" style="cursor: pointer"/>
                            </td>
                        </tr>
                    </tbody>
                </table>

                <UserPlaylistSelectable
                    @add="addPlaylistToList"
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
    name: 'VideoAddToSeminar',

    components: {
        StudipDialog, StudipIcon,
        UserPlaylistSelectable
    },

    props: ['event'],

    emits: ['done', 'cancel'],

    computed: {
    ...mapGetters(['playlists'])
    },

    methods: {
        addPlaylistToList(course) {
            this.event.playlists.push(course);
        },

        confirmDelete(playlist_index) {
            if (confirm(this.$gettext('Sind sie sicher, dass sie dieses Video aus der Wiedergabeliste entfernen möchten?'))) {
                this.event.playlists.splice(playlist_index, 1);
            }
        },

        async addToCourse() {
            let data = {
                token: this.event.token,
                playlists: this.event.playlists,
            };
            await this.$store.dispatch('addVideoToPlaylists', data)
            .then(({ data }) => {
                this.$store.dispatch('addMessage', data.message);
                this.$emit('done', 'refresh');
            }).catch(() => {
                this.$emit('cancel');
            });
        },

        decline() {
            this.$emit('cancel');
        }
    },

    mounted () {
        this.$store.dispatch('loadPlaylists');
    },
}
</script>