<template>
    <table class="default" v-if="event.playlists?.length > 0">
        <colgroup>
            <col>
            <col style="width: 25%">
            <col style="width: 25%">
            <col v-if="removable" style="width: 3%">
        </colgroup>
        <thead>
            <tr>
                <th>
                    {{ $gettext('Wiedergabeliste') }}
                </th>
                <th>
                    {{ $gettext('Veranstaltung') }}
                </th>
                <th>
                    {{ $gettext('Semester') }}
                </th>
                <th v-if="removable"></th>
            </tr>
        </thead>
        <tbody>
            <tr v-for="(playlist, index) in event.playlists" v-bind:key="playlist.token">
                <td>
                    {{ playlist.title }}
                </td>
                <td>
                    <a v-if="getCourseName(playlist)"
                        :href="getCourseUrl(playlist)"
                        target="_blank"
                        rel="noopener noreferrer"
                    >
                        {{ getCourseName(playlist) }}
                    </a>
                </td>
                <td>
                    {{ getSemester(playlist) }}
                </td>
                <td v-if="removable">
                    <studip-icon shape="trash" role="clickable" @click="removePlaylist(index)" style="cursor: pointer"/>
                </td>
            </tr>
        </tbody>
    </table>
</template>

<script>
import StudipIcon from '@studip/StudipIcon'

export default {
    name: 'VideoPlaylists',

    components: { StudipIcon },

    props: {
        event: Object,
        removable: {
            type: Boolean,
            default: true,
        },
    },

    emits: ['removePlaylist'],

    methods: {
        getCourseUrl(playlist) {
            return window.STUDIP.URLHelper.getURL(
                'plugins.php/opencastv3/course?cid=' + playlist.courses[0].id + '#/course/videos'
            );
        },

        getCourseName(playlist) {
            if (!Array.isArray(playlist.courses) || playlist.courses.length === 0) {
                return '';
            }

            // Assume a playlist has only one course
            return playlist.courses[0].name;
        },

        getSemester(playlist) {
            if (!Array.isArray(playlist.courses) || playlist.courses.length === 0) {
                return '';
            }

            // Assume a playlist has only one course
            return playlist.courses[0].semester;
        },

        removePlaylist(index) {
            this.$emit('removePlaylist', index);
        },
    }
}
</script>
