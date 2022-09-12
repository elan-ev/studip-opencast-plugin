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
                <form class="default" @submit.prevent="addToCourse">
                    <fieldset>
                        <div v-if="event.courses.length > 0">
                            <h5>
                                <translate>Ausgewählte Kurse</translate>
                            </h5>
                            <StudipButton v-for="(course, index) in event.courses" :key="index"
                                icon="cancel"
                                :title="$gettext('Aus diesem Kurs entfernen')"
                                @click.prevent="confirmDelete(index)"
                                >
                                <span>{{ course.name }}</span> - <span>{{ course.semester_name }}</span>
                            </StudipButton>
                        </div>
                        <label>
                            <translate>Neuen Kurs auswählen</translate>
                            <select v-model="selectedCourse">
                                <option value="" disabled selected>
                                    <span v-translate>Bitte wählen Sie einen Kurs.</span>
                                </option>
                                <template v-for="(semester, index) in user_course_options" :key="index">
                                    <optgroup style="font-weight:bold;" :label="semester.name">
                                        <option v-for="(course, cindex) in semester.courses" :key="cindex" :value="course.id" :disabled="course.selected">
                                            {{ course.name }}
                                        </option>
                                    </optgroup>
                                </template>
                            </select>
                            <StudipButton :disabled="selectedCourse == ''"
                                icon="accept"
                                :title="$gettext('Hinzufügen')"
                                @click.prevent="addCourseToList"
                                >
                                <translate>Hinzufügen</translate>
                            </StudipButton>
                        </label>
                    </fieldset>
                </form>
            </template>
        </StudipDialog>
    </div>
</template>

<script>
import { mapGetters } from "vuex";
import StudipDialog from '@studip/StudipDialog'
import StudipSelect from '@studip/StudipSelect';
import StudipIcon from '@studip/StudipIcon';
import StudipButton from "@studip/StudipButton";

export default {
    name: 'VideoAddToSeminar',

    components: {
        StudipDialog, StudipSelect,
        StudipButton, StudipIcon
    },

    props: ['event'],

    emits: ['done', 'cancel'],

    data() {
        return {
            selectedCourse: '',
        }
    },

    computed: {
        ...mapGetters(['userCourses']),

        user_course_options() {
            let optgourp = [];

            for (const semester_date in this.userCourses) {
                let sem_date_obj = this.userCourses[semester_date];
                for (const semester in sem_date_obj) {
                    let options = [];
                    let semester_obj = sem_date_obj[semester];
                    for (const course in semester_obj) {
                        let course_obj = semester_obj[course];
                        let selected = false;
                        if (this.event.courses.find(c => c.id == course_obj.id)) {
                            selected = true;
                        }
                        course_obj.selected = selected;
                        course_obj.semester_name = semester;
                        options.push(course_obj);
                    }
                    optgourp.push({
                        name: semester,
                        courses: options
                    });
                }
            }
            return optgourp;
        }
    },

    methods: {

        addCourseToList() {
            if (this.selectedCourse != '') {
                for (const sem of this.user_course_options) {
                    for (const course of sem.courses) {
                        if (course.id == this.selectedCourse) {
                            this.event.courses.push(course);
                            this.selectedCourse = '';
                            break;
                        }
                    }
                }
            }
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
        
    },
}
</script>