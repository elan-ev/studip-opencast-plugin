<template>
    <table v-if="asTable" class="default">
        <colgroup>
            <col />
            <col style="width: 25%" />
            <col style="width: 25%" />
            <col v-if="removable" style="width: 3%" />
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
            <template v-if="event.playlists.length > 0">
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
                        <studip-icon
                            shape="trash"
                            role="clickable"
                            @click="removePlaylist(index)"
                            style="cursor: pointer"
                        />
                    </td>
                </tr>
            </template>
            <tr v-else>
                <td :colspan="removable ? 4 : 3">
                    {{ $gettext('Keine Wiedergabelisten vorhanden.') }}
                </td>
            </tr>
        </tbody>
    </table>
    <div v-else>
        <div v-if="event.playlists.length > 0" class="playlist-list">
            <div v-for="(playlist, index) in event.playlists" :key="playlist.id" class="playlist-entry">
                <button
                    v-if="removable"
                    class="playlist-remove"
                    @click="removePlaylist(index)"
                    :aria-label="$gettext('Wiedergabeliste entfernen')"
                >
                    <studip-icon shape="trash" />
                </button>
                <div class="playlist-title">{{ playlist.title }}</div>
                <div class="playlist-meta">
                    <span class="playlist-course">{{ getCourseName(playlist) }}</span> |
                    <span class="playlist-semester">{{ getSemester(playlist) }}</span>
                </div>
            </div>
        </div>
        <p v-else>{{ $gettext('Keine Wiedergabelisten vorhanden.') }}</p>
    </div>
</template>

<script>
import StudipIcon from '@studip/StudipIcon';

export default {
    name: 'VideoPlaylists',

    components: { StudipIcon },

    props: {
        event: Object,
        removable: {
            type: Boolean,
            default: true,
        },
        asTable: {
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
    },
};
</script>
