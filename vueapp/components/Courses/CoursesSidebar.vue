<template>
    <div class="sidebar-widget oc--course-sidebar-widget" id="sidebar-navigation">
        <div class="sidebar-widget-header">
            {{ $gettext('Navigation') }}
        </div>
        <div class="sidebar-widget-content">
            <ul class="widget-list widget-links sidebar-navigation">
                <li :class="{
                    active: currentView == 'videos'
                    }"
                    v-on:click="setView('videos')">
                    <router-link :to="{ name: 'course' }">
                        {{ $gettext('Videos') }}
                    </router-link>
                </li>
                <li :class="{
                    active: currentView == 'schedule'
                    }"
                    v-if="canSchedule"
                    v-on:click="setView('schedule')">
                    <router-link :to="{ name: 'schedule' }">
                        {{ $gettext('Aufzeichnungen planen') }}
                    </router-link>
                </li>
            </ul>
        </div>
    </div>

    <div class="sidebar-widget" v-if="currentView == 'videos'">
        <div class="sidebar-widget-header">
            {{ $gettext('Wiedergabelisten') }}
        </div>
        <div class="sidebar-widget-content">
            <ul class="widget-list widget-links oc--sidebar-links sidebar-navigation">
                <template v-if="hasDefaultPlaylist">
                    <li :class="{
                        active: playlist?.token == p.token
                        }"
                        v-for="p in playlists"
                        v-bind:key="p.token"
                        v-on:click="setPlaylist(p)">
                        <router-link :to="{ name: 'course' }">
                            <div class="oc--playlist-title-contanier">
                                <span class="oc--playlist-title">
                                    {{ p.title }}
                                </span>
                                <div v-if="p.is_default == 1"
                                    class="tooltip oc--playlist-default-icon" :data-tooltip="$gettext('Standard-Kurswiedergabeliste')">
                                    <studip-icon shape="check-circle" :role="playlist?.token == p.token ? 'info_alt' : 'clickable'" :size="16"/>
                                </div>
                            </div>
                        </router-link>
                    </li>

                    <li v-if="canEdit" @click="showCreatePlaylist" data-reject-toggle-sidebar="true">
                        <studip-icon style="margin-top: -2px;" shape="add" role="clickable"/>
                        {{ $gettext('Wiedergabeliste hinzufügen') }}
                    </li>
                </template>
                <template v-else>
                    <li v-if="canEdit" @click="showCreateDefaultPlaylist">
                        <studip-icon style="margin-top: -2px;" shape="add" role="clickable"/>
                        {{ $gettext('Kurswiedergabeliste hinzufügen') }}
                    </li>
                </template>
            </ul>
        </div>
    </div>

    <template v-if="currentView == 'schedule'">
        <div v-if="semester_list.length" class="sidebar-widget " id="sidebar-actions">
            <div class="sidebar-widget-header">
                {{ $gettext('Semesterfilter') }}
            </div>
            <div class="sidebar-widget-content">
                <select class="sidebar-selectlist submit-upon-select" v-model="semesterFilter">
                    <option v-for="semester in semester_list"
                        :key="semester.id"
                        :value="semester.id"
                        :selected="semester.id == semester_filter"
                    >
                        {{ semester.name }}
                    </option>
                </select>
            </div>
        </div>
        <div class="sidebar-widget " id="sidebar-actions" v-if="canSchedule">
            <div class="sidebar-widget-header">
                {{ $gettext('Aktionen') }}
            </div>
            <div class="sidebar-widget-content">
                <div class="oc--sidebar-dropdown-wrapper">
                    <span class="oc--sidebar-dropdown-text">
                        {{ $gettext('Aufzeichnungen in Wiedergabeliste') }}
                    </span>
                    <select class="oc--sidebar-dropdown-select sidebar-selectlist submit-upon-select" v-model="schedulePlaylistToken" @change="updateScheduledRecordingsPlaylists('scheduled')">
                        <option v-for="p in playlists"
                            :key="p.token"
                            :value="p.token"
                            :selected="schedulePlaylistToken == p.token"
                        >
                            {{ p.title }}
                        </option>
                    </select>
                </div>
                <div class="oc--sidebar-dropdown-wrapper">
                    <span class="oc--sidebar-dropdown-text">
                        {{ $gettext('Livestreams in Wiedergabeliste') }}
                    </span>
                    <select class="oc--sidebar-dropdown-select sidebar-selectlist submit-upon-select" v-model="livestreamPlaylistToken" @change="updateScheduledRecordingsPlaylists('livestreams')">
                        <option v-for="p in playlists"
                            :key="p.token"
                            :value="p.token"
                            :selected="livestreamPlaylistToken == p.token"
                        >
                            {{ p.title }}
                        </option>
                    </select>
                </div>
            </div>
        </div>
    </template>
    <template v-else>
        <div class="sidebar-widget " id="sidebar-actions" v-if="(canEdit || canUpload) && hasDefaultPlaylist">
            <div class="sidebar-widget-header">
                {{ $gettext('Wiedergabeliste bearbeiten') }}
            </div>
            <div class="sidebar-widget-content">
                <ul class="widget-list oc--sidebar-links widget-links" @click.capture="toggleSidebarOnResponsive">
                    <template v-if="videoSortMode">
                        <li @click="$emit('saveSortVideo')" v-if="canEdit && videoSortMode">
                            <studip-icon style="margin-left: -20px;" shape="accept" role="clickable"/>
                            {{ $gettext('Sortierung speichern') }}
                        </li>
                        <li @click="$emit('cancelSortVideo')" v-if="canEdit && videoSortMode">
                            <studip-icon style="margin-left: -20px;" shape="decline" role="clickable"/>
                            {{ $gettext('Sortierung abbrechen') }}
                        </li>
                    </template>
                    <template v-else>
                        <li @click="openPlaylistAddVideosDialog" v-if="canEdit || canUpload">
                            <studip-icon style="margin-left: -20px;" shape="add" role="clickable"/>
                            {{ $gettext('Videos hinzufügen') }}
                        </li>
                        <li v-if="canEdit && downloadSetting !== 'never'">
                            <a v-if="!downloadEnabled" @click="setDownload(true)" target="_blank">
                                <studip-icon style="margin-left: -20px;" shape="decline" role="clickable"/>
                                {{ $gettext('Mediendownloads erlauben') }}
                            </a>
                            <a v-else @click="setDownload(false)" target="_blank">
                                <studip-icon style="margin-left: -20px;" shape="accept" role="clickable"/>
                                {{ $gettext('Mediendownloads verbieten') }}
                            </a>
                        </li>
                        <li @click="$emit('sortVideo')" v-if="canEdit">
                            <studip-icon style="margin-left: -20px;" shape="hamburger" role="clickable"/>
                            {{ $gettext('Videos sortieren') }}
                        </li>
                        <li @click="$emit('editPlaylist')" v-if="canEdit">
                            <studip-icon style="margin-left: -20px;" shape="edit" role="clickable"/>
                            {{ $gettext('Metadaten bearbeiten') }}
                        </li>
                    </template>
                </ul>
            </div>
        </div>

        <div class="sidebar-widget " id="sidebar-actions" v-if="(canEdit || canUpload) && hasDefaultPlaylist">
            <div class="sidebar-widget-header">
                {{ $gettext('Veranstaltungsweite Aktionen') }}
            </div>
            <div class="sidebar-widget-content">
                <ul class="widget-list oc--sidebar-links widget-links" @click.capture="toggleSidebarOnResponsive">
                    <template v-if="!videoSortMode">
                        <li>
                            <a :href="recordingLink" target="_blank" v-if="canUpload && course_config?.series?.series_id">
                                <studip-icon style="margin-left: -20px;" shape="video" role="clickable"/>
                                {{ $gettext('Video aufnehmen') }}
                            </a>
                        </li>
                        <li v-if="canToggleVisibility">
                            <a v-if="course_config['series']['visibility'] === 'invisible'" @click="setVisibility('visible')" target="_blank">
                                <studip-icon style="margin-left: -20px;" shape="visibility-invisible" role="clickable"/>
                                {{ $gettext('Reiter sichtbar schalten') }}
                            </a>
                            <a v-else @click="setVisibility('invisible')" target="_blank">
                                <studip-icon style="margin-left: -20px;" shape="visibility-visible" role="clickable"/>
                                {{ $gettext('Reiter verbergen') }}
                            </a>
                        </li>
                        <li v-if="canEdit">
                            <a v-if="!uploadEnabled" @click="setUpload(1)" target="_blank">
                                <studip-icon style="margin-left: -20px;" shape="decline" role="clickable"/>
                                {{ $gettext('Studierendenupload erlauben') }}
                            </a>
                            <a v-else @click="setUpload(0)" target="_blank">
                                <studip-icon style="margin-left: -20px;" shape="accept" role="clickable"/>
                                {{ $gettext('Studierendenupload verbieten') }}
                            </a>
                        </li>
                        <li @click="showChangeDefaultPlaylist" v-if="canEdit" data-reject-toggle-sidebar="true">
                            <studip-icon style="margin-left: -20px;" shape="refresh" role="clickable"/>
                            {{ $gettext('Standard-Kurswiedergabeliste ändern') }}
                        </li>
                        <li v-if="canEdit">
                            <a @click="$emit('copyAll')">
                                <studip-icon style="margin-left: -20px;" shape="export" role="clickable"/>
                                {{ $gettext('Kursinhalte übertragen') }}
                            </a>
                        </li>
                    </template>
                </ul>
            </div>
        </div>

        <PlaylistAddCard v-if="addPlaylist"
            :is-default="isDefault"
            @done="closePlaylistAdd"
            @cancel="closePlaylistAdd"
        />

        <PlaylistsLinkCard v-if="showChangeDefaultDialog"
            :is-default="true"
            :custom-title="$gettext('Kurswiedergabeliste wechseln')"
            @done="closeChangeDefaultPlaylist"
            @cancel="closeChangeDefaultPlaylist"
        />

    </template>
