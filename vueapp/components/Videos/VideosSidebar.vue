<template>
    <div class="sidebar-widget " id="sidebar-navigation">
        <div class="sidebar-widget-header" v-translate>
            Navigation
        </div>
        <div class="sidebar-widget-content">
            <ul class="widget-list widget-links sidebar-navigation">
                <li :class="{
                    active: fragment == 'videos'
                    }">
                    <router-link :to="{ name: 'videos' }">
                        Videos
                    </router-link>
                </li>
                <li :class="{
                    active: fragment == 'videosTrashed'
                    }">
                    <router-link :to="{ name: 'videosTrashed' }">
                        Gelöschte Videos
                    </router-link>
                </li>
                <li :class="{
                    active: fragment == 'playlists' || fragment == 'playlist_edit'
                    }">
                    <router-link :to="{ name: 'playlists' }">
                        Wiedergabelisten
                    </router-link>
                </li>
            </ul>
        </div>
    </div>

    <div class="sidebar-widget" id="sidebar-actions">
        <div class="sidebar-widget-header" v-translate>
            Aktionen
        </div>
        <div class="sidebar-widget-content">
            <ul class="widget-list oc--sidebar-links widget-links">
                <li @click="$emit('uploadVideo')" v-if="fragment == 'videos'">
                    <studip-icon style="margin-left: -20px;" shape="upload" role="clickable"/>
                    {{ $gettext('Medien Hochladen') }}
                </li>
                <!--
                <li @click="$emit('recordVideo')" v-if="fragment == 'videos'">
                    <studip-icon style="margin-left: -20px;" shape="video" role="clickable"/>
                    {{ $gettext('Video Aufnehmen') }}
                </li>
                -->

                <li @click="addVideosToPlaylist" v-if="fragment == 'playlist_edit'">
                    <studip-icon style="margin-left: -20px;" shape="video" role="clickable"/>
                    {{ $gettext('Videos hinzufügen') }}
                </li>

                <li @click="$emit('allowDownloadForPlaylist')"
                    v-if="fragment == 'playlist_edit' && downloadSetting!=='never' && !isDownloadAllowedForPlaylist">
                    <studip-icon style="margin-left: -20px;" shape="video" role="clickable"/>
                    {{ $gettext('Mediendownloads erlauben') }}
                </li>

                <li @click="$emit('disallowDownloadForPlaylist')"
                    v-if="fragment == 'playlist_edit' && downloadSetting!=='never' && isDownloadAllowedForPlaylist">
                    <studip-icon style="margin-left: -20px;" shape="video" role="clickable"/>
                   {{ $gettext(' Mediendownloads verbieten') }}
                </li>

                <li @click="$emit('sortVideo')" v-if="fragment == 'playlist_edit' && !videoSortMode">
                    <studip-icon style="margin-left: -20px;" shape="hamburger" role="clickable"/>
                    {{ $gettext('Videos Sortieren') }}
                </li>
                <li @click="$emit('saveSortVideo')" v-if="fragment == 'playlist_edit' && videoSortMode">
                    <studip-icon style="margin-left: -20px;" shape="accept" role="clickable"/>
                    {{ $gettext('Sortierung Speichern') }}
                </li>
                <li @click="$emit('cancelSortVideo')" v-if="fragment == 'playlist_edit' && videoSortMode">
                    <studip-icon style="margin-left: -20px;" shape="decline" role="clickable"/>
                    {{ $gettext('Sortierung Abbrechen') }}
                </li>
                <li @click="true" v-if="fragment == 'edit'">
                </li>

                <li @click="createPlaylist" v-if="fragment == 'playlists'">
                    <studip-icon style="margin-left: -20px;" shape="add" role="clickable"/>
                    {{ $gettext('Wiedergabeliste anlegen') }}
                </li>
            </ul>
        </div>
    </div>

    <LoadingSpinner v-if="axios_running"/>
</template>

<script>
import { mapGetters } from "vuex";

import StudipIcon from '@studip/StudipIcon.vue';
import LoadingSpinner from '@/components/LoadingSpinner';

export default {
    name: 'episodes-action-widget',
    components: {
        StudipIcon,     LoadingSpinner
    },

    emits: ['uploadVideo', 'recordVideo',
            'sortVideo', 'saveSortVideo', 'cancelSortVideo',
            'allowDownloadForPlaylist', 'disallowDownloadForPlaylist'
            ],

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
            'videoSortMode', 'playlist',
            'axios_running', 'downloadSetting',
        ]),

        isDownloadAllowedForPlaylist() {
            if (this.playlist) {
                if (this.playlist['allow_download'] === null) {
                    return this.downloadSetting === 'allow';
                }
                else {
                    return this.playlist['allow_download'];
                }
            }
            return false;
        }
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
        this.$store.dispatch('simpleConfigListRead');
    }
}
</script>
