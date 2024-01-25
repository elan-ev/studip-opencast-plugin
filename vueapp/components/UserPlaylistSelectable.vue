<template>
    <form class="default">
        <fieldset>
            <legend>
                {{ $gettext('Weiteren Wiedergabelisten hinzufügen') }}
            </legend>

            <label>
                <studip-select :options="playlistsOptions" v-model="selectedPlaylistOption"
                    label="name"
                    track-by="token"
                    :selectable="option => !option.header"
                    :filterable="false"
                    @search="updateSearch"
                    :placeholder="$gettext('Wiedergabeliste auswählen')"
                >
                    <template #list-header>
                        <li style="text-align: center">
                            <b>{{ $gettext('Wiedergabelisten') }}</b>
                        </li>
                    </template>
                    <template #no-options="{ search, searching, loading }">
                        {{ $gettext('Keine Wiedergabelisten gefunden!')}}
                    </template>
                    <template #selected-option="option">
                        <span class="vs__option">
                            {{ option.name }}
                        </span>
                    </template>
                    <template #option="{ name }">
                        <span class="vs__option">
                            {{ name }}
                        </span>
                    </template>
                </studip-select>
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
import StudipSelect from "@studip/StudipSelect";

export default {
    name: 'UserPlaylistSelectable',

    components: {
        StudipButton,
        StudipSelect
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
            selectedPlaylistOption: null,
            search: null
        }
    },

    computed: {

        currentPlaylist() {
            return this.playlists.find(p => p.token === this.selectedPlaylistOption?.token);
        },

        filteredPlaylists() {
            let search    = this.search ? this.search.toLowerCase() : null;

            return this.playlists.filter((playlist) => {
                let courseSearch = this.search && playlist.courses.findIndex(c => {
                    let courseName = (c.name + ' (' + c.semester + ')').toLowerCase();
                    return courseName.includes(search);
                }) >= 0;

                return (
                    (courseSearch || !this.search || playlist['title'].toLowerCase().indexOf(search) >= 0)
                    &&
                    (!this.selectedPlaylists || !this.selectedPlaylists.map(p => { return p.token}).includes(playlist.token))
                );
            });
        },

        sortedPlaylistsCourses() {
            let courses = [];

            // Get distinct courses
            for (const playlist of this.filteredPlaylists) {
                for (const course of playlist.courses) {
                    if (courses.findIndex(c => c.id === course.id) === -1) {
                        courses.push(course);
                    }
                }
            }

            // Sort courses by semester end date
            courses.sort(function (a, b) {
                return b.end_semester_begin - a.end_semester_begin;
            });

            return courses
        },

        playlistsOptions() {
            let options = [];

            // Playlists without linked courses
            let unlinkedPlaylists = this.filteredPlaylists.filter(p => !p.courses || p.courses.length === 0);

            if (unlinkedPlaylists.length > 0) {
                options.push({
                    name: this.$gettext('Keine Kursverknüpfung'),
                    header: true,
                });

                for (const playlist of unlinkedPlaylists) {
                    // Check if playlist is in course
                    options.push({
                        name: playlist.title,
                        token: playlist.token,
                        header: false,
                    });
                }
            }

            // Playlists with linked courses
            for (const course of this.sortedPlaylistsCourses) {
                options.push({
                   name: course.name + ' (' + course.semester + ')',
                   header: true,
                });

                for (const coursePlaylist of this.filteredPlaylists) {
                    // Check if playlist is in course
                    if (coursePlaylist.courses.findIndex(c => c.id === course.id) !== -1) {
                        options.push({
                            name: coursePlaylist.title,
                            token: coursePlaylist.token,
                            header: false,
                        });
                    }
                }
            }

            return options;
        },
    },

    methods: {
        updateSearch(search, loading) {
            this.search = search;
        },

        returnSelectedPlaylist() {
            this.$emit('add', this.currentPlaylist);
            this.selectedPlaylistOption = null;
        }
    }
}
</script>
