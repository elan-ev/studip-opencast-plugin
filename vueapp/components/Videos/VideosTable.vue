<template>
    <StudipProgressIndicator
        v-if="Object.keys(videos_list).length === 0 && videos_loading"    
        class="oc--loading-indicator"
        :description="$gettext('Lade Videos...')"
        :size="64"
    />
    <div v-else>
        <!-- <SearchBar v-if="!videoSortMode"
            :availableTags="videosTags"
            :availablePlaylists="playlists"
            :availableCourses="isCourse ? null : videosCourses"
            :activePlaylist="playlist"
            @search="doSearch"
       /> -->

        <PaginationButtons v-if="!nolimit"
            :paging="paging"
            @changePage="changePage"
            @changeLimit="changeLimit"
        />

        <MessageBox type="info" v-if="noReadPerms">
            {{ $gettext('Beachten Sie bitte, dass Sie hier keine Videos einbinden können, an denen Sie nur Leserechte besitzen.') }}
        </MessageBox>

        <table id="episodes" class="default oc--episode-table--small">
            <colgroup>
                <col v-if="canEdit && videoSortMode" style="width: 20px">
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
                    <th v-if="canEdit && videoSortMode" data-sort="false"></th>
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
                    <th @click="setSort('presenters')" class="responsive-hidden" :class="sortClasses('presenters')">
                        <a href="#" @click.prevent>
                            {{ $gettext('Vortragende(r)') }}
                        </a>
                    </th>
                    <th></th>
                    <th v-if="showActions && !videoSortMode" class="actions" data-sort="false">{{ $gettext('Aktionen') }}</th>
                </tr>
            </thead>
            <tbody v-if="Object.keys(videos_list).length === 0">
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
                        :selectable="selectable || isContents"
                        :isCourse="isCourse"
                        :canUpload="canUpload"
                        :showActions="showActions"
                        @doAction="doAction"
                        @redirectAction="redirectAction"
                        :userHasSelectableVideos="userHasSelectableVideos"
                    ></VideoRow>
                </template>
            </draggable>

            <tfoot v-if="canEdit || (isCourse && playlist) || isContents">
                <tr>
                    <td :colspan="numberOfColumns">
                        <span class="oc--bulk-actions">
                            <StudipButton v-if="canUpload && userHasSelectableVideos" icon="remove" @click.prevent="removeVideosFromPlaylist" :disabled="!hasCheckedVideos">
                                {{ $gettext('Aus Wiedergabeliste entfernen') }}
                            </StudipButton>
                        </span>

                        <span v-if="(canEdit || isContents) && !isCourse && !trashBin">
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

                        <span v-if="(canEdit || !isCourse) && trashBin">
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

                        <span v-if="isCourse && playlist && canEdit">
                            <StudipButton class="wrap-button"
                                          v-if="playlist.is_default != true"
                                          @click.prevent="removePlaylist(playlist.token, cid)"
                            >
                                <studip-icon shape="trash" role="clickable" />
                                {{ $gettext('Wiedergabeliste entfernen') }}
                            </StudipButton>
                        </span>
                    </td>
                </tr>
            </tfoot>
        </table>
        <template v-if="showActionDialog">
            <component :is="actionComponent"
                @cancel="clearAction"
                @done="doAfterAction"
                @doAction="doAction"
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
import StudipProgressIndicator from "@studip/StudipProgressIndicator.vue";

import VideoRow from './VideoRow.vue';
import PaginationButtons from '@/components/PaginationButtons.vue';
import MessageBox from '@/components/MessageBox.vue';
import SearchBar from '@/components/SearchBar.vue';
import VideoDelete from '@/components/Videos/Actions/VideoDelete.vue';
import VideoDeletePermanent from '@/components/Videos/Actions/VideoDeletePermanent.vue';
import VideoDownloadDialog from '@/components/Videos/Actions/VideoDownloadDialog.vue';
import VideoEdit from '@/components/Videos/Actions/VideoEdit.vue';
import VideoCut from '@/components/Videos/Actions/VideoCut.vue';
import VideoRestore from '@/components/Videos/Actions/VideoRestore.vue';
import VideoRemoveFromPlaylist from '@/components/Videos/Actions/VideoRemoveFromPlaylist.vue';
import VideoEmbeddingCodeDialog from '@/components/Videos/Actions/VideoEmbeddingCodeDialog.vue';

