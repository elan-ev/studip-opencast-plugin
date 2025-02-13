<template>
    <div>
        <MessageBox type="info" v-if="!hasDefaultPlaylist">
            {{ $gettext('Für diesen Kurs existiert noch keine Standard-Kurswiedergabeliste. Bitte erstellen Sie diese über das Aktionsmenü.') }}
        </MessageBox>
        <template v-else>
            <VideosTable
                v-if="playlist"
                :playlist="playlist"
                :cid="cid"
                :canEdit="canEdit"
                :canUpload="canUpload"
            />
        </template>
    </div>
</template>

<script>
import { mapGetters } from "vuex";
import VideosTable from "@/components/Videos/VideosTable";
import MessageBox from '@/components/MessageBox.vue';

export default {
    name: "CourseVideos",
    components: {
        VideosTable,
        MessageBox
    },

    computed: {
        ...mapGetters(['playlist', 'cid', 'defaultPlaylist', 'course_config']),

        canEdit() {
            return this.course_config?.edit_allowed ?? false;
        },

        canUpload() {
            return this.course_config?.upload_allowed ?? false;
        },

        hasDefaultPlaylist() {
            // if the course config is not available, assume it has a default playlist
            if (!this.course_config) {
                return true;
            }

            return this.course_config?.has_default_playlist;
        },
    },

    mounted() {
        this.$store.dispatch('loadPlaylists').then(() => {
            this.$store.dispatch('setPlaylist', this.defaultPlaylist);
        });
    },
};
</script>
