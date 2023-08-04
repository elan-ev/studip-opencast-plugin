<template>
    <div>
        <StudipDialog
            :title="$gettext('Alle Inhalte in weitere Kurse übertragen')"
            :confirmText="$gettext('Übertragen')"
            :confirmClass="'accept'"
            :closeText="$gettext('Abbrechen')"
            :closeClass="'cancel'"
            height="600"
            width="600"
            @close="decline"
            @confirm="copyVideosToCourses"
        >
            <template v-slot:dialogContent>
                <table class="default">
                    <thead>
                        <tr>
                            <th>
                                {{ $gettext('Veranstaltung') }}
                            </th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody v-if="courses.length > 0">
                        <tr v-for="(course, index) in courses" v-bind:key="course.id">
                            <td>
                                <a :href="getCourseLink(course)" target="_blank">
                                    {{ course.name }}
                                </a>
                            </td>
                            <td>
                                <studip-icon shape="trash" role="clickable" @click="confirmDelete(index)" style="cursor: pointer"/>
                            </td>
                        </tr>
                    </tbody>
                    <tbody v-else>
                        <tr>
                            <td colspan="2">
                                {{ $gettext('Kein Kurs gewählt.') }}
                            </td>
                        </tr>
                    </tbody>
                </table>
                <UserCourseSelectable
                    @add="addCourseToList"
                    :courses="user_courses_filtered"
                    :selectedCourses="[]"
                />
            </template>
        </StudipDialog>
    </div>
</template>

<script>
import { mapGetters } from "vuex";
import StudipDialog from '@studip/StudipDialog'
import StudipIcon from '@studip/StudipIcon';

import UserCourseSelectable from '@/components/UserCourseSelectable';

export default {
    name: 'VideoCopyToSeminar',

    components: {
        StudipDialog, StudipIcon,
        UserCourseSelectable
    },

    data() {
        return {
            courses: []
        }
    },

    computed: {
        ...mapGetters(['userCourses', 'cid', 'courseVideosToCopy']),

        user_courses_filtered() {
            let userCoursesFiltered = {};
            for (let semester_code in this.userCourses) {
                userCoursesFiltered[semester_code] = {};
                for (let semester in this.userCourses[semester_code]) {
                    if (Array.isArray(this.userCourses[semester_code][semester])) {
                        let filtered = this.userCourses[semester_code][semester].filter(
                            course => course.id != this.cid && !this.courses.includes(course));
                        userCoursesFiltered[semester_code][semester] = filtered;
                    }
                }
            }
            return userCoursesFiltered;
        }
    },

    methods: {

        getCourseLink(course) {
            return window.STUDIP.URLHelper.getURL('plugins.php/opencast/course?cid=' + course.id)
        },

        addCourseToList(course) {
            this.courses.push(course);
        },

        confirmDelete(course_index) {
            if (confirm(this.$gettext('Sind sie sicher, dass Sie diesen Kurs entfernen möchten?'))) {
                this.courses.splice(course_index, 1);
            }
        },

        async copyVideosToCourses() {
            this.$store.dispatch('clearMessages');
            let data = {
                cid: this.cid,
                courses: this.courses,
                tokens: this.courseVideosToCopy
            };
            await this.$store.dispatch('copyVideosToCourses', data)
            .then(({ data }) => {
                this.$store.dispatch('addMessage', data.message);
                if (data?.message?.type == 'success') {
                    this.$emit('done');
                } else {
                    this.$emit('cancel');
                }
            }).catch(() => {
                this.$emit('cancel');
            });
        },

        decline() {
            this.$emit('cancel');
        }
    },

    mounted () {
        this.$store.dispatch('loadUserCourses');
    },
}
</script>