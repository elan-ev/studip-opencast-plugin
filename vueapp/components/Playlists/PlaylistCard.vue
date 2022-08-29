<template>
    <tr :key="playlist.id">
        <td>
             <input type="checkbox">
        </td>

        <td>
            <router-link :to="{ name: 'playlist_edit', params: { token: playlist.token, playlist: playlist } }">
                {{ playlist.title }}
            </router-link>

            <div class="oc--tags">
            <Tag v-for="tag in playlist.tags" v-bind:key="tag.id" :tag="tag.tag" />
            </div>
        </td>

        <td></td>

        <td>
            <PlaylistVisibility css="oc--playlist-visibility" :visibility="playlist.visibility"/>
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
           <StudipActionMenu :items="menuItems"
                @addToCourse="addToCourse(this.playlist)"
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
                    label: this.$gettext('Verknüpfte Kurse'),
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
        },

        addToCourse(playlist) {
            this.$emit('addToCourse', playlist);
        }
    }
}
</script>