<template>
    <div>
        <MessageBox type="info" v-if="playlistForVideos">
            {{ $gettext('Bitte wählen Sie die Videos aus, die zur Wiedergabeliste hinzugefügt werden sollen.') }}
        </MessageBox>
        <h3 v-if="playlistForVideos">
            {{ playlistForVideos.title }}
            <div class="oc--tags oc--tags-playlist">
                <Tag v-for="tag in playlistForVideos.tags" v-bind:key="tag.id" :tag="tag.tag" />
            </div>
        </h3>

        <SearchBar @search="doSearch" v-if="!videoSortMode"/>

        <PaginationButtons v-if="!playlistEdit" @changePage="changePage"/>

        <table id="episodes" class="default oc--episode-table--small">
            <colgroup>
                <col v-if="showCheckbox" style="width: 30px">
                <col style="width: 119px">
                <col>
                <col style="width: 180px" class="responsive-hidden">
                <col style="width: 150px" class="responsive-hidden">
                <col style="width: 64px">
            </colgroup>
            <thead>
                <tr class="sortable">
                    <th v-if="showCheckbox" data-sort="false">
                        <input
                            type="checkbox"
                            :checked="selectAll"
                            @click.stop="toggleAll"
                            :title="$gettext('Alle Videos auswählen')">
                    </th>
                    <th data-sort="false">{{ $gettext('Video') }}</th>
                    <th @click="setSort('title')" :class="sortClasses('title')">
                        <a href="#" @click.prevent>
                            {{ $gettext('Titel') }}
                        </a>
                    </th>
                    <th @click="setSort('created')" class="responsive-hidden" :class="sortClasses('created')">
                        <a href="#" @click.prevent>
                            {{ $gettext('Datum') }}
                        </a>
                    </th>
                    <th @click="setSort('author')" class="responsive-hidden" :class="sortClasses('author')">
                        <a href="#" @click.prevent>
                            {{ $gettext('Autor/-in') }}
                        </a>
                    </th>
                    <th class="actions" data-sort="false">{{ $gettext('Aktionen') }}</th>
                </tr>
            </thead>

            <tbody v-if="Object.keys(videos_list).length === 0 && (axios_running || videos_loading)" class="oc--episode-table--empty">
                <EmptyVideoRow :showCheckbox="showCheckbox" :numberOfColumns="numberOfColumns" />
                <EmptyVideoRow :showCheckbox="showCheckbox" :numberOfColumns="numberOfColumns" />
                <EmptyVideoRow :showCheckbox="showCheckbox" :numberOfColumns="numberOfColumns" />
                <EmptyVideoRow :showCheckbox="showCheckbox" :numberOfColumns="numberOfColumns" />
                <EmptyVideoRow :showCheckbox="showCheckbox" :numberOfColumns="numberOfColumns" />
            </tbody>
            <tbody v-else-if="Object.keys(videos_list).length === 0">
                <tr>
                    <td :colspan="numberOfColumns">
                        {{ $gettext('Es wurden keine Videos für die gewählten Ansichtsoptionen gefunden.') }}
                    </td>
                </tr>
            </tbody>
            <draggable v-else
                :disabled="!videoSortMode"
                v-model="videos_list"
                item-key="id"
                handle=".dragarea"
                tag="tbody"
                ghost-class="oc--ghost">
                <template #item="{element, index}">
                    <VideoRow
                        :event="element"
                        :numberOfColumns="numberOfColumns"
                        :canMoveUp="canMoveUp(index)"
                        :canMoveDown="canMoveDown(index)"
                        @moveUp="moveUpVideoRow"
                        @moveDown="moveDownVideoRow"
                        :playlistForVideos="playlistForVideos"
                        :selectedVideos="selectedVideos"
                        @toggle="toggleVideo"
                        :showCheckbox="showCheckbox"
                        :playlistMode="playlistEdit"
                        @doAction="doAction"
                        @redirectAction="redirectAction"
                    ></VideoRow>
                </template>
            </draggable>

            <tfoot v-if="playlistForVideos || playlistEdit || (isCourse && playlist && canEdit)">
                <tr>
                    <td :colspan="numberOfColumns">
                        <span class="oc--bulk-actions">
                            <StudipButton v-if="playlistForVideos" icon="add" @click.stop="addVideosToPlaylist" :disabled="!hasCheckedVideos">
                                {{ $gettext('Zur Wiedergabeliste hinzufügen') }}
                            </StudipButton>

                            <StudipButton v-if="playlistEdit" icon="trash" @click.prevent="removeVideosFromPlaylist" :disabled="!hasCheckedVideos">
                                {{ $gettext('Verknüpfungen aufheben') }}
                            </StudipButton>
                        </span>

                        <span v-if="isCourse && playlist && canEdit">
                            <StudipButton class="wrap-button"
                                          v-if="playlist.is_default != '1'"
                                          @click.prevent="removePlaylistFromCourse(playlist.token, cid)"
                            >
                                <studip-icon shape="trash" role="clickable" />
                                {{ $gettext('Wiedergabeliste aus diesem Kurs entfernen') }}
                            </StudipButton>

                            <a :href="getPlaylistLink(playlist.token)" class="button" target="_blank">
                                <studip-icon shape="edit" role="clickable" />
                                {{ $gettext('Wiedergabeliste bearbeiten') }}
                            </a>
                        </span>
                    </td>
                </tr>
            </tfoot>
        </table>

        <!--
        <div v-if="isCourse && Object.keys(videos).length > 0" class="oc--bulk-actions">
            <input type="checkbox" :checked="selectAll" @click.stop="toggleAll">
            <StudipButton icon="add" @click.stop="showCopyDialog">
                {{ $gettext('Verknüpfung mit anderen Kursen') }}
            </StudipButton>
        </div>
        -->

        <template v-if="showActionDialog">
            <component :is="actionComponent"
                @cancel="clearAction"
                @done="doAfterAction"
                :event="selectedEvent"
            >
            </component>
        </template>
    </div>
