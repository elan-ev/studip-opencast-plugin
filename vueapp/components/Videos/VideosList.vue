<template>
    <div>
        {{ selectedVideos }}
        <MessageBox type="info" v-if="playlistForVideos">
            {{ $gettext('Bitte wählen Sie die Videos aus, die zur Wiedergabeliste hinzugefügt werden sollen.') }}
        </MessageBox>
        <h3 v-if="playlistForVideos">
            {{ playlistForVideos.title }}
            <div class="oc--tags">
                <Tag v-for="tag in playlistForVideos.tags" v-bind:key="tag.id" :tag="tag.tag" />
            </div>
        </h3>
        <SearchBar @search="doSearch" v-if="!videoSortMode"/>
        <PaginationButtons @changePage="changePage"/>

        <div v-if="playlistForVideos || true" class="oc--bulk-actions">
        <input type="checkbox" v-model="selectAll" @change="toggleAll">
        <select>
            <option>{{ $gettext('Aktionen') }}</option>
        </select>
        </div>

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
                    @moveUp="moveUpVideoCard"
                    @moveDown="moveDownVideoCard"
                    :playlistForVideos="playlistForVideos"
                    @toggleVideo="toggleVideo"
                ></VideoCard>
            </ul>
        </div>
    </div>
</template>

<script>
import { mapGetters } from "vuex";
import VideoCard from './VideoCard.vue';
import EmptyVideoCard from './EmptyVideoCard.vue';
import PaginationButtons from '@/components/PaginationButtons.vue';
import MessageBox from '@/components/MessageBox.vue';
import SearchBar from '@/components/SearchBar.vue'
import Tag from '@/components/Tag.vue'

export default {
    name: "VideosList",

    props: {
        'playlist_token': {
            type: String,
            default: null
        }
    },

    components: {
        VideoCard,          EmptyVideoCard,
        PaginationButtons,  MessageBox,
        SearchBar,          Tag
    },

    data() {
        return {
            filters: [],
            selectedVideos: [],
            selectAll: false
        }
    },

    computed: {
        ...mapGetters([
            "videos",
            "videoSortMode",
            "currentPlaylist",
            "paging",
            "loading"]),
            "loading",
            "playlistForVideos"
        ]),

        visVideos() {
            if (this.videos[this.currentPlaylist] === undefined ||
                this.videos[this.currentPlaylist][this.paging.currPage] === undefined) {
                return {};
            }
            return this.videos[this.currentPlaylist][this.paging.currPage]
        }
    },

    methods: {
        changePage: async function(page) {
            await this.$store.dispatch('setPage', page)
            await this.$store.dispatch('loadVideos')
        },

        toggleVideo(id) {
            let index = this.selectedVideos.indexOf(id);

            console.log(this.selectedVideos, id, index);

            if (index >= 0) {
                this.selectedVideos.splice(index, 1);
            } else {
                this.selectedVideos.push(id);
            }
        },

        toggleAll() {
            if (this.selectAll) {
                this.selectedVideos = [];
                for (let id in this.visVideos) {

                }
            } else {
                this.selectedVideos = [];
            }
        },

        doSearch(filters) {
            filters.concat(this.filters);
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
    },

    mounted() {
        this.$store.commit('clearPaging');

        if (this.playlist_token) {
            this.filters.push({
                type: 'playlist',
                value: this.playlist_token
            });
        }

        this.$store.dispatch('loadVideos', this.filters);

    }
};
</script>
