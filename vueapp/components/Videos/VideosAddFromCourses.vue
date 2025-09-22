<template>
    <div>
        <StudipDialog
            :title="$gettext('Videos hinzufügen')"
            :confirmText="$gettext('Hinzufügen')"
            confirmClass="add"
            :confirmDisabled="selectedVideos.length === 0"
            :closeText="$gettext('Abbrechen')"
            closeClass="cancel"
            height="800"
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
                    </div>
                    <form class="default">
                        <label>
                            {{ $gettext('Wiedergabeliste') }}
                            <select v-model="targetPlaylistToken" required>
                                <option
                                    v-for="playlist in targetPlaylists"
                                    v-bind:key="playlist.token"
                                    :value="playlist.token"
                                >
                                    {{ playlist.title }}
                                    <template v-if="playlist.is_default">
                                        ({{ $gettext('Standard-Widergabeliste') }})
                                    </template>
                                </option>
                            </select>
                        </label>
                    </form>
                    <span>{{ $gettext('Videos') }}</span>
                    <VideosTable
                        :selectable="true"
                        :showActions="false"
                        :cid="selectedCourse.id"
                        :nolimit="true"
                        @selectedVideosChange="updateSelectedVideos"
                    />
                </div>
            </template>

            <template #dialogButtons>
                <button
                    v-if="selectedCourse"
                    class="button refresh"
                    @click.prevent="
                        selectedCourse = null;
                        selectedVideos = [];
                    "
                >
                    {{ $gettext('Andere Veranstaltung wählen') }}
                </button>
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
            targetPlaylistToken: null,
        };
    },

    computed: {
        ...mapGetters('opencast', ['cid', 'userCourses']),
        ...mapGetters('playlists', ['playlist', 'playlists']),

        targetPlaylists() {
            let targetPlaylists = [...this.playlists];

            return targetPlaylists;
        },
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
                    playlist: this.targetPlaylistToken,
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
        this.targetPlaylistToken = this.targetPlaylists.filter((playlist) => playlist.is_default)[0].token;
    },
};
</script>
