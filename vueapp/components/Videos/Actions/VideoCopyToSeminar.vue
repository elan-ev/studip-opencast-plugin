<template>
    <div>
        <StudipDialog
            :title="$gettext('Alle Inhalte mit weiterem Kurs verknüpfen')"
            :confirmText="$gettext('Verknüpfen')"
            :confirmClass="'accept'"
            :closeText="$gettext('Abbrechen')"
            :closeClass="'cancel'"
            height="600"
            width="600"
            @close="decline"
            @confirm="copyVideosToCourses"
        >
            <template v-slot:dialogContent>
                <div v-if="courseCopyType !== 'selectedVideos'" style="margin-bottom: 1em;">
                    <div @click="setCopyType('all')" style="cursor: pointer">
                        <input type="radio" value="all"
                            name="type"
                            :checked="courseCopyType === 'all'"
                        >
                        {{ $gettext('Alle Inhalte') }}
                    </div>

                    <div @click="setCopyType('videos')" style="cursor: pointer">
                        <input type="radio" value="videos"
                            name="type"
                            :checked="courseCopyType === 'videos'"
                        >
                        {{ $gettext('Nur alle Videos') }}
                    </div>

                    <div @click="setCopyType('playlists')" style="cursor: pointer">
                        <input type="radio" value="playlists"
                            name="type"
                            :checked="courseCopyType === 'playlists'"
                        >
                        {{ $gettext('Nur alle Wiedergabelisten') }}
                    </div>
                </div>
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
        ...mapGetters(['userCourses', 'cid', 'courseVideosToCopy', 'courseCopyType']),

        user_courses_filtered() {
            let userCoursesFiltered = this.userCourses;
            for (let semester_code in userCoursesFiltered) {
                for (let semester in userCoursesFiltered[semester_code]) {
                    if (Array.isArray(userCoursesFiltered[semester_code][semester])) {
                        let filtered = userCoursesFiltered[semester_code][semester].filter(course => course.id != this.cid);
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
                type: this.courseCopyType,
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

        setCopyType(type) {
            if (['all', 'videos', 'playlists'].includes(type)) {
                this.$store.dispatch('setCourseCopyType', type);
            }
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