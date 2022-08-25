<template>
    <tr :key="playlist.id">
        <td>
             <input type="checkbox">
        </td>

        <td v-on:click='listVideos()'>
            {{ playlist.title }}
        </td>

        <td></td>

        <td>
            {{ playlist.visibility }}
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

        <td>
           <StudipActionMenu :items="menuItems"/>
        </td>
    </tr>
</template>

<script>
import EmptyPlaylistCard from "@/components/Playlists/EmptyPlaylistCard"
import ConfirmDialog from '@/components/ConfirmDialog'
import StudipButton from '@/components/Studip/StudipButton'
import StudipActionMenu from '@/components/Studip/StudipActionMenu'

export default {
    name: "PlaylistCard",

    components: {
        StudipButton, ConfirmDialog,
        EmptyPlaylistCard, StudipActionMenu
    },

    props: {
        playlist: Object
    },

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
                    label: this.$gettext('Zu Kurs hinzufügen'),
                    icon: 'add',
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

    methods: {
        removeVideo() {
            let view = this;
            this.$store.dispatch('deleteVideo', this.playlist.id)
            .then(() => {
                view.DeleteConfirmDialog = false;
            });
        },

        listVideos() {
            this.$store.dispatch('setCurrentPlaylist', this.playlist.token);
            this.$store.dispatch('setPage', 0);
            window.scrollTo(0,0);
            this.$store.dispatch('loadVideos');
            this.$router.push('/contents/playlistvideos');
        }
    }
}
</script>