<template>
    <div>
        <StudipDialog
            :title="$gettext('Zu Kurs hinzufügen')"
            :confirmText="$gettext('Speichern')"
            :confirmClass="'accept'"
            :closeText="$gettext('Abbrechen')"
            :closeClass="'cancel'"
            height="600"
            width="600"
            @close="decline"
            @confirm="addToCourse"
        >
            <template v-slot:dialogContent>
                <table class="default" v-if="event.courses.length > 0">
                    <thead>
                        <tr>
                            <th>
                                {{ $gettext('Veranstaltung') }}
                            </th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="(course, index) in event.courses" v-bind:key="course.id">
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
                </table>

                <UserCourseSelectable
                    @add="addCourseToList"
                    :courses="userCourses"
                    :selectedCourses="this.event.courses"
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
    name: 'VideoAddToSeminar',

    components: {
        StudipDialog, StudipIcon,
        UserCourseSelectable
    },

    props: ['event'],

    emits: ['done', 'cancel'],

    computed: {
    ...mapGetters(['userCourses'])
    },

    methods: {

        getCourseLink(course) {
            return window.STUDIP.URLHelper.getURL('plugins.php/opencast/course?cid=' + course.id)
        },

        addCourseToList(course) {
            console.log('addCourseToList', course);
            this.event.courses.push(course);
        },

        confirmDelete(course_index) {
            if (confirm(this.$gettext('Sind sie sicher, dass sie dieses Video aus dem Kurs entfernen möchten?'))) {
                this.event.courses.splice(course_index, 1);
            }
        },

        async addToCourse() {
            let data = {
                token: this.event.token,
                courses: this.event.courses,
            };
            await this.$store.dispatch('addVideoToCourses', data)
            .then(({ data }) => {
                this.$store.dispatch('addMessage', data.message);
                this.$emit('done', 'refresh');
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