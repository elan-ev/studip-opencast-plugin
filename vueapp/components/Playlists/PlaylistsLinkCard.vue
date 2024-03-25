<template>
    <div>
        <StudipDialog
            :title="title"
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
                    :cid="cid ?? null"
                    :selectable="true"
                    :multi-select="!isDefault"
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

    props: {
        isDefault: {
            type: Boolean,
            default: false
        },
        customTitle: {
            type: String,
            default: ''
        }
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

        title() {
            if (this.customTitle) {
                return this.customTitle;
            }
            return this.isDefault ? this.$gettext('Kurswiedergabeliste verknüpfen') : this.$gettext('Playlisten verknüpfen');
        }
    },

    methods: {
        cancel() {
            this.$emit('cancel');
        },

        updateSelectedPlaylists(selectedPlaylists) {
            this.selectedPlaylists = selectedPlaylists;
        },

        addPlaylistsToCourse() {
            if (this.cid && this.isDefault && this.selectedPlaylists?.[0]) {
                let token = this.selectedPlaylists[0];
                this.$store.dispatch('addPlaylistToCourse', {
                        course: this.cid,
                        token: token,
                        is_default: true
                })
                .then(() => {
                    this.selectedPlaylists = [];
                    this.$store.dispatch('addMessage', {
                        type: 'success',
                        text: this.$gettext('Die Kurswiedergabeliste hinzugefügt.')
                    });
                    this.$store.dispatch('setPlaylistsReload', true);
                    this.$store.dispatch('loadPlaylists');
                    this.$store.dispatch('loadCourseConfig', this.cid);
                    this.$store.dispatch('loadPlaylistByToken', token);
                    this.$emit('done');
                });
            } else {
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
            }
        },
    },
};
</script>
