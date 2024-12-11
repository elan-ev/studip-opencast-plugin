<template>
    <table class="default" v-if="event.playlists.length > 0">
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
            <tr v-for="(playlist, index) in event.playlists" v-bind:key="playlist.id">
                <td>
                    {{ playlist.title }}
                </td>
                <td>
                    {{ getCourseName(playlist) }}
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
