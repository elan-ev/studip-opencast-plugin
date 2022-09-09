<template>
    <div>
        <SearchBar @search="doSearch" v-if="!videoSortMode"/>
        <PaginationButtons @changePage="changePage"/>

        <div id="episodes" class="oc--flexitem oc--flexepisodelist">
            <ul v-if="Object.keys(videos).length === 0 && loading" class="oc--episode-list oc--episode-list--empty">
                <EmptyVideoCard />
                <EmptyVideoCard />
                <EmptyVideoCard />
                <EmptyVideoCard />
                <EmptyVideoCard />
            </ul>

            <ul v-else-if="Object.keys(videos).length === 0" class="oc--episode-list oc--episode-list--empty">
                <MessageBox type="info">
                    <translate>
                        Es gibt bisher keine Aufzeichnungen.
                    </translate>
                </MessageBox>
            </ul>

            <ul class="oc--episode-list" v-else>
                <VideoCard
                    v-for="(event, index) in videos"
                    v-bind:event="event"
                    v-bind:key="event.id"
                    :canMoveUp="canMoveUp(index)"
                    :canMoveDown="canMoveDown(index)"
                    :isCourse="isCourse"
                    @moveUp="moveUpVideoCard"
                    @moveDown="moveDownVideoCard"
                    @doAction="doAction"
                ></VideoCard>
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
import VideoCard from './VideoCard.vue';
import EmptyVideoCard from './EmptyVideoCard.vue';
import PaginationButtons from '@/components/PaginationButtons.vue';
import MessageBox from '@/components/MessageBox.vue';
import SearchBar from '@/components/SearchBar.vue';
import VideoAddToPlaylist from '@/components/Videos/Actions/VideoAddToPlaylist.vue';
import VideoAddToSeminar from '@/components/Videos/Actions/VideoAddToSeminar.vue';
import VideoDelete from '@/components/Videos/Actions/VideoDelete.vue';
import VideoDownload from '@/components/Videos/Actions/VideoDownload.vue';
import VideoReport from '@/components/Videos/Actions/VideoReport.vue';
import VideoEdit from '@/components/Videos/Actions/VideoEdit.vue';

export default {
    name: "VideosList",

    components: {
        VideoCard, EmptyVideoCard,
        PaginationButtons, MessageBox,
        SearchBar, VideoAddToPlaylist,
        VideoAddToSeminar, VideoDelete,
        VideoDownload, VideoReport,
        VideoEdit
    },

    data() {
        return {
            actionComponent: null,
            showActionDialog: false,
            selectedEvent: null
        }
    },

    computed: {
        ...mapGetters([
            "videos",
            "videoSortMode",
            "currentPlaylist",
            "paging",
            "loading",
            "cid"]),

        isCourse() {
            return this?.cid;
        },
    },

    methods: {
        changePage: async function(page) {
            await this.$store.dispatch('setPage', page)
            await this.$store.dispatch('loadVideos')
        },

        doSearch(filters) {
            console.log('video list update initiated', filters);
            this.$store.dispatch('loadVideos', filters)
        },

        canMoveUp(index) {
            return this.videoSortMode && (this.paging.currPage !== 0 || index !== 0);
        },

        canMoveDown(index) {
            return this.videoSortMode && (index !== this.videos.length - 1 || !(this.paging.currPage !== 0));
        },

        moveUpVideoCard(token) {
            const index = this.videos.findIndex(video => {
                return video.token === token;
            });

            if (this.canMoveUp(index)) {
                let from = {
                    playlist: this.currentPlaylist,
                    page: this.paging.currPage,
                    index: index
                }
                let to = {}

                if (index !== 0) {
                    to = {
                        playlist: this.currentPlaylist,
                        page: this.paging.currPage,
                        index: index-1
                    }
                }
                else {
                    let length = this.videos[this.currentPlaylist][this.paging.currPage-1].length;

                    to = {
                        playlist: this.currentPlaylist,
                        page: this.paging.currPage-1,
                        index: length-1
                    }
                }
                this.$store.dispatch('setVideoPosition', {'from': from, 'to': to})
            }
        },

        moveDownVideoCard(token) {
            const index = this.videos.findIndex(video => {
                return video.token === token;
            });

            if (this.canMoveDown(index)) {
                let from = {
                    playlist: this.currentPlaylist,
                    page: this.paging.currPage,
                    index: index
                }
                let to = {}

                if (index !== this.videos.length - 1) {
                    to = {
                        playlist: this.currentPlaylist,
                        page: this.paging.currPage,
                        index: index+1
                    }
                }
                else {
                    to = {
                        playlist: this.currentPlaylist,
                        page: this.paging.currPage+1,
                        index: 0
                    }
                }
                this.$store.dispatch('setVideoPosition', {'from': from, 'to': to})
            }
        },

        doAction(args) {
            if (Object.keys(this.$options.components).includes(args.actionComponent)) {
                this.actionComponent = args.actionComponent;
                this.selectedEvent = args.event;
                this.showActionDialog = true;
            }
        },

        async doAfterAction(args) {
            this.clearAction();
            if (args == 'refresh') {
                await this.$store.dispatch('loadVideos');
            }
        },

        clearAction() {
            this.showActionDialog = false;
            this.actionComponent = null;
            this.selectedEvent = null;
        }
    },

    mounted() {
        this.$store.commit('clearPaging');
        this.$store.dispatch('loadVideos');
    }
};
</script>