</template>

<script>
import { useRoute } from 'vue-router';
import { mapGetters } from "vuex";

import StudipIcon from '@studip/StudipIcon.vue';
import PlaylistAddCard from '@/components/Playlists/PlaylistAddCard.vue';
import PlaylistsLinkCard from '@/components/Playlists/PlaylistsLinkCard.vue';

export default {
    name: 'episodes-action-widget',
    components: {
        StudipIcon,
        PlaylistAddCard,
        PlaylistsLinkCard,
    },

    emits: ['uploadVideo', 'recordVideo', 'copyAll', 'editPlaylist', 'sortVideo', 'saveSortVideo', 'cancelSortVideo'],

    data() {
        return {
            showAddDialog: false,
            isDefault: false,
            showChangeDefaultDialog: false,
            semesterFilter: null,
            schedulePlaylistToken: null,
            livestreamPlaylistToken: null,
            targetPlaylistToken: null,
            routeObj: null,
        }
    },

    computed: {
        ...mapGetters(["playlists", "currentView", 'addPlaylist',
            "cid", "semester_list", "semester_filter", 'currentUser',
            'simple_config_list', 'course_config', 'playlist',
            'defaultPlaylist', 'videoSortMode', 'downloadSetting',
            'schedule_playlist', 'livestream_playlist'
        ]),

        fragment() {
            return this.$route.name;
        },

        canSchedule() {
            try {
                return this.cid !== undefined &&
                    this.currentUser.can_edit &&
                        this.simple_config_list['settings']['OPENCAST_ALLOW_SCHEDULER'] &&
                        this.hasDefaultPlaylist;
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
                    'upload.seriesId'  : this.course_config['series']['series_id'],
                    'upload.workflowId': this.getWorkflow(config_id),
                    'return.target'    : window.STUDIP.URLHelper.getURL('plugins.php/opencast/course?cid=' + this.cid),
                    'return.label'     : 'Stud.IP'
                }
            );
        },

        canEdit() {
            if (!this.course_config) {
                return false;
            }

            return this.course_config.edit_allowed;
        },

        canUpload() {
            if (!this.course_config) {
                return false;
            }

            return this.course_config.upload_allowed;
        },

        uploadEnabled() {
            if (!this.course_config) {
                return false;
            }

            return this.course_config.upload_enabled == 1;
        },

        downloadEnabled() {
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

        canToggleVisibility() {
            return window.OpencastPlugin.STUDIP_VERSION == '4.6' && this.canEdit;
        },

        hasDefaultPlaylist() {
            return this.course_config?.has_default_playlist;
        },
    },

    methods: {
        setPlaylist(playlist) {
            this.$store.dispatch('setPlaylist', playlist);
            this.toggleSidebarOnResponsive();
        },

        async setView(page) {
            this.$store.dispatch('updateView', page);
            if (page == 'schedule') {
                this.$store.dispatch('clearMessages');
                this.$store.dispatch('getScheduleList');
                // Make sure playlists are loaded.
                await this.$store.dispatch('loadScheduledRecordingPlaylists');
                this.schedulePlaylistToken = this.schedule_playlist?.token;
                this.livestreamPlaylistToken = this.livestream_playlist?.token;
            }
        },

        async setVisibility(visibility) {
            await this.$store.dispatch('setVisibility', {'cid': this.cid, 'visibility': visibility});
            this.$router.go(); // Reload page to make changes visible in navigation tab
        },

        setDownload(download) {
            this.$store.dispatch('setAllowDownloadForPlaylist', download)
        },

        setUpload(upload) {
            this.$store.dispatch('setUpload', {'cid': this.cid, 'upload': upload})
            .then(() => {
                this.$store.dispatch('loadCourseConfig', this.cid);
            });

        },

        showCreatePlaylist() {
            this.$store.dispatch('addPlaylistUI', true);
        },

        showCreateDefaultPlaylist() {
            this.isDefault = true;
            this.$store.dispatch('addPlaylistUI', true);
        },

        showChangeDefaultPlaylist() {
            this.showChangeDefaultDialog = true;
        },

        closeChangeDefaultPlaylist() {
            this.showChangeDefaultDialog = false;
        },

        closePlaylistAdd() {
            this.isDefault = false;
            this.$store.dispatch('addPlaylistUI', false);
        },

        openPlaylistAddVideosDialog() {
            this.$store.dispatch('togglePlaylistAddVideosDialog', true);
        },

        getWorkflow(config_id) {
            let wf_id = this.simple_config_list?.workflow_configs.find(wf_config => wf_config['config_id'] == config_id && wf_config['used_for'] === 'studio')['workflow_id'];
            return this.simple_config_list?.workflows.find(wf => wf['id'] == wf_id)['name'];
        },

        updateScheduledRecordingsPlaylists(type) {
            this.$store.dispatch('clearMessages');
            if (type == 'scheduled') {
                this.$store.dispatch('setSchedulePlaylist', this.schedulePlaylistToken)
                .then(({data}) => {
                    this.$store.dispatch('addMessage', data.message);
                }).finally(async () => {
                    await this.$store.dispatch('loadPlaylists');
                    this.schedulePlaylistToken = this.schedule_playlist?.token;
                });
            } else if (type == 'livestreams') {
                this.$store.dispatch('setLivestreamPlaylist', this.livestreamPlaylistToken)
                .then(({data}) => {
                    this.$store.dispatch('addMessage', data.message);
                }).finally(async () => {
                    await this.$store.dispatch('loadPlaylists');
                    this.livestreamPlaylistToken = this.livestream_playlist?.token;
                });
            }
            this.toggleSidebarOnResponsive();
        },

        async setTargetPlaylist() {
            if (this.targetPlaylistToken) {
                if (this.playlist?.token == this.targetPlaylistToken) {
                    this.targetPlaylistToken = null;
                    return;
                }
                let playlist_filtered = this.playlists.filter(playlist => playlist.token == this.targetPlaylistToken)
                if (playlist_filtered?.length) {
                    await this.$nextTick();
                    this.setPlaylist(playlist_filtered[0]);
                    await this.$store.dispatch('loadPlaylists');
                    this.targetPlaylistToken = null;
                }
            }
        },

        ensureRenderedSidebarIsRemoved() {
            const sidebars = document.querySelectorAll('.sidebar-widget');
            for (let sidebar of sidebars) {
                if (sidebar.classList.contains('oc--course-sidebar-widget') === false) {
                    sidebar.remove();
                }
            }
        },

        /**
         * This method is used to toggle sidebar in responsive view.
         * This gets called on the outermost element of the action and playlist actions on ul elements.
         * To prevent an element from toggling the sidebar:
         *      i.e. when the dialog is opened in this component like AddPlaylis
         *  data-reject-toggle-sidebar attribute on the element must be set to true.
         *
         * @param {object} [event=null]
         *
         */
        toggleSidebarOnResponsive(event = null) {
            if (event && event.target.dataset?.rejectToggleSidebar && event.target.dataset.rejectToggleSidebar != 'false') {
                return;
            }
            let toggle_btn = document.getElementById('toggle-sidebar');
            let sidebar = document.getElementById('sidebar');
            if (sidebar && sidebar.classList.contains('responsive-show') && toggle_btn) {
                toggle_btn.click();
            }
        },

        async handleView() {
            if (this.routeObj?.path.includes('/schedule') && this.currentView != 'schedule' && this.canSchedule) {
                await this.$store.dispatch('loadPlaylists');
                await this.setView('schedule');
            } else if (this.routeObj?.path.includes('/videos') && this.currentView != 'videos') {
                await this.setView('videos');
            }
        }
    },

    async mounted() {
        this.$store.dispatch('simpleConfigListRead');
        this.semesterFilter = this.semester_filter;

        const route = useRoute();
        this.routeObj = route;
        if (this.routeObj?.query?.taget_pl_token) {
            this.targetPlaylistToken = route.query.taget_pl_token
        }

        await this.handleView();
    },

    beforeMount () {
        // Here we remove the rendered sidebar from the DOM before mount, to avoid any conflicts.
        this.ensureRenderedSidebarIsRemoved();
    },

    watch: {
        semesterFilter(newValue, oldValue) {
            if (newValue && oldValue && newValue != oldValue) {
                this.$store.dispatch('setSemesterFilter', newValue);
                this.$store.dispatch('clearMessages');
                this.$store.dispatch('getScheduleList');
            }
            this.toggleSidebarOnResponsive();
        },

        playlists(newValue) {
            if (newValue?.length && this.targetPlaylistToken) {
                this.setTargetPlaylist();
            }
        },

        canSchedule(newValue) {
            if (newValue === true) {
                this.handleView();
            }
        }
    }
}
</script>