import BulkVideoDelete from '@/components/Videos/BulkActions/VideoDelete.vue';
import BulkVideoDeletePermanent from '@/components/Videos/BulkActions/VideoDeletePermanent.vue';
import BulkVideoRestore from '@/components/Videos/BulkActions/VideoRestore.vue';

import Tag from '@/components/Tag.vue'

import draggable from 'vuedraggable'

export default {
    name: "VideosTable",

    components: {
        VideoRow,                 StudipProgressIndicator,
        PaginationButtons,        MessageBox,
        SearchBar,                Tag,
        StudipButton,
        StudipIcon,
        VideoDownloadDialog,      
        VideoEdit,                VideoCut,
        VideoRestore,             VideoDelete,
        VideoDeletePermanent,     VideoRemoveFromPlaylist,
        VideoEmbeddingCodeDialog,       BulkVideoDelete,
        BulkVideoDeletePermanent, BulkVideoRestore,
        draggable,
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
        'canEdit': {
            type: Boolean,
            default: false
        },
        'canUpload': {
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
        },
        'nolimit': {
            type: Boolean,
            default: false
        },
        noReadPerms: {
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
            filters: []
        }
    },

    computed: {
        ...mapGetters('videos', ['videos', 'videosCount', 'videosReload', 'videoSortMode', 'availableVideoTags', 'availableVideoCourses', 'courseVideosToCopy' ]),
        ...mapGetters('opencast', ['axios_running', 'isLTIAuthenticated']),
        ...mapGetters('playlists', ['playlists']),
        ...mapGetters('config', ['course_config', 'simple_config_list']),

        numberOfColumns() {
            return 7 - (this.showCheckbox ? 0 : 1) - (this.showActions ? 0 : 1);
        },

        showCheckbox() {
            return (this.selectable || this.canEdit || this.canUpload || this.isContents) && this.userHasSelectableVideos;
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

        userHasSelectableVideos() {
            let has_videos_with_write_perm = false;
            if (this.loadedVideos.length > 0) {
                has_videos_with_write_perm = this.loadedVideos.filter(v => v.perm == 'write' || v.perm == 'owner').length > 0;
            }
            return has_videos_with_write_perm;
        },

        serversCheckSuccessful() {
            return Object.values(this.isLTIAuthenticated).every(server => server);
        },

        fragment() {
            return this.$route.name;
        },

        isContents() {
            return this.fragment === 'videos';
        }

    },

    methods: {
        loadVideos() {
            this.videos_loading = true;
            this.$store.commit('videos/setVideos', {});
            this.loadedVideos = [];
            this.videosTags = [];
            this.videosCourses = [];

            if (this.isCourse && this.playlist) {
                this.$store.dispatch('videos/loadPlaylistVideos', {
                    ...this.filters,
                    offset: this.offset,
                    order: this.order,
                    cid: this.cid,
                    token: this.playlist.token,
                    limit: this.nolimit ? -1 : this.limit
                }).then(this.loadVideosFinished);
            } else if(this.isCourse && !this.playlist) {
                this.$store.dispatch('videos/loadCourseVideos', {
                    ...this.filters,
                    offset: this.offset,
                    order: this.order,
                    cid: this.cid,
                    limit: this.limit,
                }).then(this.loadVideosFinished);
            } else if (this.canEdit && this.playlist) {
                this.$store.dispatch('videos/loadPlaylistVideos', {
                    ...this.filters,
                    order: this.order,
                    token: this.playlist.token,
                    limit: -1,  // Show all videos in playlist edit view
                }).then(this.loadVideosFinished);
            } else {
                this.$store.dispatch('videos/loadMyVideos', {
                    ...this.filters,
                    offset: this.offset,
                    order: this.order,
                    limit: this.limit,
                }).then(this.loadVideosFinished);
            }
        },

        loadVideosFinished() {
            this.loadedVideos = this.filterVideos(this.videos);
            this.videosTags = this.availableVideoTags;
            this.videosCourses = this.availableVideoCourses;
            this.updatePaging();
            this.videos_loading = false;
        },

        filterVideos(videos) {
            return videos.filter(v => !this.noReadPerms || v.perm != 'read');
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
            if (this.$route.name === 'videosTrashed') {
                this.filters.trashed = true;
            }
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

            if (this.playlist && this.canEdit) {
                this.$store.dispatch('playlists/setPlaylistSort', {
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

            if (this.selectedVideos.find(video => video?.livestream)) {
                view.$store.dispatch('messages/addMessage', {
                    type: 'error',
                    text: view.$gettext('Livestream-Videos können nicht entfernt werden.')
                });
                return;
            }

            this.$store.dispatch('playlists/removeVideosFromPlaylist', {
                playlist:  this.playlist.token,
                videos:    this.selectedVideos,
                course_id: this.cid
            }).then(() => {
                this.updateSelectedVideos([]);

                view.$store.dispatch('messages/addMessage', {
                    type: 'success',
                    text: view.$gettext('Die Videos wurden von der Wiedergabeliste entfernt.')
                });

                this.loadVideos();
            }).catch(() => {
                view.$store.dispatch('messages/addMessage', {
                    type: 'error',
                    text: view.$gettext('Die Videos konnten von der Wiedergabeliste nicht entfernt werden.')
                });
            });
        },

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
            let redirectUrl = window.OpencastPlugin.REDIRECT_URL + '/perform';

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

        removePlaylist(token, cid) {
            if (confirm(this.$gettext('Sind Sie sicher, dass Sie diese Wiedergabeliste entfernen möchten?'))) {
                this.$store.dispatch('playlists/setPlaylist', null);
                this.$store.dispatch('playlists/removePlaylistFromCourse', {
                    token: token,
                    course: cid
                }).then(() => {
                    this.$store.dispatch('playlists/loadPlaylists');
                });
            }
        },
    },

    created() {
        // Disable sort mode if active
        this.$store.dispatch('videos/setVideoSortMode', false);
        this.$store.dispatch('videos/setVideosReload', false);
    },

    async mounted() {
        this.clearPaging()
        this.$store.commit('videos/setVideos', {});

        this.$store.dispatch('opencast/loadUserCourses');

        await this.$store.dispatch('opencast/authenticateLti').then(() => {
            if (this.isCourse || this.canEdit) {
                this.setDefaultSortOrder();
                this.loadVideos();
            }
            else {
                if (this.$route.name === 'videosTrashed') {
                    this.filters.trashed = true;
                }
                this.loadVideos();
            }
        })
    },

    watch: {
        courseVideosToCopy(newValue, oldValue) {
            if (this.isCourse && oldValue?.length > 0 && newValue?.length == 0) {
                this.updateSelectedVideos([]);
            }
        },

        // Catch every playlist change to handle video loading
        playlist(playlist) {
            this.$store.dispatch('videos/setVideoSortMode', false);
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
                this.$store.dispatch('videos/setVideosReload', false);
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
                    this.$store.dispatch('playlists/setPlaylistSort', {
                        token: this.playlist.token,
                        sort:  {
                            field: 'order',
                            order: 'asc',
                        }
                    });
                    // store the new sorting order
                    this.loadedVideos = this.sortedVideos;
                    this.$store.commit('videos/setVideos', this.sortedVideos);

                    this.$store.dispatch('videos/uploadSortPositions', {
                        playlist_token: this.playlist.token,
                        sortedVideos  : this.sortedVideos.map((elem) => elem.token)
                    });
                }
                else if (newmode === 'cancel') {
                    // Reload videos
                    this.loadVideos();
                }
                if (newmode !== false) {
                    this.$store.dispatch('videos/setVideoSortMode', false);
                }
            }
        },

        serversCheckSuccessful(success) {
            const error_msg = {
                type: 'error',
                text: this.$gettext('Es ist ein Verbindungsfehler zum Opencast Server aufgetreten und es wurden deshalb einige Funktionen deaktiviert. Bitte wenden Sie sich bei auftretenden Problemen an den Support oder versuchen Sie es zu einem späteren Zeitpunkt erneut.')
            };

            if (success) {
                this.$store.commit('opencast/setOpencastOffline', false);
                this.$store.dispatch('messages/removeMessage', error_msg);
            } else {
                this.$store.commit('opencast/setOpencastOffline', true);
                this.$store.dispatch('messages/addMessage', error_msg);
            }
        }
    },
};
</script>