</template>

<script>
import { mapGetters } from "vuex";
import StudipButton from "@studip/StudipButton";
import StudipIcon from "@studip/StudipIcon";

import VideoRow from './VideoRow.vue';
import EmptyVideoRow from './EmptyVideoRow.vue';
import PaginationButtons from '@/components/PaginationButtons.vue';
import MessageBox from '@/components/MessageBox.vue';
import SearchBar from '@/components/SearchBar.vue';
import VideoAddToPlaylist from '@/components/Videos/Actions/VideoAddToPlaylist.vue';
import VideoAddToSeminar from '@/components/Videos/Actions/VideoAddToSeminar.vue';
import VideoAccess from '@/components/Videos/Actions/VideoAccess.vue';
import VideoDelete from '@/components/Videos/Actions/VideoDelete.vue';
import VideoDeletePermanent from '@/components/Videos/Actions/VideoDeletePermanent.vue';
import VideoDownload from '@/components/Videos/Actions/VideoDownload.vue';
import VideoReport from '@/components/Videos/Actions/VideoReport.vue';
import VideoEdit from '@/components/Videos/Actions/VideoEdit.vue';
import VideoRestore from '@/components/Videos/Actions/VideoRestore.vue';
import CaptionUpload from '@/components/Videos/Actions/CaptionUpload.vue';
import Tag from '@/components/Tag.vue'

import draggable from 'vuedraggable'

