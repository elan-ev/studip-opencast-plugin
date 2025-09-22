<template>
    <form class="default">
        <fieldset>
            <legend>
                {{ title }}
                <span class="tooltip tooltip-important" :data-tooltip="$gettext('Es werden nur Veranstaltungen aufgeführt, in denen das Opencast-Plugin aktiviert ist')"
                    title="" tabindex="0"
                ></span>
            </legend>

            <label>
                <studip-select :options="filteredUserCourses" v-model="currentCourse"
                    label="name"
                    track-by="id"
                    :selectable="option => !option.header"
                    :filterable="false"
                    @search="updateSearch"
                    :placeholder="$gettext('Bitte eine Veranstaltung auswählen')"
                >
                    <template #list-header>
                        <li style="text-align: center">
                            <b>{{ $gettext('Veranstaltungen') }}</b>
                        </li>
                    </template>
                    <template #no-options="{ search, searching, loading }">
                        {{ $gettext('Keine Veranstaltung gefunden')}}
                    </template>
                    <template #selected-option="option">
                        <span class="vs__option">
                            {{ option.name }}
                        </span>
                    </template>
                    <template #option="{ name, header }">
                        <span v-if="header" class="vs__option">
                            {{ name }}
                        </span>
                        <span v-else class="vs__option">
                            {{ name }}
                        </span>
                    </template>
                </studip-select >
            </label>
        </fieldset>
    </form>
</template>

<script>
import StudipSelect from '@studip/StudipSelect';

export default {
    name: 'UserCourseSelectable',

    components: {
        StudipSelect,
    },

    props: {
        title: {
            type: String,
            required: true,
        },
        courses: {
            type: Object,
            required: true
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
            let course_list = [];

            for (let id in this.courses) {
                let courses    = this.courses[id];
                let sem        = Object.keys(courses)[0];
                let sem_search = this.search && sem.toLowerCase().indexOf(this.search.toLowerCase()) >= 0;

                courses = courses[sem];

                // Handle search
                // Only filter courses if semester does not match search
                courses = courses.filter((course) => {
                    return sem_search || !this.search || course['name'].toLowerCase().indexOf(this.search.toLowerCase()) >= 0;
                });

                if (courses.length > 0) {
                    // Add semester as header
                    course_list.push({
                        name: sem,
                        header: true
                    })
                    // Add courses as no header
                    courses.forEach(course => {
                        course_list.push({
                            name: course.name,
                            id: course.id,
                            header: false
                        })
                    });
                }
            }

            return course_list;
        }
    },

    methods: {
        updateSearch(search, loading) {
            this.search = search;
        }
    },

    watch: {
        // Didn't find a way to use something like onChange with studip-select, so we have to watch the currentCourse
        currentCourse(selectedCourse) {
            if (selectedCourse) {
                let course = () => {
                    let course;

                    for (let id1 in this.courses) {
                        for (let id2 in this.courses[id1]) {
                            for (let i = 0; i < this.courses[id1][id2].length; i++) {
                                course = this.courses[id1][id2][i];
                                if (course.id == selectedCourse.id) {
                                    return course;
                                }
                            }
                        }
                    }
                }

                this.currentCourse = null;
                this.$emit('add', course());
            }
        }
    }
}
</script>
