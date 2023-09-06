<template>
    <div>
        <StudipDialog
            :title="title"
            :confirmText="$gettext('Speichern')"
            :confirmClass="'accept'"
            :closeText="$gettext('Schließen')"
            :closeClass="'cancel'"
            height="500"
            @close="$emit('cancel')"
            @confirm="addToCourse"
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
                                <studip-icon shape="trash" role="clickable" @click="removeFromCourse(index)" style="cursor: pointer"/>
                            </td>
                        </tr>
                    </tbody>
                </table>

                <UserCourseSelectable @add="addCourseToList"
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
            selectedCourses: []
        }
    },

    computed: {
        ...mapGetters(['playlistCourses', 'userCourses'])
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

    methods: {
        getCourseLink(course) {
            return window.STUDIP.URLHelper.getURL('plugins.php/opencast/course?cid=' + course.id + '#/course/videos')
        },

        addCourseToList(course) {
            this.selectedCourses.push(course);
        },

        addToCourse() {
            this.$store.dispatch('addPlaylistToCourses', {
                token: this.playlist.token,
                courses: this.selectedCourses
            })
            .then(({ data }) => {
                this.$store.dispatch('addMessage', data.message);
                this.$emit('done', 'refresh');
            }).catch(() => {
                this.$emit('cancel');
            });
        },

        removeFromCourse(course_index) {
            if (confirm(this.$gettext('Sind sie sicher, dass sie diese Playlist aus dem Kurs entfernen möchten?'))) {
                this.selectedCourses.splice(course_index, 1);
            }
        }
    }
}
</script>
