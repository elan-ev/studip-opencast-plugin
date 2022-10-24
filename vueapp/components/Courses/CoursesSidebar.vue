<template>
    <div class="sidebar-widget " id="sidebar-navigation">
        <div class="sidebar-widget-header" v-translate>
            Navigation
        </div>
        <div class="sidebar-widget-content">
            <ul class="widget-list widget-links sidebar-navigation">
                <li :class="{
                    active: currentView == 'videos'
                    }"
                    v-on:click="setView('videos')">
                    <router-link :to="{ name: 'course' }">
                        Videos
                    </router-link>
                </li>
                <li :class="{
                    active: currentView == 'schedule'
                    }"
                    v-if="can_schedule"
                    v-on:click="getScheduleList">
                    <router-link :to="{ name: 'schedule' }">
                        Aufzeichnungen planen
                    </router-link>
                </li>
            </ul>
        </div>
    </div>

    <div class="sidebar-widget" v-if="currentView == 'videos'">
        <div class="sidebar-widget-header" v-translate>
            Wiedergabelisten
        </div>
        <div class="sidebar-widget-content">
            <ul class="widget-list widget-links sidebar-navigation">
                <li :class="{
                    active: currentPlaylist == 'all'
                    }"
                    v-on:click="setPlaylist('all')">
                    <router-link :to="{ name: 'course' }">
                        Videos ohne Wiedergabeliste
                    </router-link>
                </li>
                <li :class="{
                    active: currentPlaylist == playlist.token
                    }"
                    v-for="playlist in playlists"
                    v-bind:key="playlist.token"
                    v-on:click="setPlaylist(playlist.token)">
                    <router-link :to="{ name: 'course' }">
                        {{ playlist.title }}
                    </router-link>
                </li>
            </ul>
        </div>
    </div>

    <template v-if="currentView == 'schedule'">
        <div v-if="semester_list.length" class="sidebar-widget " id="sidebar-actions">
            <div class="sidebar-widget-header" v-translate>
                Semesterfilter
            </div>
            <div class="sidebar-widget-content">
                <select class="sidebar-selectlist submit-upon-select" v-model="semesterFilter">
                    <option v-for="semester in semester_list"
                        :key="semester.id"
                        :value="semester.id"
                        :selected="semester.id == semester_filter"
                        v-translate>
                        {{ semester.name }}
                    </option>
                </select>
            </div>
        </div>
    </template>
    <template v-else>
        <div class="sidebar-widget " id="sidebar-actions" v-if="canEdit">
            <div class="sidebar-widget-header" v-translate>
                Aktionen
            </div>
            <div class="sidebar-widget-content">
                <ul class="widget-list oc--sidebar-links widget-links">
                    <li @click="$emit('uploadVideo')">
                        <studip-icon style="margin-left: -20px;" shape="upload" role="clickable"/>
                        Medien Hochladen
                    </li>
                    <li>
                        <a :href="recordingLink" target="_blank">
                            <studip-icon style="margin-left: -20px;" shape="video" role="clickable"/>
                            Video Aufnehmen
                        </a>
                    </li>
                    <li v-if="canToggleVisibility">
                        <a v-if="course_config['series']['visibility'] === 'invisible'" @click="setVisibility('visible')" target="_blank">
                            <studip-icon style="margin-left: -20px;" shape="visibility-invisible" role="clickable"/>
                            Reiter sichtbar schalten
                        </a>
                        <a v-else @click="setVisibility('invisible')" target="_blank">
                            <studip-icon style="margin-left: -20px;" shape="visibility-visible" role="clickable"/>
                            Reiter verbergen
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </template>
</template>

<script>
import { mapGetters } from "vuex";

import StudipIcon from '@studip/StudipIcon.vue';

export default {
    name: 'episodes-action-widget',
    components: {
        StudipIcon
    },

    emits: ['uploadVideo', 'recordVideo'],

    data() {
        return {
            showAddDialog: false,
            semesterFilter: null,
        }
    },

    computed: {
        ...mapGetters(["playlists", "currentView",
            "cid", "semester_list", "semester_filter", 'currentUser',
            'simple_config_list', 'course_config', 'currentPlaylist']),

        fragment() {
            return this.$route.name;
        },

        can_schedule() {
            try {
                return this.cid !== undefined && this.currentUser.can_edit && this.simple_config_list['settings']['OPENCAST_ALLOW_SCHEDULER'];
            } catch (error) {
                return false;
            }
        },

        recordingLink() {
            if (!this.simple_config_list.settings || !this.course_config) {
                return;
            }

            let config_id = this.simple_config_list.settings['OPENCAST_DEFAULT_SERVER'];
            let server    = this.simple_config_list.server[config_id];

            // use the first avai
            return window.STUDIP.URLHelper.getURL(
                server.studio, {
                    'upload.seriesId' : this.course_config['series']['series_id'],
                    'upload.acl'      : false,
                    'return.target'   : window.STUDIP.URLHelper.getURL('plugins.php/opencast/course?cid=' + this.cid),
                    'return.label'    : 'Stud.IP'
                }
            );
        },

        canEdit() {
            if (!this.course_config) {
                return false;
            }

            return this.course_config.edit_allowed;
        },

        canToggleVisibility() {
            return window.OpencastPlugin.STUDIP_VERSION == '4.6';
        }
    },

    methods: {
        setPlaylist(token) {
            this.$store.commit('setCurrentPlaylist', token);
            this.$store.commit('clearPaging');

            if (token === 'all' || token === null) {
                 this.$store.dispatch('loadCourseVideos', {
                    cid: this.cid,
                });
            } else {
                this.$store.dispatch('loadPlaylistCourseVideos', {
                    cid: this.cid,
                    token: token
                });
            }

        },

        getScheduleList() {
            this.$store.dispatch('updateView', 'schedule');
            this.$store.dispatch('clearMessages');
            this.$store.dispatch('getScheduleList');
        },

        setView(page) {
            this.$store.dispatch('updateView', page);

            if (this.currentPlaylist === 'all' || this.currentPlaylist === null) {
                 this.$store.dispatch('loadCourseVideos', {
                    cid: this.cid,
                });
            } else {
                this.$store.dispatch('loadPlaylistCourseVideos', {
                    cid: this.cid,
                    token: this.currentPlaylist
                });
            }
        },

        async setVisibility(visibility) {
            await this.$store.dispatch('setVisibility', {'cid': this.cid, 'visibility': visibility});
            this.$router.go(); // Reload page to make changes visible in navigation tab
        }
    },

    mounted() {
        this.$store.dispatch('loadPlaylists');

        this.$store.dispatch('simpleConfigListRead');
        this.$store.dispatch('loadCourseConfig', this.cid);
        this.semesterFilter = this.semester_filter;
    },

    watch: {
        semesterFilter(newValue, oldValue) {
            if (newValue && oldValue && newValue != oldValue) {
                this.$store.dispatch('setSemesterFilter', newValue);
                this.$store.dispatch('clearMessages');
                this.$store.dispatch('getScheduleList');
            }
        },

        currentPlaylist(newValue, oldValue) {
            if (newValue !== oldValue) {
                this.setPlaylist(newValue);
            }
        }
    },
}
</script>
