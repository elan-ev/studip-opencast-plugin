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

        <div v-if="playlistForVideos" class="oc--bulk-actions">
            <input type="checkbox" :checked="selectAll" @click.stop="toggleAll">
            <StudipButton icon="add" @click.stop="addVideosToPlaylist">
                {{ $gettext('Zur Wiedergabeliste hinzufügen') }}
            </StudipButton>

        </div>

        <!--

             <select>
                <option>{{ $gettext('Aktionen') }}</option>
            </select>
            -->

        <div id="episodes" class="oc--flexitem oc--flexepisodelist">
            <ul v-if="Object.keys(videos).length === 0 && (axios_running || videos_loading)" class="oc--episode-list oc--episode-list--empty">
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
                    v-for="event in videos"
                    v-bind:event="event"
                    v-bind:key="event.token"
                    :playlistForVideos="playlistForVideos"
                    :selectedVideos="selectedVideos"
                    :isCourse="isCourse"
                    @toggle="toggleVideo"
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
import StudipButton from "@studip/StudipButton";
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
import Tag from '@/components/Tag.vue'

export default {
    name: "VideosList",

    props: {
        'playlist_token': {
            type: String,
            default: null
        },
        'filters': {
            type: Object,
            default: []
        }
    },

    components: {
        VideoCard, EmptyVideoCard,
        PaginationButtons, MessageBox,
        SearchBar, Tag,
        StudipButton, VideoAddToPlaylist,
        VideoAddToSeminar, VideoDelete,
        VideoDownload, VideoReport,
        VideoEdit
    },

    data() {
        return {
            selectedVideos: [],
            videos_loading: true,
            actionComponent: null,
            showActionDialog: false,
            selectedEvent: null
        }
    },

    computed: {
        ...mapGetters([
            "videos",
            "paging",
            "axios_running",
            "playlistForVideos",
            "cid",
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
            await this.$store.dispatch('loadVideos', this.filters)
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
            filters.filters = filters.filters.concat(this.filters);
            this.$store.dispatch('loadVideos', filters)
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
        let view = this;
        this.$store.commit('clearPaging');
        this.$store.dispatch('authenticateLti').then(() => {
            console.log(JSON.stringify(view.filters));
            view.$store.dispatch('loadVideos', view.filters)
                .then(() => { view.videos_loading = false });
        })
        this.$store.dispatch('loadUserCourses');
    }
};
</script>
