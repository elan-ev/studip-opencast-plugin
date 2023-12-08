<template>
    <div>
        <!--
        <MessageBox type="info" v-if="playlistForVideos">
            {{ $gettext('Bitte wählen Sie die Videos aus, die zur Wiedergabeliste hinzugefügt werden sollen.') }}
        </MessageBox>
        <h3 v-if="playlistForVideos">
            {{ playlistForVideos.title }}
            <div class="oc--tags oc--tags-playlist">
                <Tag v-for="tag in playlistForVideos.tags" v-bind:key="tag.id" :tag="tag.tag" />
            </div>
        </h3>
        -->

        <SearchBar v-if="!videoSortMode"
            :availableTags="videosTags"
            :availablePlaylists="playlists"
            :availableCourses="isCourse ? null : videosCourses"
            :activePlaylist="playlist"
            @search="doSearch"
       />

        <PaginationButtons v-if="!editable"
            :paging="paging"
            @changePage="changePage"
            @changeLimit="changeLimit"
        />

        <table id="episodes" class="default oc--episode-table--small">
            <colgroup>
                <col v-if="editable && videoSortMode" style="width: 20px">
                <col v-if="showCheckbox" style="width: 30px">
                <col style="width: 119px">
                <col>
                <col style="width: 180px" class="responsive-hidden">
                <col style="width: 150px" class="responsive-hidden">
                <col style="width: 18px">
                <col v-if="showActions && !videoSortMode" style="width: 64px">
            </colgroup>
            <thead>
                <tr class="sortable">
                    <th v-if="editable && videoSortMode" data-sort="false"></th>
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
                    <th></th>
                    <th v-if="showActions && !videoSortMode" class="actions" data-sort="false">{{ $gettext('Aktionen') }}</th>
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
                        :selectedVideos="selectedVideos"
                        @toggle="toggleVideo"
                        :selectable="selectable"
                        :playlistEditable="editable"
                        :showActions="showActions"
                        @doAction="doAction"
                        @redirectAction="redirectAction"
                    ></VideoRow>
                </template>
            </draggable>

            <tfoot v-if="editable || (isCourse && playlist)">
                <tr>
                    <td :colspan="numberOfColumns">
                        <span class="oc--bulk-actions">
                            <!--
                            <StudipButton v-if="playlistForVideos" icon="add" @click.stop="addVideosToPlaylist" :disabled="!hasCheckedVideos">
                                {{ $gettext('Zur Wiedergabeliste hinzufügen') }}
                            </StudipButton>
                            -->

                            <StudipButton v-if="editable" icon="remove" @click.prevent="removeVideosFromPlaylist" :disabled="!hasCheckedVideos">
                                {{ $gettext('Aus Wiedergabeliste entfernen') }}
                            </StudipButton>
                        </span>

                        <span v-if="(editable || !isCourse) && !trashBin">
                            <StudipButton icon="trash"
                                @click.prevent="doBulkAction('BulkVideoDelete')"
                                :class="{
                                    'disabled': selectedVideos.length == 0
                                }"
                                :disabled="selectedVideos.length == 0"
                            >
                                {{ $gettext('Zum Löschen markieren') }}
                            </StudipButton>
                        </span>

                        <span v-if="(editable || !isCourse) && trashBin">
                            <StudipButton icon="trash"
                                @click.prevent="doBulkAction('BulkVideoDeletePermanent')"
                                :class="{
                                    'disabled': selectedVideos.length == 0
                                }"
                                :disabled="selectedVideos.length == 0"
                            >
                                {{ $gettext('Unwiderruflich entfernen') }}
                            </StudipButton>

                            <StudipButton icon="trash"
                                @click.prevent="doBulkAction('BulkVideoRestore')"
                                :class="{
                                    'disabled': selectedVideos.length == 0
                                }"
                                :disabled="selectedVideos.length == 0"
                            >
                                {{ $gettext('Wiederherstellen') }}
                            </StudipButton>
                        </span>

                        <span v-if="isCourse && playlist">
                            <StudipButton class="wrap-button"
                                          v-if="playlist.is_default != '1'"
                                          @click.prevent="removePlaylistFromCourse(playlist.token, cid)"
                            >
                                <studip-icon shape="trash" role="clickable" />
                                {{ $gettext('Wiedergabeliste aus diesem Kurs entfernen') }}
                            </StudipButton>
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
import VideoLinkToPlaylists from '@/components/Videos/Actions/VideoLinkToPlaylists.vue';
import VideoAccess from '@/components/Videos/Actions/VideoAccess.vue';
import VideoDelete from '@/components/Videos/Actions/VideoDelete.vue';
import VideoDeletePermanent from '@/components/Videos/Actions/VideoDeletePermanent.vue';
import VideoDownload from '@/components/Videos/Actions/VideoDownload.vue';
import VideoReport from '@/components/Videos/Actions/VideoReport.vue';
import VideoEdit from '@/components/Videos/Actions/VideoEdit.vue';
import VideoRestore from '@/components/Videos/Actions/VideoRestore.vue';
import VideoRemoveFromPlaylist from '@/components/Videos/Actions/VideoRemoveFromPlaylist.vue';
import CaptionUpload from '@/components/Videos/Actions/CaptionUpload.vue';

