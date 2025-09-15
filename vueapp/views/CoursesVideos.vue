<template>
    <div>
        <MessageBox type="info" v-if="!hasDefaultPlaylist">
            {{
                $gettext(
                    'Für diesen Kurs existiert noch keine Standard-Kurswiedergabeliste. Bitte erstellen Sie diese über das Aktionsmenü.'
                )
            }}
        </MessageBox>
        <template v-else>
            <!--- Hack for creepy responsive contentbar solution --->
            <div v-show="false" class="sidebar-image">
                <div class="sidebar-title">{{ $gettext('Opencast Videos') }}</div>
            </div>
            <!--- end hack --->
            <ContentBar>
                <template #title>
                    <StudipIcon shape="opencast" :size="24" role="info" />
                    <span>{{ $gettext('Opencast Videos') }}</span>
                </template>
                <template #nav>
                    <Tabs v-model="tabSelection" :responsive="true">
                        <Tab :name="$gettext('Übersicht')"></Tab>
                        <Tab :name="$gettext('Videos')"></Tab>
                        <Tab :name="$gettext('Wiedergabelisten')"></Tab>
                        <Tab v-if="canSchedule" :name="$gettext('Aufzeichnungen planen')"></Tab>
                    </Tabs>
                </template>
                <template #actions>
                    <DropdownActions
                        v-if="(canUpload || canEdit) && addButtonAvailable && !showScheduleOverview"
                        :title="$gettext('Hinzufügen')"
                        :items="addActions"
                        @select="handleSelect"
                    >
                        <template #button>
                            <StudipIcon shape="add" :size="20" />
                        </template>
                    </DropdownActions>
                    <template v-if="showScheduleOverview">
                        <DropdownSelect
                            :title="$gettext('Aufzeichnungsoptionen')"
                            :items="scheduleOptionsItems"
                            @select="handleScheduleOptions"
                        >
                            <template #button>
                                <StudipIcon shape="video2" :size="20" />
                            </template>
                        </DropdownSelect>
                        <DropdownSelect
                            :title="$gettext('Filter')"
                            :items="scheduleFilterItems"
                            @select="handleScheduleFilter"
                        >
                            <template #button>
                                <StudipIcon shape="filter" :size="20" />
                            </template>
                        </DropdownSelect>
                    </template>
                    <DropdownActions
                        v-if="canEdit"
                        :title="$gettext('Einstellungen')"
                        :items="settingsActions"
                        @select="handleSelect"
                        @toggle="handleToggle"
                    >
                        <template #button>
                            <StudipIcon shape="admin" :size="20" />
                        </template>
                    </DropdownActions>
                    <DropdownSearch
                        v-if="searchAvailable"
                        :title="$gettext('Suchen und Filtern')"
                        :tags="availableTags"
                        @search="handleSearch"
                        @filter="handleFilter"
                    >
                        <template #button>
                            <StudipIcon shape="search" :size="20" />
                        </template>
                    </DropdownSearch>
                </template>
            </ContentBar>
            <div class="oc--course-videos-content">
                <VideosOverview v-if="tabSelection === 0" />
                <VideosAllInCourse v-if="tabSelection === 1" />
                <PlaylistsOverview v-if="tabSelection === 2" />
                <ScheduleOverview v-if="showScheduleOverview" />
            </div>

            <template v-if="canUpload || canEdit">
                <VideoUpload
                    v-if="activeDialog === 'videoUpload'"
                    :currentUser="currentUser"
                    @done="done"
                    @cancel="cancel"
                />
                <VideosAddFromContents v-if="activeDialog === 'videoContents'" @done="done" @cancel="cancel" />
                <VideosAddFromCourses v-if="activeDialog === 'videoCourses'" @done="done" @cancel="cancel" />
                <PlaylistAddNewCard v-if="activeDialog === 'playlistNew'" @done="done" @cancel="cancel" />
                <PlaylistsCopyCard v-if="activeDialog === 'playlistCopy'" @done="done" @cancel="cancel" />
            </template>

            <template v-if="canEdit">
                <EpisodesDefaultVisibilityDialog
                    v-if="activeDialog === 'changeDefaultVisibility'"
                    @done="done"
                    @cancel="cancel"
                />
            </template>
        </template>
    </div>
</template>

<script>
import { mapGetters } from 'vuex';
import VideosOverview from '@/components/Videos/VideosOverview';
import VideosAllInCourse from '@/components/Videos/VideosAllInCourse.vue';
import PlaylistsOverview from '@/components/Playlists/PlaylistsOverview.vue';
import MessageBox from '@/components/MessageBox.vue';
import ContentBar from '@components/Layouts/ContentBar.vue';
import Tab from '@components/Layouts/Tab.vue';
import Tabs from '@components/Layouts/Tabs.vue';
import DropdownActions from '@components/Layouts/DropdownActions.vue';
import DropdownSearch from '@components/Layouts/DropdownSearch.vue';
import DropdownSelect from '@components/Layouts/DropdownSelect.vue';
import StudipIcon from '@studip/StudipIcon.vue';

