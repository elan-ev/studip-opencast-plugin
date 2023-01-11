<template>
    <div>
        <MessageBox type="info" v-if="playlistForVideos">
            {{ $gettext('Bitte wählen Sie die Videos aus, die zur Wiedergabeliste hinzugefügt werden sollen.') }}
        </MessageBox>
        <h3 v-if="playlistForVideos">
            {{ playlistForVideos.title }}
            <div class="oc--tags">
                <Tag v-for="tag in playlistForVideos.tags" v-bind:key="tag.id" :tag="tag.tag" />
            </div>
        </h3>
        <SearchBar @search="doSearch"/>
        <PaginationButtons @changePage="changePage"/>

        <div v-if="isCourse && currentPlaylist">
            <StudipButton
                v-if="currentPlaylist && getPlaylist(currentPlaylist).is_default != '1'"
                @click.prevent="removePlaylistFromCourse(currentPlaylist, cid)"
            >
                <studip-icon shape="trash" role="clickable" />
                {{ $gettext('Wiedergabeliste aus diesem Kurs entfernen') }}
            </StudipButton>

            <a :href="getPlaylistLink(currentPlaylist)" class="button" target="_blank">
                 <studip-icon shape="edit" role="clickable" />
                {{ $gettext('Wiedergabeliste bearbeiten') }}
            </a>
        </div>

        <div v-if="playlistForVideos" class="oc--bulk-actions">
            <input type="checkbox" :checked="selectAll" @click.stop="toggleAll">
            <StudipButton icon="add" @click.stop="addVideosToPlaylist">
                {{ $gettext('Zur Wiedergabeliste hinzufügen') }}
            </StudipButton>

        </div>

        <!--
        <div v-if="isCourse && Object.keys(videos).length > 0" class="oc--bulk-actions">
            <input type="checkbox" :checked="selectAll" @click.stop="toggleAll">
            <StudipButton icon="add" @click.stop="showCopyDialog">
                {{ $gettext('Verknüpfung mit anderen Kursen') }}
            </StudipButton>
        </div>
        -->

        <div id="episodes" class="oc--flexitem oc--flexepisodelist">
            <ul v-if="Object.keys(videos).length === 0 && (axios_running || videos_loading)" class="oc--episode-list--small oc--episode-list--empty">
                <EmptyVideoCard />
                <EmptyVideoCard />
                <EmptyVideoCard />
                <EmptyVideoCard />
                <EmptyVideoCard />
            </ul>

            <ul v-else-if="Object.keys(videos).length === 0" class="oc--episode-list oc--episode-list--empty">
                <MessageBox type="info">
                    {{ $gettext('Es wurden keine Videos für die gewählten Ansichtsoptionen gefunden.') }}
                </MessageBox>
            </ul>

            <ul class="oc--episode-list--small" v-else>
                <VideoCard
                    v-for="event in videos"
                    v-bind:event="event"
                    v-bind:key="event.token"
                    :playlistForVideos="playlistForVideos"
                    :selectedVideos="selectedVideos"
                    @toggle="toggleVideo"
                    @doAction="doAction"
                    @redirectAction="redirectAction"
                ></VideoCard>
                <!-- :isCourse="isCourse"  -->
            </ul>
        </div>

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

import VideoCard from './VideoCard.vue';
import EmptyVideoCard from './EmptyVideoCard.vue';
import PaginationButtons from '@/components/PaginationButtons.vue';
import MessageBox from '@/components/MessageBox.vue';
import SearchBar from '@/components/SearchBar.vue';
import VideoAddToPlaylist from '@/components/Videos/Actions/VideoAddToPlaylist.vue';
import VideoAddToSeminar from '@/components/Videos/Actions/VideoAddToSeminar.vue';
import VideoAccess from '@/components/Videos/Actions/VideoAccess.vue';
import VideoDelete from '@/components/Videos/Actions/VideoDelete.vue';
import VideoDownload from '@/components/Videos/Actions/VideoDownload.vue';
import VideoReport from '@/components/Videos/Actions/VideoReport.vue';
import VideoEdit from '@/components/Videos/Actions/VideoEdit.vue';
import Tag from '@/components/Tag.vue'

export default {
    name: "VideosList",

    components: {
        VideoCard, EmptyVideoCard,
        PaginationButtons,      MessageBox,
        SearchBar,              Tag,
        StudipButton,           VideoAddToPlaylist,
        VideoAccess,            StudipIcon,
        VideoAddToSeminar,      VideoDelete,
        VideoDownload,           VideoReport,
        VideoEdit
    },

    data() {
        return {
            selectedVideos: [],
            videos_loading: true,
            actionComponent: null,
            showActionDialog: false,
            selectedEvent: null,
            filters: []
        }
    },

    computed: {
        ...mapGetters([
            "videos",
            "paging",
            "axios_running",
            "playlistForVideos",
            "cid",
            'currentPlaylist',
            'courseVideosToCopy',
            'playlists'
        ]),

        isCourse() {
            return this?.cid ? true : false;
        },

        selectAll() {
            return this.videos.length == this.selectedVideos.length;
        }
    },

    methods: {
        changePage: async function(page) {
            await this.$store.dispatch('setPage', page)

            if (this.isCourse) {
                let filters = this.filters;
                filters.token = this.currentPlaylist;
                this.$store.dispatch('loadPlaylistVideos', filters);
            } else {
                await this.$store.dispatch('loadMyVideos', this.filters)
            }
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

            if (this.isCourse) {
                this.$store.dispatch('loadPlaylistVideos', {
                    ...filters,
                    cid: this.cid,
                    token: this.currentPlaylist
                });
            } else {
                this.$store.dispatch('loadMyVideos', this.filters)
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
                if (this.isCourse) {
                    this.$store.dispatch('loadPlaylistVideos', {
                        ...this.filters,
                        cid: this.cid,
                        token: this.currentPlaylist
                    });
                } else {
                    this.$store.dispatch('loadMyVideos', this.filters)
                }
            }
        },

        clearAction() {
            this.showActionDialog = false;
            this.actionComponent = null;
            this.selectedEvent = null;
        },

        removePlaylistFromCourse(token, cid) {
            if (confirm(this.$gettext('Sind sie sicher, dass sie diese Wiedergabeliste aus dem Kurs entfernen möchten?'))) {
                this.$store.commit('setCurrentPlaylist', null);
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

        getPlaylist(token) {
            for (let id in this.playlists) {
                if (this.playlists[id].token == token) {
                    return this.playlists[id];
                }
            }

            return null;
        }
    },

    async mounted() {
        let view = this;
        this.$store.commit('clearPaging');
        await this.$store.dispatch('authenticateLti').then(() => {
            if (view.isCourse) {
                if (this.currentPlaylist !== null) {
                    view.$store.dispatch('loadPlaylistVideos', {
                        ...this.filters,
                        cid  : view.cid,
                        token: view.currentPlaylist
                    }).then(() => { view.videos_loading = false });
                } else {
                    view.videos_loading = false;
                }
            } else {
                view.$store.dispatch('loadMyVideos', this.filters)
                    .then(() => { view.videos_loading = false });
            }
        })
        this.$store.dispatch('loadUserCourses');

    },

    watch: {
        courseVideosToCopy(newValue, oldValue) {
            if (this.isCourse && oldValue?.length > 0 && newValue?.length == 0) {
                this.selectedVideos = [];
            }
        }
    },
};
</script>
