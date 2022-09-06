<template>
    <div v-if="playlist">
        <h2>
            <PlaylistVisibility
                :showText="false" :visibility="playlist.visibility"/>

            {{ playlist.title }}
            <StudipIcon shape="edit" role="clickable" @click="editPlaylist"/>

            <br>
            <div class="oc--tags">
            <Tag v-for="tag in playlist.tags" v-bind:key="tag.id" :tag="tag.tag" />
            </div>
        </h2>


        <StudipDialog
            v-if="editmode"
            :title="$gettext('Wiedergabeliste bearbeiten')"
            :confirmText="$gettext('Speichern')"
            :confirmClass="'accept'"
            :closeText="$gettext('Abbrechen')"
            :closeClass="'cancel'"
            height="500"
            @close="cancelEditPlaylist"
            @confirm="updatePlaylist"
        >
            <template v-slot:dialogContent>
                <form class="default">
                    <label>
                        Name
                        <input type="text" v-model="eplaylist.title">
                    </label>

                    <label>
                        Sichtbarkeit
                        <select v-model="eplaylist.visibility">
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
                    </label>

                    <label>
                        Tags
                        <PlaylistTags :playlist="eplaylist" @update="updateTags" />
                    </label>
                </form>
             </template>
        </StudipDialog>

        <VideosList/>
    </div>
</template>

<script>
import StudipIcon from '@studip/StudipIcon.vue';
import StudipButton from '@studip/StudipButton.vue';
import StudipDialog from '@studip/StudipDialog.vue';

import PlaylistTags from '@/components/Playlists/PlaylistTags.vue';
import PlaylistVisibility from '@/components/Playlists/PlaylistVisibility.vue';
import VideosList from "@/components/Videos/VideosList";

import Tag from '@/components/Tag.vue'


import { mapGetters } from "vuex";

export default {
    name: "PlaylistEdit",

    props:['token'],

    components: {
        StudipIcon,     StudipButton,
        StudipDialog,
        PlaylistTags,   PlaylistVisibility,
        VideosList,     Tag
    },

    data() {
        return {
            editmode: false,
            eplaylist: {}
        }
    },

    computed: {
        ...mapGetters([
            'playlist',
        ])
    },

    mounted() {
        this.$store.dispatch('setCurrentPlaylist', this.token);
        this.$store.dispatch('loadVideos');
        this.$store.dispatch('loadPlaylist', this.token);
    },

    methods: {
        updateTags() {
             for (let i = 0; i < this.eplaylist.tags.length; i++) {
                if (!this.eplaylist.tags[i].tag) {
                    this.eplaylist.tags[i] = {
                        tag: this.eplaylist.tags[i]
                    }
                }
            }
        },

        editPlaylist() {
            this.eplaylist.title      = this.playlist.title;
            this.eplaylist.visibility = this.playlist.visibility;
            this.eplaylist.tags       = JSON.parse(JSON.stringify((this.playlist.tags)));

            this.editmode = true;
        },

        cancelEditPlaylist() {
            this.editmode = false;
        },

        updatePlaylist() {
            this.playlist.title      = this.eplaylist.title;
            this.playlist.visibility = this.eplaylist.visibility;
            this.playlist.tags       = JSON.parse(JSON.stringify((this.eplaylist.tags)));

            this.$store.dispatch('updatePlaylist', this.playlist).then(() => {
                this.$store.dispatch('updateAvailableTags', this.playlist);
            });

            this.editmode = false;
        }
    }

}
</script>
