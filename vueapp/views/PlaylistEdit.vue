<template>
    <div v-if="playlist">
        <h2 v-if="!editmode">
            <PlaylistVisibility
                :showText="false" :visibility="playlist.visibility"/>

            {{ playlist.title }}
            <StudipIcon shape="edit" role="clickable" @click="editPlaylist"/>
        </h2>

         <h2 v-if="editmode">
            <input type="text" class="size-s" v-model="eplaylist.title">

            <select class="size-s" v-model="eplaylist.visibility">
                <option value="internal">
                    {{ $gettext('Intern') }}
                </option>
                <option value="free">
                    {{ $gettext('Nicht gelistet') }}
                </option>
                <option value="public">
                    {{ $gettext('Öffentlich') }}
                </option>
            </select>

            <StudipButton icon="accept" @click.prevent="updatePlaylist">
                Speichern
            </StudipButton>

            <StudipButton icon="cancel" @click.prevent="cancelEditPlaylist">
                Abbrechen
            </StudipButton>
        </h2>

        <PlaylistTags :playlist="playlist" @update="updateTags" />

        <VideosList/>
    </div>
</template>

<script>
import StudipIcon from '@studip/StudipIcon.vue';
import StudipButton from '@studip/StudipButton.vue';

import PlaylistTags from '@/components/Playlists/PlaylistTags.vue';
import PlaylistVisibility from '@/components/Playlists/PlaylistVisibility.vue';
import VideosList from "@/components/Videos/VideosList";

import { mapGetters } from "vuex";

export default {
    name: "PlaylistEdit",

    props:['token'],

    components: {
        StudipIcon,     StudipButton,
        PlaylistTags,   PlaylistVisibility,
        VideosList
    },

    data() {
        return {
            editmode: false,
            eplaylist: {}
        }
    },

    computed: {
        ...mapGetters([
            'playlists',
        ]),

        playlist() {
            return this.playlists[this.token];
        }
    },

    mounted() {
        if (!this.playlists[this.token]) {
            this.$store.dispatch('loadPlaylist', this.token);
        }
    },

    methods: {
        updateTags() {
            this.fixTags();
            this.$store.dispatch('updatePlaylist', this.playlist).then(() => {
                this.$store.dispatch('updateAvailableTags', this.playlist);
            });
        },

        fixTags() {
            for (let i = 0; i < this.playlist.tags.length; i++) {
                if (!this.playlist.tags[i].tag) {
                    this.playlist.tags[i] = {
                        tag: this.playlist.tags[i]
                    }
                }
            }
        },

        editPlaylist() {
            this.eplaylist.title      = this.playlist.title;
            this.eplaylist.visibility = this.playlist.visibility;

            this.editmode = true;
        },

        cancelEditPlaylist() {
            this.editmode = false;
        },

        updatePlaylist() {
            this.playlist.title      = this.eplaylist.title;
            this.playlist.visibility = this.eplaylist.visibility;

            this.$store.dispatch('updatePlaylist', this.playlist);

            this.editmode = false;
        }
    }

}
</script>
