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
            <ContentBar>
                <template #title>
                    <StudipIcon shape="opencast" :size="24" role="info" />
                    <span>{{ $gettext('Opencast Videos') }}</span>
                </template>
                <template #nav>
                    <Tabs v-model="tabSelection">
                        <Tab :name="$gettext('Übersicht')"></Tab>
                        <Tab :name="$gettext('Videos')"></Tab>
                        <Tab :name="$gettext('Wiedergabelisten')"></Tab>
                        <Tab :name="$gettext('Aufzeichnungen planen')"></Tab>
                        <Tab :name="$gettext('Informationen')"></Tab>
                    </Tabs>
                </template>
                <template #actions>
                    <ContextMenu
                        v-if="canUpload || canEdit"
                        :title="$gettext('Hinzufügen')"
                        :items="actions"
                        @select="handleSelect"
                    >
                        <template #button>
                            <StudipIcon shape="add" :size="20" />
                        </template>
                    </ContextMenu>
                    <ContextMenu
                        v-if="canEdit"
                        :title="$gettext('Einstellungen')"
                        :items="settingsActions"
                        @select="handleSelect"
                        @toggle="handleToggle"
                    >
                        <template #button>
                            <StudipIcon shape="admin" :size="20" />
                        </template>
                    </ContextMenu>
                    <ContextMenu :title="$gettext('Suche')" :items="[]" @select="handleSelect">
                        <template #button>
                            <StudipIcon shape="search" :size="20" />
                        </template>
                    </ContextMenu>
                </template>
            </ContentBar>
            <div class="oc--course-videos-content">
                <VideosOverview v-if="tabSelection === 0" />
                <VideosAllInCourse v-if="tabSelection === 1" />
                <PlaylistsOverview v-if="tabSelection === 2" />
                <VideosTable
                    v-if="playlist && tabSelection === 4"
                    :playlist="playlist"
                    :cid="cid"
                    :canEdit="canEdit"
                    :canUpload="canUpload"
                />
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
                <EpisodesDefaultVisibilityDialog v-if="activeDialog === 'changeDefaultVisibility'" @done="done" @cancel="cancel" />
            </template>
        </template>
    </div>
</template>

<script>
import { mapGetters } from 'vuex';
import VideosTable from '@/components/Videos/VideosTable';
import VideosOverview from '@/components/Videos/VideosOverview';
import VideosAllInCourse from '@/components/Videos/VideosAllInCourse.vue';
import PlaylistsOverview from '@/components/Playlists/PlaylistsOverview.vue';
import MessageBox from '@/components/MessageBox.vue';
import ContentBar from '@components/Layouts/ContentBar.vue';
import Tab from '@components/Layouts/Tab.vue';
import Tabs from '@components/Layouts/Tabs.vue';
import ContextMenu from '@components/Layouts/ContextMenu.vue';
import StudipIcon from '@studip/StudipIcon.vue';

import VideoUpload from '@/components/Videos/VideoUpload';
import VideosAddFromContents from '@/components/Videos/VideosAddFromContents';
import VideosAddFromCourses from '@/components/Videos/VideosAddFromCourses';
import PlaylistAddNewCard from '@/components/Playlists/PlaylistAddNewCard';
import PlaylistsCopyCard from '@/components/Playlists/PlaylistsCopyCard';
import EpisodesDefaultVisibilityDialog from "@/components/Courses/EpisodesDefaultVisibilityDialog";

export default {
    name: 'CourseVideos',
    components: {
        VideosTable,
        VideosOverview,
        VideosAllInCourse,
        PlaylistsOverview,
        MessageBox,
        ContentBar,
        Tab,
        Tabs,
        StudipIcon,
        ContextMenu,

        VideoUpload,
        VideosAddFromContents,
        VideosAddFromCourses,
        PlaylistAddNewCard,
        PlaylistsCopyCard,
        EpisodesDefaultVisibilityDialog
    },
    data() {
        return {
            tabSelection: 0,
            activeDialog: null,
        };
    },

    computed: {
        ...mapGetters('opencast', ['cid', 'currentUser']),
        ...mapGetters('playlists', ['defaultPlaylist', 'playlist']),
        ...mapGetters('config', ['course_config', 'simple_config_list']),

        canEdit() {
            return this.course_config?.edit_allowed ?? false;
        },

        canUpload() {
            return this.course_config?.upload_allowed ?? false;
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

        actions() {
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
    },

    mounted() {
        this.$store.dispatch('playlists/loadPlaylists').then(() => {
            this.$store.dispatch('playlists/setPlaylist', this.defaultPlaylist);
        });
    },
    watch: {
        tabSelection(newVal) {
            if (newVal === 2) {
                this.$store.dispatch('playlists/setSelectedPlaylist', null);
            }
        },
    },
};
</script>