export default {
    name: "VideosTable",

    components: {
        VideoRow,               EmptyVideoRow,
        PaginationButtons,      MessageBox,
        SearchBar,              Tag,
        StudipButton,           VideoAddToPlaylist,
        VideoAccess,            StudipIcon,
        VideoDownload,          VideoReport,
        VideoEdit,              VideoRestore,
        VideoDelete,            VideoDeletePermanent,
        VideoAddToSeminar,      CaptionUpload,
        draggable,
    },

    props: {
        'playlistEdit': {
            type: Boolean,
            default: false
        }
    },

    data() {
        return {
            selectedVideos: [],
            sortedVideos: null,
            videos_loading: true,
            actionComponent: null,
            showActionDialog: false,
            selectedEvent: null,
            filters: [],
            interval: null,
            interval_counter: 0
        }
    },

    computed: {
        ...mapGetters([
            'videos',
            'videosReload',
            'videoSort',
            'videoSortMode',
            'paging',
            'axios_running',
            'playlistForVideos',
            'cid',
            'courseVideosToCopy',
            'playlists',
            'playlist',
            'course_config',
            'isLTIAuthenticated',
            'simple_config_list',
            'errors'
        ]),

        numberOfColumns() {
          return 6 - (this.showCheckbox ? 0 : 1);
        },

        showCheckbox() {
            return this.playlistForVideos || this.playlistEdit;
        },

        isCourse() {
            return this?.cid ? true : false;
        },

        selectAll() {
            return this.videos.length == this.selectedVideos.length;
        },

        canEdit() {
            if (!this.course_config) {
                return false;
            }

            return this.course_config.edit_allowed;
        },

        hasCheckedVideos() {
            return this.selectedVideos.length > 0;
        },

        videos_list: {
            get() {
                if (this.videoSortMode === true) {
                    return this.sortedVideos;
                } else {
                    return this.videos;
                }
            },

            set(new_video_list) {
                if (this.videoSortMode === true) {
                    this.sortedVideos = new_video_list;
                }
            }
        },
    },

    methods: {
        loadVideos() {
            this.videos_loading = true;
            this.$store.commit('setVideos', {});

            if (this.isCourse) {
                this.$store.dispatch('loadPlaylistVideos', {
                    ...this.filters,
                    cid: this.cid,
                    token: this.playlist.token
                }).then(() => { this.videos_loading = false });
            } else if (this.playlistEdit) {
                this.$store.dispatch('loadPlaylistVideos', {
                    ...this.filters,
                    token: this.playlist.token,
                    limit: -1,  // Show all videos in playlist edit view
                }).then(() => { this.videos_loading = false });
            } else {
                this.$store.dispatch('loadMyVideos', this.filters).then(() => { this.videos_loading = false });
            }
        },

        changePage: async function(page) {
            await this.$store.dispatch('setPage', page);
            this.loadVideos();
        },

        toggleVideo(data) {
            if (data.checked === false) {
                let index = this.selectedVideos.indexOf(data.event_id);
                if (index >= 0) {
                    this.selectedVideos.splice(index, 1);
                }
            } else {
                this.selectedVideos.push(data.event_id);
            }
        },

        toggleAll(e) {
            if (e.target.checked) {
                // select all videos on current page
                this.selectedVideos = [];

                for (let id in this.videos) {
                    this.selectedVideos.push(this.videos[id].token);
                }
            } else {
                // deselect all videos on current page
                this.selectedVideos = [];
            }
        },

        doSearch(filters) {
            this.filters = filters;
            this.$store.dispatch('setPage', 0);
            this.loadVideos();
        },

        setSort(column) {
            let videoSort = {
                field: column,
                order: 'asc'
            };

            if (this.videoSort.field === column) {
                if (this.playlist && this.videoSort.order === 'desc') {
                    // Custom order in playlists after descending order
                    videoSort.field = 'order';
                    videoSort.order = 'asc';
                } else {
                    videoSort.order = this.videoSort.order === 'desc' ? 'asc' : 'desc';
                }
            }

            // TODO: Wollen wir das haben? Besser custom immer als default?
            if (this.playlist && this.$route.name === 'playlist_edit') {
                this.$store.dispatch('setPlaylistSort', {
                    token: this.playlist.token,
                    sort: videoSort
                });
            }

            this.$store.dispatch('setVideoSort', videoSort);
            this.$store.dispatch('setPage', 0);
            this.loadVideos();
        },

        sortClasses(column) {
            let classes = [];
            if (this.videoSort.field === column) {
                classes.push(this.videoSort.order === 'asc' ? 'sortasc' : 'sortdesc');
            }
            return classes;
        },

        canMoveUp(index) {
            return this.videoSortMode && (index !== 0);
        },

        canMoveDown(index) {
            return this.videoSortMode && (index !== this.videos.length - 1);
        },

        moveUpVideoRow(token) {
            const index = this.sortedVideos.findIndex(video => {
                return video.token === token;
            });

            if (this.canMoveUp(index)) {
                let tmp = this.sortedVideos[index - 1];
                this.sortedVideos[index - 1] = this.sortedVideos[index];
                this.sortedVideos[index] = tmp;
            }
        },

        removeVideosFromPlaylist() {
            let view = this;

            this.$store.dispatch('removeVideosFromPlaylist', {
                playlist: this.playlist.token,
                videos:   this.selectedVideos
            }).then(() => {
                this.selectedVideos = [];
                view.$store.dispatch('addMessage', {
                    type: 'success',
                    text: view.$gettext('Die Videos wurden von der Wiedergabeliste entfernt.')
                });

                this.loadVideos();
            })
        },

        moveDownVideoRow(token) {
            const index = this.sortedVideos.findIndex(video => {
                return video.token === token;
            });

            if (this.canMoveDown(index)) {
                let tmp = this.sortedVideos[index + 1];
                this.sortedVideos[index + 1] = this.sortedVideos[index];
                this.sortedVideos[index] = tmp;
            }
        },

        addVideosToPlaylist() {
            let view = this;

            this.$store.dispatch('addVideosToPlaylist', {
                playlist: this.playlistForVideos.token,
                videos:   this.selectedVideos
            }).then(() => {
                this.selectedVideos = [];
                view.$store.dispatch('addMessage', {
                    type: 'success',
                    text: view.$gettext('Die Videos wurden der Wiedergabeliste hinzugefügt.')
                });
            })
        },

        doAction(args) {
            if (Object.keys(this.$options.components).includes(args.actionComponent)) {
                this.actionComponent = args.actionComponent;
                this.selectedEvent = args.event;
                this.showActionDialog = true;
            }
        },

        redirectAction(action) {
            let redirectUrl = window.OpencastPlugin.REDIRECT_URL;

            if (redirectUrl) {
                redirectUrl = redirectUrl + action;
                window.open(redirectUrl, '_blank');
            }
        },

        async doAfterAction(args) {
            this.clearAction();
            if (args == 'refresh') {
                this.loadVideos();
            }
        },

        clearAction() {
            this.showActionDialog = false;
            this.actionComponent = null;
            this.selectedEvent = null;
        },

        removePlaylistFromCourse(token, cid) {
            if (confirm(this.$gettext('Sind sie sicher, dass sie diese Wiedergabeliste aus dem Kurs entfernen möchten?'))) {
                this.$store.dispatch('setPlaylist', null);
                this.$store.dispatch('removePlaylistFromCourse', {
                    token: token,
                    course: cid
                }).then(() => {
                    this.$store.dispatch('loadPlaylists');
                });
            }
        },

        showCopyDialog() {
            this.$store.dispatch('clearMessages');
            if (this.selectedVideos.length > 0) {
                this.$store.dispatch('toggleCourseCopyDialog', true);
                this.$store.dispatch('setCourseVideosToCopy', this.selectedVideos);
            } else {
                this.$store.dispatch('addMessage', {
                    type: 'warning',
                    text: this.$gettext('Es wurden keine Videos ausgewählt!')
                });
            }
        },

        getPlaylistLink(token) {
            return window.STUDIP.URLHelper.getURL('plugins.php/opencast/contents/index#/contents/playlists/' + token + '/edit', {}, ['cid'])
        },

        checkLTIPeriodically() {
            let view = this;

            this.$store.dispatch('simpleConfigListRead').then(() => {

                const error_msg = this.$gettext('Es ist ein Verbindungsfehler zum Opencast Server aufgetreten. Einige Aktionen könnten nicht richtig funktionieren.');
                const server_ids = Object.keys(view.simple_config_list['server']);

                // periodically check, if lti is authenticated
                view.interval = setInterval(async () => {
                    // Create an array of promises for checking each server in parallel
                    const promises = server_ids.map(async (id) => {
                        await view.$store.dispatch('checkLTIAuthentication', view.simple_config_list['server'][id]);
                        // Remove server from list, if authenticated
                        if (view.isLTIAuthenticated[id]) {
                            server_ids.splice(server_ids.indexOf(id), 1);
                        }
                    });
                    // Wait for all checks to finish
                    await Promise.all(promises);

                    if (server_ids.length === 0) {
                        view.$store.dispatch('errorRemove', error_msg);
                        clearInterval(view.interval);
                    } else {
                        if (!view.errors.find((e) => e === error_msg)) {
                            view.$store.dispatch('errorCommit', error_msg);
                        }
                    }

                    view.interval_counter++;
                    if (view.interval_counter > 10) {
                        clearInterval(view.interval);
                    }
                }, 2000);
            });
        }
    },

    async mounted() {
        this.$store.commit('clearPaging');
        this.$store.commit('setVideos', {});

        let loadVideos = this.playlist !== null;

        this.$store.dispatch('loadUserCourses');

        await this.$store.dispatch('authenticateLti').then(() => {
            if (this.isCourse || this.playlistEdit) {
                if (loadVideos) {
                    this.$store.dispatch('setDefaultSortOrder', this.playlist).then(() => {
                        this.loadVideos();
                    })
                }
            }
            else {
                if (this.$route.name === 'videosTrashed') {
                    this.filters.trashed = true;
                }
                this.loadVideos();
            }
        })

        this.checkLTIPeriodically();
    },

    watch: {
        courseVideosToCopy(newValue, oldValue) {
            if (this.isCourse && oldValue?.length > 0 && newValue?.length == 0) {
                this.selectedVideos = [];
            }
        },

        // Catch every playlist change to handle video loading
        playlist(playlist) {
            if (this.isCourse && playlist !== null) {
                this.$store.commit('clearPaging');
                this.$store.dispatch('setDefaultSortOrder', this.playlist).then(() => {
                    this.loadVideos();
                });
            }
        },

        // Handle reloading Videos from outside of this component (e.g. used after VideoUpload)
        videosReload(reload) {
            if (reload) {
                this.loadVideos();
                this.$store.dispatch('setVideosReload', false);
            }
        },

        videoSortMode(newmode) {
            if (newmode === true) {
                this.$store.dispatch('setVideoSort', {
                    field: 'order',
                    order: 'asc',
                    text : 'Benutzerdefiniert'
                });
                this.sortedVideos = this.videos;
            } else {
                if (newmode === 'commit') {
                    this.$store.dispatch('setPlaylistSort', {
                        token: this.playlist.token,
                        sort:  {
                            field: 'order',
                            order: 'asc',
                            text : 'Benutzerdefiniert'
                        }
                    });
                    // store the new sorting order
                    this.$store.commit('setVideos', this.sortedVideos);

                    this.$store.dispatch('uploadSortPositions', {
                        playlist_token: this.playlist.token,
                        sortedVideos  : this.sortedVideos.map((elem) => elem.token)
                    });
                }
                else if (newmode === 'cancel') {
                    // Reload videos
                    this.loadVideos();
                }
                this.$store.dispatch('setVideoSortMode', false);
            }
        }
    },
};
</script>
