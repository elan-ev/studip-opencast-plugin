<template>
    <tr :key="playlist.id">
        <td v-if="selectable">
             <input type="checkbox" :checked="isChecked" @click.stop="togglePlaylist">
        </td>

        <td>
            {{ playlist.title }}
            <span v-if="playlist?.default_course_tooltip" class="tooltip tooltip-important" data-tooltip title="" tabindex="0"
            >
                <span class="tooltip-content" v-html="playlist.default_course_tooltip"></span>
            </span>

            <div class="oc--tags oc--tags-playlist">
            <Tag v-for="tag in playlist.tags" v-bind:key="tag.id" :tag="tag.tag" />
            </div>
        </td>

        <td>
            {{ courseName }}
        </td>

        <td>
            {{ semester }}
        </td>

        <td>
            {{ playlist.videos_count }}
        </td>

        <td>
            {{ $gettext('Erstellt am:') }}
            <span v-if="playlist.mkdate">
            {{ $filters.datetime(playlist.mkdate * 1000) }} Uhr
            </span>
            <span v-else>
                {{ $gettext('unbekannt') }}
            </span>
        </td>

        <td v-if="showActions">
           <StudipActionMenu :items="menuItems"
                @addToCourse="addToCourse(playlist)"
                @deletePlaylist="deletePlaylist(playlist)"
                @editPlaylist="$router.push({ name: 'playlist', params: { token: playlist.token } })"
                @addToPlaylist="addToPlaylist(playlist)"
           />
        </td>
    </tr>
</template>

<script>
import EmptyPlaylistCard from "@/components/Playlists/EmptyPlaylistCard"
import ConfirmDialog from '@/components/ConfirmDialog'
import StudipButton from '@/components/Studip/StudipButton'
import StudipActionMenu from '@/components/Studip/StudipActionMenu'
import PlaylistVisibility from '@/components/Playlists/PlaylistVisibility.vue'
import Tag from '@/components/Tag.vue'

export default {
    name: "PlaylistCard",

    components: {
        StudipButton,           ConfirmDialog,
        EmptyPlaylistCard,      StudipActionMenu,
        PlaylistVisibility,     Tag
    },

    props: {
        playlist: Object,
        showActions: {
            type: Boolean,
            default: true,
        },
        selectable: {
            type: Boolean,
            default: false,
        },
        selectedPlaylists: Object,
    },

    emits: ['togglePlaylist', 'addToCourse', 'deletePlaylist'],

    data() {
        return {
            menuItems: [
                {
                    id: 1,
                    label: this.$gettext('Bearbeiten'),
                    icon: 'edit',
                    emit: 'editPlaylist'
                },
                {
                    id: 2,
                    label: this.$gettext('Videos hinzufügen'),
                    icon: 'add',
                    emit: 'addToPlaylist'
                },
                {
                    id: 2,
                    label: this.$gettext('Verknüpfte Kurse'),
                    icon: 'group',
                    emit: 'addToCourse'
                },
                {
                    id: 3,
                    label: this.$gettext('Löschen'),
                    icon: 'trash',
                    emit: 'deletePlaylist'
                }
            ]
        }
    },

    computed: {
        isChecked() {
            return this.selectedPlaylists.indexOf(this.playlist.token) >= 0;
        },

        course() {
            if (!Array.isArray(this.playlist.courses) || this.playlist.courses.length === 0) {
                return null;
            }

            // Assume a playlist has only one course
            return this.playlist.courses[0];
        },

        courseName() {
            return this.course?.name ?? '';
        },

        semester() {
            // Assume a playlist has only one course
            return this.course?.semester ?? '';
        }
    },

    methods: {
        togglePlaylist(e) {
            this.$emit('togglePlaylist', {
                token: this.playlist.token,
                checked: e.target.checked ? true : false,
            })
        },

        removeVideo() {
            let view = this;
            this.$store.dispatch('deleteVideo', this.playlist.id)
            .then(() => {
                view.DeleteConfirmDialog = false;
            });
        },

        addToCourse(playlist) {
            this.$emit('addToCourse', playlist);
        },

        deletePlaylist(playlist) {
           this.$emit('deletePlaylist', playlist);
        },

        addToPlaylist(playlist) {
            this.$store.dispatch('loadPlaylist', playlist);
            this.$store.dispatch('togglePlaylistAddVideosDialog', true);
        }
    }
}
</script>
