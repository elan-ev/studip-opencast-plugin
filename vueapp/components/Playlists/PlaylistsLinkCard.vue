<template>
    <div>
        <StudipDialog
            :title="$gettext('Playlisten verknüpfen')"
            :confirmText="$gettext('Hinzufügen')"
            :disabled="selectedPlaylists.length === 0"
            :closeText="$gettext('Schließen')"
            :closeClass="'cancel'"
            height="600"
            width="800"
            @close="cancel"
            @confirm="addPlaylistsToCourse"
        >
            <template v-slot:dialogContent>
                <PlaylistsTable
                    :selectable="true"
                    :showActions="false"
                    @selectedPlaylistsChange="updateSelectedPlaylists"
                />
            </template>
        </StudipDialog>
    </div>
</template>

<script>
import { mapGetters } from "vuex";

import StudipDialog from "@/components/Studip/StudipDialog.vue";
import PlaylistsTable from "@/components/Playlists/PlaylistsTable.vue";
import ApiService from "@/common/api.service";

export default {
    name: "PlaylistsLinkCard",

    components: {
        StudipDialog,
        PlaylistsTable,
    },

    emits: ['done', 'cancel'],

    data() {
        return {
            playlists: [],
            selectedPlaylists: [],
        }
    },

    computed: {
        ...mapGetters(['cid']),
    },

    methods: {
        cancel() {
            this.$emit('cancel');
        },

        updateSelectedPlaylists(selectedPlaylists) {
            this.selectedPlaylists = selectedPlaylists;
        },

        addPlaylistsToCourse() {
            this.$store.dispatch('addPlaylistsToCourse', {
                course: this.cid,
                playlists: this.selectedPlaylists
            }).then(() => {
                this.selectedPlaylists = [];
                this.$store.dispatch('addMessage', {
                    type: 'success',
                    text: this.$gettext('Die Playlisten wurden der Veranstaltung hinzugefügt.')
                });
                this.$store.dispatch('loadPlaylists');
                this.$store.dispatch('setPlaylistsReload', true);
                this.$emit('done');
            });
        },
    },
};
</script>