import BulkVideoDelete from '@/components/Videos/BulkActions/VideoDelete.vue';
import BulkVideoDeletePermanent from '@/components/Videos/BulkActions/VideoDeletePermanent.vue';
import BulkVideoRestore from '@/components/Videos/BulkActions/VideoRestore.vue';

import Tag from '@/components/Tag.vue'

import draggable from 'vuedraggable'

export default {
    name: "VideosTable",

    components: {
        VideoRow,                 EmptyVideoRow,
        PaginationButtons,        MessageBox,
        SearchBar,                Tag,
        StudipButton,             VideoLinkToPlaylists,
        VideoAccess,              StudipIcon,
        VideoDownload,            VideoReport,
        VideoEdit,                VideoRestore,
        VideoDelete,              VideoDeletePermanent,
        VideoRemoveFromPlaylist,  CaptionUpload,
        BulkVideoDelete,          BulkVideoDeletePermanent,
        BulkVideoRestore,         draggable,
    },

    props: {
        'playlist': {
            type: Object,
            default: null
        },
        'cid': {
            type: String,
            default: null
        },
        'editable': {
            type: Boolean,
            default: false
        },
        'selectable': {
            type: Boolean,
            default: false
        },
        'showActions': {
            type: Boolean,
            default: true
        },
        'trashBin' : {
            type: Boolean,
            default: false
        }
    },

    emits: ['selectedVideosChange'],

    data() {
        return {
            loadedVideos: [],
            selectedVideos: [],
            videoSort: {
                field: 'created',
                order: 'desc',
            },
            limit: 15,
            paging: {
                currPage: 0,
                lastPage: 0,
                items: 0
            },
            sortedVideos: null,
            videos_loading: true,
            actionComponent: null,
            showActionDialog: false,
            selectedEvent: null,
            videosTags: [],
            videosCourses: [],
            filters: [],
            interval: null,
            interval_counter: 0
        }
    },

    computed: {
        ...mapGetters([
            'videos',
            'videosCount',
            'videosReload',
            'videoSortMode',
            'availableVideoTags',
            'availableVideoCourses',
            'axios_running',
            'courseVideosToCopy',
            'playlists',
            'course_config',
            'isLTIAuthenticated',
            'simple_config_list',
            'errors'
        ]),

        numberOfColumns() {
          return 7 - (this.showCheckbox ? 0 : 1) - (this.showActions ? 0 : 1);
        },

        showCheckbox() {
            return this.selectable || this.editable;
        },

        isCourse() {
            return this?.cid ? true : false;
        },

        selectAll() {
            return this.loadedVideos.length == this.selectedVideos.length;
        },

        hasCheckedVideos() {
            return this.selectedVideos.length > 0;
        },

        offset() {
          return this.paging.currPage * this.limit;
        },

        order() {
            return this.videoSort.field + '_' + this.videoSort.order;
        },

        videos_list: {
            get() {
                if (this.videoSortMode === true && this.sortedVideos) {
                    return this.sortedVideos;
                } else {
                    return this.loadedVideos;
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
            this.loadedVideos = [];
            this.videosTags = [];
            this.videosCourses = [];

            if (this.isCourse && this.playlist) {
                this.$store.dispatch('loadPlaylistVideos', {
                    ...this.filters,
                    offset: this.offset,
                    order: this.order,
                    cid: this.cid,
                    token: this.playlist.token,
                    limit: this.editable ? -1 : this.limit
                }).then(this.loadVideosFinished);
            } else if (this.editable && this.playlist) {
                this.$store.dispatch('loadPlaylistVideos', {
                    ...this.filters,
                    order: this.order,
                    token: this.playlist.token,
                    limit: -1,  // Show all videos in playlist edit view
                }).then(this.loadVideosFinished);
            } else {
                this.$store.dispatch('loadMyVideos', {
                    ...this.filters,
                    offset: this.offset,
                    order: this.order,
                    limit: this.limit,
                }).then(this.loadVideosFinished);
            }
        },

        loadVideosFinished() {
            this.loadedVideos = this.videos;
            this.videosTags = this.availableVideoTags;
            this.videosCourses = this.availableVideoCourses;
            this.updatePaging();
            this.videos_loading = false;
        },

        changeLimit(limit) {
            this.limit = limit;
        },

        changePage(page) {
            if (page >= 0 && page <= this.paging.lastPage) {
                this.paging.currPage = page;
            }
            this.loadVideos();
        },

        clearPaging() {
            this.paging = {
                currPage: 0,
                lastPage: 0,
                items: 0
            };
        },

        updatePaging() {
            this.paging.items = this.videosCount;
            this.paging.lastPage = (this.paging.items === this.limit) ? 0 : Math.floor((this.paging.items - 1) / this.limit);
        },

        updateSelectedVideos(selectedVideos) {
            this.selectedVideos = selectedVideos;
            this.$emit('selectedVideosChange', this.selectedVideos);
        },

        toggleVideo(data) {
            if (data.checked === false) {
                let index = this.selectedVideos.indexOf(data.event_id);
                if (index >= 0) {
                    this.updateSelectedVideos(this.selectedVideos.toSpliced(index, 1))

                }
            } else {
                this.updateSelectedVideos(this.selectedVideos.concat(data.event_id));
            }
        },

        toggleAll(e) {
            if (e.target.checked) {
                // select all videos on current page
                this.updateSelectedVideos(this.loadedVideos.map(v => v.token));
            } else {
                // deselect all videos on current page
                this.updateSelectedVideos([]);
            }
        },

        doSearch(filters) {
            this.filters = filters;
            this.changePage(0);
            this.loadVideos();
        },

        setDefaultSortOrder() {
            if (this.playlist?.sort_order) {
                let field, order;
                [field, order] = this.playlist.sort_order.split('_');
                this.videoSort = {
                    field: field,
                    order: order
                }
            }
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

            if (this.playlist && this.editable) {
                this.$store.dispatch('setPlaylistSort', {
                    token: this.playlist.token,
                    sort: videoSort
                });
            }

            this.videoSort = videoSort;
            this.changePage(0);
            this.loadVideos();
        },

        sortClasses(column) {
            let classes = [];
            if (this.videoSort.field === column) {
                classes.push(this.videoSort.order === 'asc' ? 'sortasc' : 'sortdesc');
            }
            return classes;
        },

        removeVideosFromPlaylist() {
            let view = this;

            this.$store.dispatch('removeVideosFromPlaylist', {
                playlist: this.playlist.token,
                videos:   this.selectedVideos
            }).then(() => {
                this.updateSelectedVideos([]);

                view.$store.dispatch('addMessage', {
                    type: 'success',
                    text: view.$gettext('Die Videos wurden von der Wiedergabeliste entfernt.')
                });

                this.loadVideos();
            })
        },

        /*
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
            this.$emit('addVideosDone');
        },
        */

        doAction(args) {
            if (Object.keys(this.$options.components).includes(args.actionComponent)) {
                this.actionComponent = args.actionComponent;
                this.selectedEvent = args.event;
                this.showActionDialog = true;
            }
        },

        doBulkAction(action)
        {
            if (Object.keys(this.$options.components).includes(action)) {
                this.actionComponent = action;
                this.selectedEvent = this.selectedVideos;
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
                this.selectedVideos = [];
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

        checkLTIPeriodically() {
            let view = this;

            this.$store.dispatch('simpleConfigListRead').then(() => {

                const error_msg = {
                    type: 'error',
                    text: this.$gettext('Es ist ein Verbindungsfehler zum Opencast Server aufgetreten. Bitte wenden Sie sich bei auftretenden Problemen an den Support oder versuchen Sie es zu einem späteren Zeitpunkt erneut.')
                };
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
                        view.$store.dispatch('removeMessage', error_msg);
                        clearInterval(view.interval);
                    } else {
                        view.$store.dispatch('addMessage', error_msg);
                    }

                    view.interval_counter++;
                    if (view.interval_counter > 10) {
                        clearInterval(view.interval);
                    }
                }, 2000);
            });
        }
    },

    created() {
        // Disable sort mode if active
        this.$store.dispatch('setVideoSortMode', false);
        this.$store.dispatch('setVideosReload', false);
    },

    async mounted() {
        this.clearPaging()
        this.$store.commit('setVideos', {});

        let loadVideos = this.playlist !== null;

        this.$store.dispatch('loadUserCourses');

        await this.$store.dispatch('authenticateLti').then(() => {
            if (this.isCourse || this.editable) {
                if (loadVideos) {
                    this.setDefaultSortOrder();
                    this.loadVideos();
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
                this.updateSelectedVideos([]);
            }
        },

        // Catch every playlist change to handle video loading
        playlist(playlist) {
            this.$store.dispatch('setVideoSortMode', false);
            if (playlist !== null) {
                this.clearPaging();
                this.setDefaultSortOrder();
                this.loadVideos();
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
                this.videoSort = {
                    field: 'order',
                    order: 'asc',
                };
                this.sortedVideos = this.loadedVideos;
            } else {
                if (newmode === 'commit') {
                    this.$store.dispatch('setPlaylistSort', {
                        token: this.playlist.token,
                        sort:  {
                            field: 'order',
                            order: 'asc',
                        }
                    });
                    // store the new sorting order
                    this.loadedVideos = this.sortedVideos;
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
                if (newmode !== false) {
                    this.$store.dispatch('setVideoSortMode', false);
                }
            }
        }
    },
};
</script>
