<template>
    <div>
        <StudipDialog
            :title="$gettext('Videos hinzufügen')"
            :confirmText="$gettext('Hinzufügen')"
            :disabled="selectedVideos.length === 0"
            :closeText="$gettext('Schließen')"
            :closeClass="'cancel'"
            height="600"
            width="800"
            @close="cancel"
            @confirm="addVideosToPlaylist"
        >
            <template v-slot:dialogContent>
                <VideosTable
                    :selectable="true"
                    :showActions="false"
                    @selectedVideosChange="updateSelectedVideos"
                />
            </template>
        </StudipDialog>
    </div>
</template>

<script>
import { mapGetters } from "vuex";

import StudipDialog from "@/components/Studip/StudipDialog.vue";
import VideosTable from "@/components/Videos/VideosTable";

export default {
    name: "VideosAddFromContents",

    components: {
        StudipDialog,
        VideosTable
    },

    emits: ['done', 'cancel'],

    data() {
        return {
            selectedVideos: [],
        }
    },

    computed: {
        ...mapGetters(['playlist']),
    },

    methods: {
        cancel() {
            this.$emit('cancel');
        },

        updateSelectedVideos(selectedVideos) {
            this.selectedVideos = selectedVideos;
        },

        addVideosToPlaylist() {
            this.$store.dispatch('addVideosToPlaylist', {
                playlist: this.playlist.token,
                videos:   this.selectedVideos
            }).then(() => {
                this.selectedVideos = [];
                this.$store.dispatch('addMessage', {
                    type: 'success',
                    text: this.$gettext('Die Videos wurden der Wiedergabeliste hinzugefügt.')
                });
                this.$store.commit('setVideosReload', true);
                this.$emit('done');
            });
        },
    },
};
</script>
