<template>
    <div>
        <SearchBar/>
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
                    v-for="event in visVideos"
                    v-bind:event="event"
                    v-bind:key="event.id"
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
        }
    },

    mounted() {
        // this.$store.dispatch('loadVideos');
    }
};
</script>
