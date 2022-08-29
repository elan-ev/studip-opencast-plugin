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
                <table class="default">
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
                                <studip-icon shape="trash" role="clickable"/>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </template>
        </StudipDialog>
    </div>
</template>

<script>
import { mapGetters } from "vuex";

import StudipDialog from '@studip/StudipDialog'
import StudipIcon from '@studip/StudipIcon'

export default {
    name: 'PlaylistAddToCourseDialog',

    components: {
        StudipDialog,       StudipIcon
    },

    props: ['title', 'playlist'],

    computed: {
        ...mapGetters(['playlistCourses'])
    },

    mounted() {
        this.$store.commit('setPlaylistCourses', null);
        this.$store.dispatch('loadPlaylistCourses', this.playlist.token);
    },

    methods: {
        getCourseLink(course) {
            window.STUDIP.URLHelper.getURL('/dispatch.php/course/details/index/' + course.id)
        }
    }
}
</script>
