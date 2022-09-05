<template>
    <div>
        <StudipDialog
            :title="title"
            :closeText="$gettext('Schließen')"
            :closeClass="'cancel'"
            height="500"
            @close="$emit('cancel')"
        >
            <template v-slot:dialogContent>
                <table class="default" v-if="playlistCourses">
                    <thead>
                        <tr>
                            <th>
                                {{ $gettext('Veranstaltung') }}
                            </th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="course in playlistCourses" v-bind:key="course.id">
                            <td>
                                <a :href="getCourseLink(course)" target="_blank">
                                    {{ course.name }}
                                </a>
                            </td>
                            <td>
                                <studip-icon shape="trash" role="clickable" @click="removeFromCourse(course.id)" style="cursor: pointer"/>
                            </td>
                        </tr>
                    </tbody>
                </table>

                <form class="default">
                    <fieldset>
                        <legend>Zu neuem Kurs hinzufügen</legend>

                        <label>
                            <select v-model="currentCourse">
                                <template v-for="course_sem in myCourses">
                                <optgroup v-for="(courses, semester) in course_sem" v-bind:key="semester" :label="semester">
                                    <option v-for="course in courses" :value="course.id" v-bind:key="course.id">
                                        {{ course.name }}
                                    </option>
                                </optgroup>
                                </template>
                            </select>
                        </label>
                    </fieldset>
                    <footer>
                         <StudipButton icon="accept" @click.prevent="addToCourse">
                            <span v-translate>Playlist zu Kurs hinzufügen</span>
                        </StudipButton>
                    </footer>
                </form>
            </template>
        </StudipDialog>
    </div>
</template>

<script>
import { mapGetters } from "vuex";

import StudipDialog from '@studip/StudipDialog'
import StudipIcon from '@studip/StudipIcon'
import StudipButton from '@studip/StudipButton'

export default {
    name: 'PlaylistAddToCourseDialog',

    components: {
        StudipDialog,       StudipIcon,
        StudipButton
    },

    props: ['title', 'playlist'],

    data() {
        return {
            currentCourse: null
        }
    },

    computed: {
        ...mapGetters(['playlistCourses', 'myCourses'])
    },

    mounted() {
        this.$store.commit('setPlaylistCourses', null);
        this.$store.dispatch('loadPlaylistCourses', this.playlist.token);
        this.$store.dispatch('loadMyCourses');
    },

    methods: {
        getCourseLink(course) {
            window.STUDIP.URLHelper.getURL('/dispatch.php/course/details/index/' + course.id)
        },

        addToCourse() {
            this.$store.dispatch('addPlaylistToCourse', {
                token: this.playlist.token,
                course: this.currentCourse
            }).then(({ data }) => {
                this.$store.dispatch('loadPlaylistCourses', this.playlist.token);
            });
        },

        removeFromCourse(id) {
            if (confirm(this.$gettext('Sind sie sicher, dass sie diese Playlist aus dem gewählten Kurs entfernen möchten?'))) {
                this.$store.dispatch('removePlaylistFromCourse', {
                    token: this.playlist.token,
                    course: id
                }).then(({ data }) => {
                    this.$store.dispatch('loadPlaylistCourses', this.playlist.token);
                });
            }
        }
    }
}
</script>
