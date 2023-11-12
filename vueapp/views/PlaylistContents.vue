<template>
    <div v-if="playlist">
        <h2>
            <!-- <PlaylistVisibility
                :showText="false" :visibility="playlist.visibility"/> -->

            {{ playlist.title }}
            <StudipIcon shape="edit" role="clickable" @click="editPlaylist"/>

            <br>
            <div class="oc--tags oc--tags-playlist">
            <Tag v-for="tag in playlist.tags" v-bind:key="tag.id" :tag="tag.tag" />
            </div>
        </h2>

        <PlaylistEditCard v-if="editmode"
            @done="editPlaylistDone"
            @cancel="cancelEditPlaylist"
        />

        <VideosTable
            :playlist="playlist"
            :editable="true"
        />
    </div>
</template>

<script>
import StudipIcon from '@studip/StudipIcon.vue';

import PlaylistVisibility from '@/components/Playlists/PlaylistVisibility.vue';
import PlaylistEditCard from '@/components/Playlists/PlaylistEditCard.vue';
import VideosTable from "@/components/Videos/VideosTable.vue";

import Tag from '@/components/Tag.vue'


import { mapGetters } from "vuex";

export default {
    name: "PlaylistContents",

    props:['token'],

    components: {
        StudipIcon,
        PlaylistVisibility,
        PlaylistEditCard,
        VideosTable, Tag
    },

    data() {
        return {
            editmode: false,
        }
    },

    computed: {
        ...mapGetters([
            'playlist',
        ])
    },

    async mounted() {
        this.$store.dispatch('loadPlaylist', this.token);
    },

    unmounted() {
        this.$store.dispatch('setPlaylist', null);
    },

    methods: {
        editPlaylist() {
            this.editmode = true;
        },

        editPlaylistDone() {
            this.editmode = false;
        },

        cancelEditPlaylist() {
            this.editmode = false;
        },
    }

}
</script>
