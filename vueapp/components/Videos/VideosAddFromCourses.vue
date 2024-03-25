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
                <UserCourseSelectable v-if="!selectedCourse"
                    @add="selectCourse"
                    :title="$gettext('Kurs auswählen')"
                    :courses="userCourses"
                />

                <div v-else>
                    <h2>{{ selectedCourse.name }}</h2>

                    <VideosTable
                        :selectable="true"
                        :showActions="false"
                        :cid="selectedCourse.id"
                        @selectedVideosChange="updateSelectedVideos"
                    />
                </div>
            </template>
        </StudipDialog>
    </div>
</template>

<script>
import { mapGetters } from "vuex";

import StudipDialog from "@studip/StudipDialog";
import UserCourseSelectable from '@/components/UserCourseSelectable';
import VideosTable from "@/components/Videos/VideosTable";

export default {
    name: "VideosAddFromContents",

    components: {
        StudipDialog,
        UserCourseSelectable,
        VideosTable
    },

    emits: ['done', 'cancel'],

    data() {
        return {
            selectedCourse: null,
            selectedVideos: [],
        }
    },

    computed: {
        ...mapGetters(['playlist', 'userCourses']),
    },

    methods: {
        cancel() {
            this.$emit('cancel');
        },

        selectCourse(course) {
            this.selectedCourse = course;
        },

        updateSelectedVideos(selectedVideos) {
            this.selectedVideos = selectedVideos;
        },

        addVideosToPlaylist() {
            this.$store.dispatch('addVideosToPlaylist', {
                playlist: this.playlist,
                videos:   this.selectedVideos
            }).then(() => {
                this.selectedVideos = [];
                this.$store.dispatch('addMessage', {
                    type: 'success',
                    text: this.$gettext('Die Videos wurden der Wiedergabeliste hinzugefügt.')
                });
                this.$store.commit('setVideosReload', true);
                this.$emit('done');
            }).catch(() => {
                this.$store.dispatch('addMessage', {
                    type: 'error',
                    text: this.$gettext('Die Videos konnten der Wiedergabeliste nicht hinzugefügt werden.')
                });
                this.$emit('cancel');
            });
        },
    },

    mounted() {
        this.$store.dispatch('loadUserCourses');
    }
};
</script>
