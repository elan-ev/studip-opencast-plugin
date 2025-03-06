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

    <div class="sidebar-widget" id="sidebar-actions" v-if="fragment != 'videosTrashed' && (fragment == playlist || canShowStudio || canShowUpload)">
        <div class="sidebar-widget-header">
            {{ $gettext('Aktionen') }}
        </div>
        <div class="sidebar-widget-content">
            <ul class="widget-list oc--sidebar-links widget-links">
                <li v-if="fragment == 'videos' && currentUserSeries && canShowUpload">
                    <a href="#" @click.prevent="$emit('uploadVideo')">
                        <studip-icon style="margin-left: -20px;" shape="upload" role="clickable"/>
                        {{ $gettext('Medien hochladen') }}
                    </a>
                </li>
                <li v-if="fragment == 'videos' && currentUserSeries && canShowStudio">
                    <a :href="recordingLink" target="_blank">
                        <studip-icon style="margin-left: -20px;" shape="video" role="clickable"/>
                        {{ $gettext('Video aufnehmen') }}
                    </a>
                </li>

                <li v-if="fragment == 'playlist'">
                    <a href="#" @click.prevent="openPlaylistAddVideosDialog">
                        <studip-icon style="margin-left: -20px;" shape="add" role="clickable"/>
                        {{ $gettext('Videos hinzufügen') }}
                    </a>
                </li>

                <li v-if="fragment == 'playlist' && downloadSetting!=='never' && !isDownloadAllowedForPlaylist">
                    <a href="#" @click.prevent="$emit('allowDownloadForPlaylist')">
                        <studip-icon style="margin-left: -20px;" shape="video" role="clickable"/>
                        {{ $gettext('Mediendownloads erlauben') }}
                    </a>
                </li>

                <li v-if="fragment == 'playlist' && downloadSetting!=='never' && isDownloadAllowedForPlaylist">
                    <a href="#" @click.prevent="$emit('disallowDownloadForPlaylist')">
                        <studip-icon style="margin-left: -20px;" shape="video" role="clickable"/>
                        {{ $gettext('Mediendownloads verbieten') }}
                    </a>
                </li>

                <li v-if="fragment == 'playlist' && !videoSortMode">
                    <a href="#" @click.prevent="$emit('sortVideo')">
                        <studip-icon style="margin-left: -20px;" shape="hamburger" role="clickable"/>
                        {{ $gettext('Videos sortieren') }}
                    </a>
                </li>
                <li v-if="fragment == 'playlist' && videoSortMode">
                    <a href="#" @click.prevent="$emit('saveSortVideo')">
                        <studip-icon style="margin-left: -20px;" shape="accept" role="clickable"/>
                        {{ $gettext('Sortierung speichern') }}
                    </a>
                </li>
                <li v-if="fragment == 'playlist' && videoSortMode">
                    <a href="#" @click.prevent="$emit('cancelSortVideo')">
                        <studip-icon style="margin-left: -20px;" shape="decline" role="clickable"/>
                        {{ $gettext('Sortierung abbrechen') }}
                    </a>
                </li>

                <li v-if="fragment == 'playlists'">
                    <a href="#" @click.prevent="createPlaylist">
                        <studip-icon style="margin-left: -20px;" shape="add" role="clickable"/>
                        {{ $gettext('Wiedergabeliste anlegen') }}
                    </a>
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
            'simple_config_list', 'currentUserSeries',
            'currentUser'
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

        canShowUpload()
        {
            try {
                return this.currentUserSeries
                    && (
                        this.simple_config_list['settings']['OPENCAST_ALLOW_STUDENT_WORKSPACE_UPLOAD']
                        || ['root', 'admin', 'dozent'].includes(this.currentUser.status)
                    )
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
                    'return.target'    : window.STUDIP.URLHelper.getURL('plugins.php/opencastv3/contents/index#/contents/videos'),
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
