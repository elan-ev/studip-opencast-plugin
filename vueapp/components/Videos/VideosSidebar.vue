<template>
    <div class="sidebar-widget " id="sidebar-navigation">
        <div class="sidebar-widget-header">
            {{ $gettext('Navigation') }}
        </div>
        <div class="sidebar-widget-content">
            <ul class="widget-list widget-links sidebar-navigation">
                <li :class="{
                    active: fragment == 'videos'
                    }">
                    <router-link :to="{ name: 'videos' }">
                        {{ $gettext('Videos') }}
                    </router-link>
                </li>
                <li :class="{
                    active: fragment == 'videosTrashed'
                    }">
                    <router-link :to="{ name: 'videosTrashed' }">
                        {{ $gettext('Gelöschte Videos') }}
                    </router-link>
                </li>
                <!--
                <li :class="{
                    active: fragment == 'playlists' || fragment == 'playlist'
                    }">
                    <router-link :to="{ name: 'playlists' }">
                        {{ $gettext('Wiedergabelisten') }}
                    </router-link>
                </li>
                -->
            </ul>
        </div>
    </div>

    <div class="sidebar-widget" id="sidebar-actions" v-if="fragment != 'videosTrashed'">
        <div class="sidebar-widget-header">
            {{ $gettext('Aktionen') }}
        </div>
        <div class="sidebar-widget-content">
            <ul class="widget-list oc--sidebar-links widget-links">
                <li @click="$emit('uploadVideo')" v-if="fragment == 'videos' && currentUserSeries">
                    <studip-icon style="margin-left: -20px;" shape="upload" role="clickable"/>
                    {{ $gettext('Medien hochladen') }}
                </li>
                <li>
                    <a :href="recordingLink" v-if="fragment == 'videos' && currentUserSeries && canShowStudio" target="_blank">
                        <studip-icon style="margin-left: -20px;" shape="video" role="clickable"/>
                        {{ $gettext('Video aufnehmen') }}
                    </a>
                </li>

                <li @click="openPlaylistAddVideosDialog" v-if="fragment == 'playlist'">
                    <studip-icon style="margin-left: -20px;" shape="add" role="clickable"/>
                    {{ $gettext('Videos hinzufügen') }}
                </li>

                <li @click="$emit('allowDownloadForPlaylist')"
                    v-if="fragment == 'playlist' && downloadSetting!=='never' && !isDownloadAllowedForPlaylist">
                    <studip-icon style="margin-left: -20px;" shape="video" role="clickable"/>
                    {{ $gettext('Mediendownloads erlauben') }}
                </li>

                <li @click="$emit('disallowDownloadForPlaylist')"
                    v-if="fragment == 'playlist' && downloadSetting!=='never' && isDownloadAllowedForPlaylist">
                    <studip-icon style="margin-left: -20px;" shape="video" role="clickable"/>
                   {{ $gettext('Mediendownloads verbieten') }}
                </li>

                <li @click="$emit('sortVideo')" v-if="fragment == 'playlist' && !videoSortMode">
                    <studip-icon style="margin-left: -20px;" shape="hamburger" role="clickable"/>
                    {{ $gettext('Videos sortieren') }}
                </li>
                <li @click="$emit('saveSortVideo')" v-if="fragment == 'playlist' && videoSortMode">
                    <studip-icon style="margin-left: -20px;" shape="accept" role="clickable"/>
                    {{ $gettext('Sortierung speichern') }}
                </li>
                <li @click="$emit('cancelSortVideo')" v-if="fragment == 'playlist' && videoSortMode">
                    <studip-icon style="margin-left: -20px;" shape="decline" role="clickable"/>
                    {{ $gettext('Sortierung abbrechen') }}
                </li>

                <li @click="createPlaylist" v-if="fragment == 'playlists'">
                    <studip-icon style="margin-left: -20px;" shape="add" role="clickable"/>
                    {{ $gettext('Wiedergabeliste anlegen') }}
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
            'simple_config_list', 'currentUserSeries'
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
        },

        canShowStudio() {
            try {
                return this.currentUserSeries && this.simple_config_list['settings']['OPENCAST_ALLOW_STUDIO']
            } catch (error) {
                return false;
            }
        },

        recordingLink() {
            if (!this.simple_config_list.settings || !this.canShowStudio) {
                return;
            }

            let config_id = this.simple_config_list.settings['OPENCAST_DEFAULT_SERVER'];
            let server    = this.simple_config_list.server[config_id];

            // use the first avai
            return window.STUDIP.URLHelper.getURL(
                server.studio, {
                    'upload.seriesId'  : this.currentUserSeries,
                    'upload.acl'       : false,
                    'upload.workflowId': this.getWorkflow(config_id),
                    'return.target'    : window.STUDIP.URLHelper.getURL('plugins.php/opencast/contents/index#/contents/videos'),
                    'return.label'     : 'Stud.IP'
                }
            );
        },
    },

    methods: {
        openPlaylistAddVideosDialog() {
            this.$store.dispatch('togglePlaylistAddVideosDialog', true);
        },

        createPlaylist() {
            this.$store.dispatch('addPlaylistUI', true);
        },

        getWorkflow(config_id) {
            let wf_id = this.simple_config_list?.workflow_configs.find(wf_config => wf_config['config_id'] == config_id && wf_config['used_for'] === 'studio')['workflow_id'];
            return this.simple_config_list?.workflows.find(wf => wf['id'] == wf_id)['name'];
        }
    },

    mounted() {
        this.$store.dispatch('simpleConfigListRead');
    }
}
</script>
