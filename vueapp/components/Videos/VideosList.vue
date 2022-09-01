<template>
    <div>
        <SearchBar @search="doSearch" v-if="!videoSortMode"/>
        <PaginationButtons @changePage="changePage"/>

        <div id="episodes" class="oc--flexitem oc--flexepisodelist">
            <ul v-if="Object.keys(visVideos).length === 0 && loading" class="oc--episode-list oc--episode-list--empty">
                <EmptyVideoCard />
                <EmptyVideoCard />
                <EmptyVideoCard />
                <EmptyVideoCard />
                <EmptyVideoCard />
            </ul>

            <ul v-else-if="Object.keys(visVideos).length === 0" class="oc--episode-list oc--episode-list--empty">
                <MessageBox type="info">
                    <translate>
                        Es gibt bisher keine Aufzeichnungen.
                    </translate>
                </MessageBox>
            </ul>

            <ul class="oc--episode-list" v-else>
                <VideoCard
                    v-for="(event, index) in visVideos"
                    v-bind:event="event"
                    v-bind:key="event.id"
                    :canMoveUp="canMoveUp(index)"
                    :canMoveDown="canMoveDown(index)"
                    @moveUp="moveUpVideoCard"
                    @moveDown="moveDownVideoCard"
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

export default {
    name: "VideosList",

    components: {
        VideoCard, EmptyVideoCard, PaginationButtons, MessageBox, SearchBar
    },

    computed: {
        ...mapGetters([
            "videos",
            "videoSortMode",
            "currentPlaylist",
            "paging",
            "loading"]),

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

        doSearch(filters) {
            console.log('video list update initiated', filters);
            this.$store.dispatch('loadVideos', filters)
        },

        canMoveUp(index) {
            return this.videoSortMode && (this.paging.currPage !== 0 || index !== 0);
        },

        canMoveDown(index) {
            return this.videoSortMode && (index !== this.visVideos.length - 1 || !(this.paging.currPage !== 0));
        },

        moveUpVideoCard(token) {
            let sortVideos = this.visVideos;
            const index = sortVideos.findIndex(video => {
                return video.token === token;
            });

            if (this.canMoveUp(index)) {
                if (index !== 0) {
                    sortVideos.splice(index - 1, 0, sortVideos.splice(index, 1)[0]);
                }
                else {
                    let length = this.videos[this.currentPlaylist][this.paging.currPage-1].length;
                    let tmp = sortVideos[index];
                    sortVideos[index] = this.videos[this.currentPlaylist][this.paging.currPage-1][length-1];
                    this.videos[this.currentPlaylist][this.paging.currPage-1][length-1] = tmp;
                }
            }
        },

        moveDownVideoCard(token) {
            let sortVideos = this.visVideos;
            const index = sortVideos.findIndex(video => {
                return video.token === token;
            });

            if (this.canMoveDown(index)) {
                if (index !== this.visVideos.length - 1) {
                    sortVideos.splice(index + 1, 0, sortVideos.splice(index, 1)[0]);
                }
                else {
                    let tmp = sortVideos[index];
                    sortVideos[index] = this.videos[this.currentPlaylist][this.paging.currPage+1][0];
                    this.videos[this.currentPlaylist][this.paging.currPage+1][0] = tmp;
                }
            }
        },
    },

    mounted() {
        // this.$store.dispatch('loadVideos');
    }
};
</script>
