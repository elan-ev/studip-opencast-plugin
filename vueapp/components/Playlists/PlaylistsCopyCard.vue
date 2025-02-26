<template>
    <div>
        <StudipDialog
            :title="$gettext('Wiedergabelisten kopieren')"
            :confirmText="$gettext('Kopieren')"
            :disabled="selectedPlaylists.length === 0 || isCopying"
            :closeText="$gettext('Schließen')"
            :closeClass="'cancel'"
            height="600"
            width="1200"
            @close="cancel"
            @confirm="copyPlaylistsToCourse"
        >
            <template v-slot:dialogContent>
                <messageBox :type="'info'" v-show="isCopying">
                    {{ $gettext('Die ausgewählten Wiedergabelisten werden derzeit kopiert.') }}
                </messageBox>
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
import MessageBox from "@/components/MessageBox.vue";

export default {
    name: "PlaylistsCopyCard",

    components: {
        MessageBox,
        StudipDialog,
        PlaylistsTable,
    },

    emits: ['done', 'cancel'],

    props: {
        isDefault: {
            type: Boolean,
            default: false
        },
    },

    data() {
        return {
            playlists: [],
            selectedPlaylists: [],
            isCopying: false,
        }
    },

    computed: {
        ...mapGetters(['cid', 'defaultPlaylist']),
    },

    methods: {
        cancel() {
            this.$emit('cancel');
        },

        updateSelectedPlaylists(selectedPlaylists) {
            this.selectedPlaylists = selectedPlaylists;
        },

        copyPlaylistsToCourse() {
            const is_default = this.isDefault;
            this.isCopying = true;

            this.$store.dispatch('copyPlaylistsToCourse', {
                course: this.cid,
                playlists: this.selectedPlaylists,
                is_default: is_default,
            }).then(() => {
                this.selectedPlaylists = [];
                this.$store.dispatch('addMessage', {
                    type: 'success',
                    text: this.$gettext('Die Playlisten wurden in die Veranstaltung kopiert.')
                });

                if (is_default) {
                    // When is_default is true, it means it is the course playlist creation and we need to set a few things.
                    this.$store.dispatch('loadCourseConfig', this.cid);
                }

                this.$store.dispatch('loadPlaylists')
                    .then(() => {
                        if (is_default) {
                            // Set default playlist active
                            this.$store.dispatch('setPlaylist', this.defaultPlaylist);
                        }
                    });

                this.$store.dispatch('setPlaylistsReload', true);
                this.isCopying = false;
                this.$emit('done');
            });
        },
    },
};
</script>
