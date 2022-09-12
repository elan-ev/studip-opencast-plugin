<template>
    <div class="sidebar-widget " id="sidebar-navigation">
        <div class="sidebar-widget-header" v-translate>
            Navigation
        </div>
        <div class="sidebar-widget-content">
            <ul class="widget-list widget-links sidebar-navigation">
                <li :class="{
                    active: fragment == 'videos'
                    }"
                    v-on:click="this.$store.dispatch('setCurrentPlaylist', 'all'); this.$store.dispatch('loadVideos')">
                    <router-link :to="{ name: 'videos' }">
                        Videos
                    </router-link>
                </li>
                <li :class="{
                    active: fragment == 'playlists' || fragment == 'playlistvideos'
                    }">
                    <router-link :to="{ name: 'playlists' }">
                        Wiedergabelisten
                    </router-link>
                </li>
            </ul>
        </div>
    </div>

    <div class="sidebar-widget " id="sidebar-actions">
        <div class="sidebar-widget-header" v-translate>
            Aktionen
        </div>
        <div class="sidebar-widget-content">
            <ul class="widget-list oc--sidebar-links widget-links">
                <li @click="$emit('uploadVideo')" v-if="fragment == 'videos'">
                    <studip-icon style="margin-left: -20px;" shape="upload" role="clickable"/>
                    Medien Hochladen
                </li>
                <!--
                <li @click="$emit('recordVideo')" v-if="fragment == 'videos'">
                    <studip-icon style="margin-left: -20px;" shape="video" role="clickable"/>
                    Video Aufnehmen
                </li>
                -->

                <li @click="addVideosToPlaylist" v-if="fragment == 'playlist_edit'">
                    <studip-icon style="margin-left: -20px;" shape="video" role="clickable"/>
                    Videos hinzufügen
                </li>

                <li @click="$emit('sortVideo')" v-if="fragment == 'playlist_edit' && !videoSortMode">
                    <studip-icon style="margin-left: -20px;" shape="hamburger" role="clickable"/>
                    Videos Sortieren
                </li>
                <li @click="$emit('saveSortVideo')" v-if="fragment == 'playlist_edit' && videoSortMode">
                    <studip-icon style="margin-left: -20px;" shape="accept" role="clickable"/>
                    Sortierung Speichern
                </li>
                <li @click="$emit('cancelSortVideo')" v-if="fragment == 'playlist_edit' && videoSortMode">
                    <studip-icon style="margin-left: -20px;" shape="decline" role="clickable"/>
                    Sortierung Abbrechen
                </li>
                <li @click="true" v-if="fragment == 'edit'">
                </li>

                <li @click="createPlaylist" v-if="fragment == 'playlists'">
                    <studip-icon style="margin-left: -20px;" shape="video" role="clickable"/>
                    Wiedergabeliste anlegen
                </li>
            </ul>
        </div>
    </div>
</template>

<script>
import { mapGetters } from "vuex";

import StudipIcon from '@studip/StudipIcon.vue';

export default {
    name: 'episodes-action-widget',
    components: {
        StudipIcon
    },

    emits: ['uploadVideo', 'recordVideo', 'sortVideo', 'saveSortVideo', 'cancelSortVideo'],

    watch: {
        $route(to) {
            //console.log('Route:', to);
        },
    },

    data() {
        return {
            showAddDialog: false
        }
    },

    computed: {
        fragment() {
            return this.$route.name;
        },

        ...mapGetters([
            'videoSortMode', 'playlist'
        ]),
    },

    methods: {
        createPlaylist() {
            this.$store.dispatch('addPlaylistUI', true);
        },

        addVideosToPlaylist() {
            this.$store.commit('setPlaylistForVideos', this.playlist);
            this.$router.push({ name: 'videos'})
        }
    },

    mounted() {
        this.$store.dispatch('loadVideos');
    }
}
</script>
