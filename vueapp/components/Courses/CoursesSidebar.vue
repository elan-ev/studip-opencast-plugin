<template>
    <div class="sidebar-widget " id="sidebar-navigation">
        <div class="sidebar-widget-header" v-translate>
            Navigation
        </div>
        <div class="sidebar-widget-content">
            <ul class="widget-list widget-links sidebar-navigation">
                <li :class="{
                    active: currentPlaylist == null
                    }"
                    v-on:click="setPlaylist(null)">
                    <router-link :to="{ name: 'videos' }">
                        Videos
                    </router-link>
                </li>
                <li :class="{
                    active: currentPlaylist == playlist.token
                    }"
                    v-for="playlist in playlists"
                    v-bind:key="playlist.token"
                    v-on:click="setPlaylist(playlist.token)">
                    <router-link :to="{ name: 'videos' }">
                        {{ playlist.title }}
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
            <ul class="widget-list oc-widget-list widget-links cw-action-widget">
                <li class="cw-action-widget" @click="$emit('uploadVideo')">
                    <studip-icon style="margin-left: -20px;" shape="upload" role="clickable"/>
                    Medien Hochladen
                </li>
                <li class="cw-action-widget" @click="$emit('recordVideo')">
                    <studip-icon style="margin-left: -20px;" shape="video" role="clickable"/>
                    Video Aufnehmen
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

    methods: {
        setPlaylist(token) {
            this.$store.dispatch('setCurrentPlaylist', token);
            this.$store.dispatch('loadVideos');
        }
    },

    computed: {
        fragment() {
            return this.$route.name;
        },
        ...mapGetters(["playlists", "currentPlaylist"])
    },
    
    mounted() {
        this.$store.dispatch('loadPlaylists');
        this.$store.dispatch('loadVideos');
    }
}
</script>