import VideoUpload from '@/components/Videos/VideoUpload';
import VideosAddFromContents from '@/components/Videos/VideosAddFromContents';
import VideosAddFromCourses from '@/components/Videos/VideosAddFromCourses';
import PlaylistAddNewCard from '@/components/Playlists/PlaylistAddNewCard';
import PlaylistsCopyCard from '@/components/Playlists/PlaylistsCopyCard';
import EpisodesDefaultVisibilityDialog from '@/components/Courses/EpisodesDefaultVisibilityDialog';

import ScheduleOverview from '@components/Schedule/ScheduleOverview.vue';
export default {
    name: 'CourseVideos',
    components: {
        VideosOverview,
        VideosAllInCourse,
        PlaylistsOverview,
        MessageBox,
        ContentBar,
        Tab,
        Tabs,
        StudipIcon,
        DropdownSearch,
        DropdownActions,
        DropdownSelect,

        VideoUpload,
        VideosAddFromContents,
        VideosAddFromCourses,
        PlaylistAddNewCard,
        PlaylistsCopyCard,
        EpisodesDefaultVisibilityDialog,

        ScheduleOverview,
    },
    data() {
        return {
            tabSelection: 0,
            addButtonAvailable: true,
            activeDialog: null,
            selectedTags: [],
        };
    },

    computed: {
        ...mapGetters('opencast', ['cid', 'currentUser']),
        ...mapGetters('playlists', [
            'availableTags',
            'defaultPlaylist',
            'playlist',
            'playlists',
            'schedule_playlist',
            'livestream_playlist',
        ]),
        ...mapGetters('config', ['course_config', 'simple_config_list', 'canSchedule']),
        ...mapGetters('videos', ['searchAvailable']),
        ...mapGetters('schedule', ['semester_list', 'semester_filter']),

        canEdit() {
            return this.course_config?.edit_allowed ?? false;
        },

        canUpload() {
            return this.course_config?.upload_allowed ?? false;
        },

        showScheduleOverview() {
            return this.tabSelection === 3;
        },

        hasDefaultPlaylist() {
            // if the course config is not available, assume it has a default playlist
            if (!this.course_config) {
                return true;
            }

            return this.course_config?.has_default_playlist;
        },

        canShowStudio() {
            try {
                return (
                    this.cid !== undefined &&
                    this.currentUser.can_edit &&
                    this.simple_config_list['settings']['OPENCAST_ALLOW_STUDIO'] &&
                    this.hasDefaultPlaylist
                );
            } catch (error) {
                return false;
            }
        },

        uploadEnabled() {
            if (!this.course_config) {
                return false;
            }

            return this.course_config.upload_enabled == 1;
        },

        recordingLink() {
            if (!this.simple_config_list.settings || !this.course_config || !this.canShowStudio) {
                return '';
            }

            let config_id = this.simple_config_list.settings['OPENCAST_DEFAULT_SERVER'];
            let server = this.simple_config_list.server[config_id];

            // use the first avai
            return window.STUDIP.URLHelper.getURL(server.studio, {
                'upload.seriesId': this.course_config['series']['series_id'],
                'upload.acl': true,
                'upload.workflowId': this.getWorkflow(config_id),
                'return.target': window.STUDIP.URLHelper.getURL('plugins.php/opencastv3/course?cid=' + this.cid),
                'return.label': 'Stud.IP',
            });
        },
        scheduleOptionsItems() {
            const options = this.playlists?.map((playlist) => {
                return { value: playlist.token, label: playlist.title };
            });
            return [
                {
                    label: this.$gettext('Zielwiedergabeliste für Aufzeichnungen'),
                    options: options,
                    selected: this.schedule_playlist?.token,
                    emit: 'scheduled',
                },
                {
                    label: this.$gettext('Zielwiedergabeliste für Livestreams'),
                    options: options,
                    selected: this.livestream_playlist?.token,
                    emit: 'livestreams',
                },
            ];
        },
        addActions() {
            const menuItems = [];

            if (this.canUpload) {
                const uploadItems = [
                    {
                        id: 'addVideoFromSystem',
                        label: this.$gettext('Mein Computer'),
                        description: this.$gettext('Fügt eine Video-Datei vom Computer hinzu'),
                        icon: 'computer',
                    },
                    {
                        id: 'addVideoFromContents',
                        label: this.$gettext('Arbeitsplatz'),
                        description: this.$gettext('Fügt Videos aus Ihrer Sammlung hinzu'),
                        icon: 'content',
                    },
                    {
                        id: 'addVideoFromCourse',
                        label: this.$gettext('Meine Veranstaltungen'),
                        description: this.$gettext('Fügt Videos aus einer anderen Veranstaltung hinzu'),
                        icon: 'seminar',
                    },
                ];

                if (this.canShowStudio) {
                    uploadItems.push({
                        id: 'recordVideoLink',
                        label: this.$gettext('Aufnahme'),
                        description: this.$gettext('Öffnet Opencast Studio um ein Video aufzuzeichnen'),
                        icon: 'opencast',
                        type: 'link',
                        newTab: true,
                        url: this.recordingLink,
                    });
                }

                menuItems.push({
                    name: this.$gettext('Video'),
                    items: uploadItems,
                });
            }
            if (this.canEdit) {
                menuItems.push({
                    name: this.$gettext('Wiedergabeliste'),
                    items: [
                        {
                            id: 'addPlaylist',
                            label: this.$gettext('Neu erstellen'),
                            description: this.$gettext('Erstellt eine neue Wiedergabeliste für diese Veranstaltung'),
                            icon: 'add',
                        },
                        {
                            id: 'copyPlaylist',
                            label: this.$gettext('Bestehende kopieren'),
                            description: this.$gettext('Kopiert eine Wiedergabeliste in diese Veranstaltung'),
                            icon: 'copy',
                        },
                    ],
                });
            }
            return menuItems;
        },
        settingsActions() {
            const menuItems = [];
            menuItems.push({
                items: [
                    {
                        id: 'uploadEnabled',
                        label: this.$gettext('Hochladen durch Studierende'),
                        description: this.$gettext('Legt fest, ob Studierende Videos hochladen dürfen'),
                        icon: 'upload',
                        type: 'toggle',
                        value: this.uploadEnabled,
                        emit: 'upload-enabled',
                    },
                    {
                        id: 'changeDefaultVisibility',
                        label: this.$gettext('Standard für Sichtbarkeit'),
                        description: this.$gettext(
                            'Standardwert für die Sichtbarkeit von Videos in dieser Veranstaltung'
                        ),
                        icon: 'visibility-visible',
                    },
                ],
            });
            return menuItems;
        },

        scheduleFilterItems() {
            const options = this.semester_list.map((semester) => {
                return { value: semester.id, label: semester.name };
            });
            return [
                {
                    label: this.$gettext('Semesterfilter'),
                    options: options,
                    selected: this.semester_filter,
                    emit: 'semester',
                },
            ];
        },
    },

    methods: {
        handleSelect(item) {
            switch (item.id) {
                case 'addVideoFromSystem':
                    this.activeDialog = 'videoUpload';
                    break;
                case 'addVideoFromContents':
                    this.activeDialog = 'videoContents';
                    break;
                case 'addVideoFromCourse':
                    this.activeDialog = 'videoCourses';
                    break;
                case 'addPlaylist':
                    this.activeDialog = 'playlistNew';
                    break;
                case 'copyPlaylist':
                    this.activeDialog = 'playlistCopy';
                    break;
                case 'changeDefaultVisibility':
                    this.activeDialog = 'changeDefaultVisibility';
                    break;
            }
        },
        handleToggle(event) {
            const item = event.item;
            const value = event.value;

            switch (item.id) {
                case 'uploadEnabled':
                    this.setUpload(value);
                    break;
            }
        },
        done() {
            this.activeDialog = null;
            this.$emit('done');
        },

        cancel() {
            this.activeDialog = null;
            this.$emit('cancel');
        },
        async setUpload(upload) {
            const uploadInt = +upload;
            await this.$store.dispatch('opencast/setUpload', { cid: this.cid, upload: uploadInt });
            this.$store.dispatch('config/loadCourseConfig', this.cid);
        },
        getWorkflow(config_id) {
            let wf_id = this.simple_config_list?.workflow_configs.find(
                (wf_config) => wf_config['config_id'] == config_id && wf_config['used_for'] === 'studio'
            )['workflow_id'];
            return this.simple_config_list?.workflows.find((wf) => wf['id'] == wf_id)['name'];
        },

        handleSearch(e) {
            console.log(e);
        },
        handleFilter(e) {
            console.log(e);
        },
        handleScheduleOptions(e) {
            if (!this.canSchedule) {
                return;
            }
            if (e.emit == 'scheduled') {
                this.$store.dispatch('playlists/setSchedulePlaylist', e.value);
            }
            if (e.emit == 'livestreams') {
                this.$store.dispatch('playlists/setLivestreamPlaylist', e.value);
            }
        },
        async handleScheduleFilter(e) {
            if (e.emit === 'semester') {
                console.log(e.value);
                await this.$store.dispatch('schedule/setSemesterFilter', e.value);
                this.$store.dispatch('schedule/getScheduleList');
            }
        },
    },

    mounted() {
        this.$store.dispatch('playlists/loadPlaylists').then(() => {
            this.$store.dispatch('playlists/setPlaylist', this.defaultPlaylist);
        });
        this.$store.dispatch('playlists/updateAvailableTags');
    },
    watch: {
        tabSelection(newVal) {
            if (newVal === 1) {
                this.$store.dispatch('videos/setSearchAvailable', true);
            } else {
                this.$store.dispatch('videos/setSearchAvailable', false);
            }

            if (newVal === 2) {
                this.$store.dispatch('playlists/setSelectedPlaylist', null);
            }

            if (newVal === 3) {
                this.$store.dispatch('schedule/getScheduleList');
                this.addButtonAvailable = false;
            } else {
                this.addButtonAvailable = true;
            }
        },
    },
};
</script>
