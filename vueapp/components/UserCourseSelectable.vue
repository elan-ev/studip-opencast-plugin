<template>
    <form class="default">
        <fieldset>
            <legend>
                {{ $gettext('Zu Kurs hinzufügen') }}
                <span class="tooltip tooltip-important" :data-tooltip="$gettext('Es werden nur Kurse aufgeführt, in denen das Opencast-Plugin aktiviert ist!')"
                    title="" tabindex="0"
                ></span>
            </legend>

            <label>
                <input type="text" :placeholder="$gettext('In Veranstaltungen suchen')" v-model="search">
                <select v-model="currentCourse" v-if="filteredUserCourses">
                    <template v-for="course_sem in filteredUserCourses">
                    <optgroup v-for="(courses, semester) in course_sem" v-bind:key="semester" :label="semester">
                        <option v-for="course in courses"
                            :value="course.id" v-bind:key="course.id"
                        >
                            {{ course.name }}
                        </option>
                    </optgroup>
                    </template>
                </select>
            </label>
        </fieldset>
        <footer>
            <StudipButton
                :disabled="currentCourse == null"
                icon="accept"
                @click.prevent="returnSelectedCourse()"
            >
                {{ $gettext('Kurse auswählen') }}
            </StudipButton>
        </footer>
    </form>
</template>

<script>
import StudipButton from "@studip/StudipButton";

export default {
    name: 'UserCourseSelectable',

    components: {
        StudipButton
    },

    props: {
        courses: {
            type: Object,
            required: true
        },

        selectedCourses: {
            type: Array
        }
    },

    data() {
        return {
            currentCourse: null,
            search: null
        }
    },

    computed: {

        filteredUserCourses() {
            let noCoursesFound = {};
            noCoursesFound['0'] = {}
            noCoursesFound['0'][this.$gettext('Keine Treffer')] = [{
                id: 0,
                name: this.$gettext('Keine Kurse gefunden.')
            }];

            if (this.courses.length == 0) {
                this.currentCourse = 0;
                return noCoursesFound;
            }

            /*
            if (!this.search) {
                this.currentCourse = Object.values(Object.values(this.courses)[0])[0][0].id;
                return this.courses;
            }
            */

            let course_list = {};
            let search      = this.search ? this.search.toLowerCase() : null;

            for (let id in this.courses) {
                let courses = this.courses[id];
                let sem     = Object.keys(courses)[0];
                courses = courses[sem];

                courses = courses.filter((course) => {
                    return (
                        (!this.search || course['name'].toLowerCase().indexOf(search) >= 0)
                        &&
                        (!this.selectedCoursesList || !this.selectedCoursesList[course['id']])
                    );
                });

                if (courses.length > 0) {
                    course_list[id] = {}
                    course_list[id][sem] = courses
                }
            }

            if (Object.keys(course_list).length == 0) {
                this.currentCourse = 0;
                return noCoursesFound;
            }

            this.currentCourse = Object.values(Object.values(course_list)[0])[0][0].id;

            return course_list;
        },

        selectedCoursesList() {
            let courses = {};

            for (let i = 0; i < this.selectedCourses.length; i++) {
                courses[this.selectedCourses[i].id] = true;
            }

            return courses;
        }
    },

    methods: {
        returnSelectedCourse() {
            let course = () => {
                let course;

                for (let id1 in this.courses) {
                    for (let id2 in this.courses[id1]) {
                        for (let i = 0; i < this.courses[id1][id2].length; i++) {
                            course = this.courses[id1][id2][i];
                            if (course.id == this.currentCourse) {
                               return course;
                            }
                        }
                    }
                }
            }

            this.$emit('add', course());
        }
    }
}
</script>
