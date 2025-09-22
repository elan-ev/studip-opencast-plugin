<template>
    <div>
        <StudipDialog
            :title="$gettext('Videos hinzufügen')"
            :confirmText="$gettext('Hinzufügen')"
            confirmClass="add"
            :confirmDisabled="selectedVideos.length === 0"
            :closeText="$gettext('Abbrechen')"
            closeClass="cancel"
            height="600"
            width="800"
            @close="cancel"
            @confirm="addVideosToPlaylist"
        >
            <template v-slot:dialogContent>
                <UserCourseSelectable
                    v-if="!selectedCourse"
                    @add="selectCourse"
                    :title="$gettext('Veranstaltung auswählen')"
                    :courses="userCourses"
                />

                <div v-else>
                    <div class="oc--dialog-add-videos__header">
                        <h2>{{ selectedCourse.name }}</h2>
                        <button
                            class="button refresh"
                            @click.prevent="
                                selectedCourse = null;
                                selectedVideos = [];
                            "
                        >
                            {{ $gettext('Andere Veranstaltung wählen') }}
                        </button>
                    </div>
                    <VideosTable
                        :selectable="true"
                        :showActions="false"
                        :cid="selectedCourse.id"
                        :nolimit="true"
                        @selectedVideosChange="updateSelectedVideos"
                    />
                </div>
            </template>
        </StudipDialog>
    </div>
</template>

<script>
import { mapGetters } from 'vuex';

import StudipDialog from '@studip/StudipDialog';
import UserCourseSelectable from '@/components/UserCourseSelectable';
import VideosTable from '@/components/Videos/VideosTable';

export default {
    name: 'VideosAddFromContents',

    components: {
        StudipDialog,
        UserCourseSelectable,
        VideosTable,
    },

    emits: ['done', 'cancel'],

    data() {
        return {
            selectedCourse: null,
            selectedVideos: [],
        };
    },

    computed: {
        ...mapGetters('opencast', ['cid', 'userCourses']),
        ...mapGetters('playlists', ['playlist']),
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
            this.$store
                .dispatch('playlists/addVideosToPlaylist', {
                    playlist: this.playlist.token,
                    videos: this.selectedVideos,
                    course_id: this.cid,
                })
                .then(() => {
                    this.selectedVideos = [];
                    this.$store.dispatch('messages/addMessage', {
                        type: 'success',
                        text: this.$gettext(
                            'Die Videos wurden der Wiedergabeliste hinzugefügt. Videos, die bereits in der Wiedergabeliste enthalten sind, wurden nicht erneut hinzugefügt.'
                        ),
                    });
                    this.$store.commit('videos/setVideosReload', true);
                    this.$emit('done');
                })
                .catch(() => {
                    this.$store.dispatch('messages/addMessage', {
                        type: 'error',
                        text: this.$gettext('Die Videos konnten der Wiedergabeliste nicht hinzugefügt werden.'),
                    });
                    this.$emit('cancel');
                });
        },
    },

    mounted() {
        this.$store.dispatch('opencast/loadUserCourses');
    },
};
</script>
