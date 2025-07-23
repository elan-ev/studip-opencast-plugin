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
                <template #search>
                    <!-- <ContentBarSearch /> -->
                    <ContextMenu :title="$gettext('Hinzufügen')" :items="actions" @select="handleSelect">
                        <template #button>
                            <StudipIcon shape="add" :size="20" />
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

            <VideoUpload
                v-if="activeDialog === 'videoUpload'"
                @done="done"
                @cancel="cancel"
                :currentUser="currentUser"
            />

            <VideosAddFromContents v-if="activeDialog === 'videoContents'" @done="done" @cancel="cancel" />

            <VideosAddFromCourses v-if="activeDialog === 'videoCourses'" @done="done" @cancel="cancel" />

            <PlaylistAddNewCard v-if="activeDialog === 'playlistNew'" @done="done" @cancel="cancel" />

            <PlaylistsCopyCard v-if="activeDialog === 'playlistCopy'" @done="done" @cancel="cancel" />
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
import ContentBarSearch from '@/components/ContentBarSearch.vue';
import ContextMenu from '@components/Layouts/ContextMenu.vue';
import StudipIcon from '@studip/StudipIcon.vue';

import VideoUpload from '@/components/Videos/VideoUpload';
import VideosAddFromContents from '@/components/Videos/VideosAddFromContents';
import VideosAddFromCourses from '@/components/Videos/VideosAddFromCourses';
import PlaylistAddNewCard from '@/components/Playlists/PlaylistAddNewCard';
import PlaylistsCopyCard from '@/components/Playlists/PlaylistsCopyCard';

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
        ContentBarSearch,
        StudipIcon,
        ContextMenu,

        VideoUpload,
        VideosAddFromContents,
        VideosAddFromCourses,
        PlaylistAddNewCard,
        PlaylistsCopyCard,
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
                        description: this.$gettext('Fügt ein Video aus Ihrer Sammlung hinzu'),
                        icon: 'content',
                    },
                    {
                        id: 'addVideoFromCourse',
                        label: this.$gettext('Meine Veranstaltungen'),
                        description: this.$gettext('Fügt ein Video aus einer anderen Veranstaltung hinzu'),
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
    },

    methods: {
        handleSelect(item) {
            console.log('Ausgewählte Aktion:', item.id);

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
