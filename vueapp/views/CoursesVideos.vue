<template>
    <div>
        <MessageBox type="info" v-if="!hasDefaultPlaylist">
            {{ $gettext('Für diesen Kurs gibt es keine Standard-Kurswiedergabeliste. Versuchen Sie bitte, im Aktionsmenü eine zu erstellen.') }}
        </MessageBox>
        <template v-else>
            <VideosTable
                v-if="playlist"
                :playlist="playlist"
                :cid="cid"
                :editable="canEdit"
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

        hasDefaultPlaylist() {
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
