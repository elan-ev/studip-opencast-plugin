<template>
    <div>
        <StudipDialog
            :title="title"
            :closeText="$gettext('Schließen')"
            :closeClass="'cancel'"
            height="500"
            width="600"
            @close="this.$emit('done', 'refresh')"
        >
            <template v-slot:dialogContent>
                <table class="default" v-if="selectedCourses.length > 0">
                    <thead>
                        <tr>
                            <th>
                                {{ $gettext('Verknüpfte Veranstaltungen') }}
                            </th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="(course, index) in playlistCourses" v-bind:key="course.id">
                            <td>
                                <a :href="getCourseLink(course)" target="_blank">
                                    {{ course.name }}
                                </a>
                            </td>
                            <td>
                                <studip-icon shape="trash" role="clickable" @click="removeCourse(index)" style="cursor: pointer"/>
                            </td>
                        </tr>
                    </tbody>
                </table>

                <UserCourseSelectable @add="addCourse"
                    :title="$gettext('Zu Kurs hinzufügen')"
                    :courses="userCourses"
                    :selectedCourses="selectedCourses"
                />
            </template>
        </StudipDialog>
    </div>
</template>

<script>
import { mapGetters } from "vuex";

import StudipDialog from '@studip/StudipDialog'
import StudipIcon from '@studip/StudipIcon'

import UserCourseSelectable from '@/components/UserCourseSelectable';

export default {
    name: 'PlaylistAddToCourseDialog',

    components: {
        StudipDialog, StudipIcon,
        UserCourseSelectable
    },

    props: ['title', 'playlist'],

    data() {
        return {
            selectedCourses: [],
            add_course_error: {
                type: 'error',
                text: this.$gettext('Beim Hinzufügen des Kurses ist ein Fehler aufgetreten.'),
                dialog: true
            },
            remove_course_error: {
                type: 'error',
                text: this.$gettext('Beim Entfernen des Kurses ist ein Fehler aufgetreten.'),
                dialog: true
            }
        }
    },

    computed: {
        ...mapGetters(['playlistCourses', 'userCourses'])
    },

    methods: {
        getCourseLink(course) {
            return window.STUDIP.URLHelper.getURL('plugins.php/opencast/course?cid=' + course.id + '#/course/videos')
        },

        addCourse(course) {
            this.selectedCourses.push(course);

            this.$store.dispatch('updatePlaylistCourses', {
                token: this.playlist.token,
                courses: this.selectedCourses
            })
            .catch(() => {
                // find the index of the course that was just added and remove it
                let index = this.selectedCourses.findIndex((c) => c.id === course.id);
                this.selectedCourses.splice(index, 1);
                this.$store.dispatch('addMessage', add_course_error);
            });
        },

        removeCourse(index) {
            if (!confirm(this.$gettext('Sind sie sicher, dass sie diese Playlist aus dem Kurs entfernen möchten?'))) {
                return;
            }

            let course = this.selectedCourses.splice(index, 1)[0];

            this.$store.dispatch('updatePlaylistCourses', {
                token: this.playlist.token,
                courses: this.selectedCourses
            })
            .catch(() => {
                // find the index of the course that was just added and remove it
                let index = this.selectedCourses.findIndex((c) => c.id === course.id);
                this.selectedCourses.splice(index, 1);
                this.$store.dispatch('addMessage', remove_course_error);
            });
        }
    },

    mounted() {
        let view = this;

        this.$store.commit('setPlaylistCourses', null);
        this.$store.dispatch('loadPlaylistCourses', this.playlist.token)
            .then(() => {
                view.selectedCourses = this.playlistCourses;
            });

        this.$store.dispatch('loadUserCourses');
    },
}
</script>
