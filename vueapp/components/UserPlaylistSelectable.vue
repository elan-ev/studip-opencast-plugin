<template>
    <form class="default">
        <fieldset>
            <legend>
                {{ $gettext('Weiteren Wiedergabelisten hinzufügen') }}
            </legend>

            <label>
                <input type="text" :placeholder="$gettext('In Wiedergabelisten suchen')" v-model="search">
                <select v-model="currentPlaylist" v-if="filteredPlaylists">
                    <option v-for="playlist in filteredPlaylists"
                        :value="playlist" v-bind:key="playlist.token"
                    >
                        {{ playlist.title }}
                    </option>
                </select>
            </label>
        </fieldset>
        <footer>
            <StudipButton
                :disabled="currentPlaylist == null || currentPlaylist == 0 || currentPlaylist.id == 0"
                icon="accept"
                @click.prevent="returnSelectedPlaylist()"
            >
                {{ $gettext('Zur Wiedergabeliste hinzufügen') }}
            </StudipButton>
        </footer>
    </form>
</template>

<script>
import StudipButton from "@studip/StudipButton";

export default {
    name: 'UserPlaylistSelectable',

    components: {
        StudipButton
    },

    props: {
        playlists: {
            type: Object,
            required: true
        },

        selectedPlaylists: {
            type: Array
        }
    },

    data() {
        return {
            currentPlaylist: null,
            search: null
        }
    },

    computed: {

        filteredPlaylists() {
            let noPlaylistsFound = {};
            noPlaylistsFound['0']= {
                id: 0,
                title: this.$gettext('Keine weiteren Wiedergabelisten gefunden.')
            };

            if (this.playlists.length == 0) {
                this.currentPlaylist = 0;
                return noPlaylistsFound;
            }

            let search    = this.search ? this.search.toLowerCase() : null;

            let playlists = this.playlists.filter((playlist) => {
                return (
                    (!this.search || playlist['title'].toLowerCase().indexOf(search) >= 0)
                    &&
                    (!this.selectedPlaylists || !this.selectedPlaylists.map(p => { return p.token}).includes(playlist.token))
                );
            });

            if (playlists.length > 0) {
                this.currentPlaylist = Object.values(playlists)[0];
            } else {
                this.currentPlaylist = 0;
                return noPlaylistsFound;
            }

            return playlists;
        },
    },

    methods: {
        returnSelectedPlaylist() {
            this.$emit('add', this.currentPlaylist);
        }
    }
}
</script>
