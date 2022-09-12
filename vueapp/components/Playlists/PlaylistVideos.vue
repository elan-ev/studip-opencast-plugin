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
        <SearchBar @search="doSearch" v-if="!videoSortMode"/>

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
            <ul v-if="Object.keys(videos).length === 0 && (axios_running || videos_loading)" class="oc--episode-list--small oc--episode-list--empty">
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

            <ul class="oc--episode-list--small" v-else>
                <PlaylistVideoCard
                    v-for="(event, index) in videos"
                    v-bind:event="event"
                    v-bind:key="event.token"
                    :canMoveUp="canMoveUp(index)"
                    :canMoveDown="canMoveDown(index)"
                    @moveUp="moveUpVideoCard"
                    @moveDown="moveDownVideoCard"
                    :playlistForVideos="playlistForVideos"
                    :selectedVideos="selectedVideos"
                    @toggle="toggleVideo"
                ></PlaylistVideoCard>
            </ul>
        </div>
    </div>
</template>

<script>
import { mapGetters } from "vuex";
import StudipButton from "@studip/StudipButton";
import PlaylistVideoCard from '../Playlists/PlaylistVideoCard.vue';
import EmptyVideoCard from '../Videos/EmptyVideoCard.vue';
import MessageBox from '@/components/MessageBox.vue';
import SearchBar from '@/components/SearchBar.vue'
import Tag from '@/components/Tag.vue'

export default {
    name: "PlaylistVideos",

    props: {
        'playlist_token': {
            type: String,
            required: true
        }
    },

    components: {
        PlaylistVideoCard,  EmptyVideoCard,
        MessageBox,
        SearchBar,          Tag,
        StudipButton
    },

    data() {
        return {
            filters: [],
            selectedVideos: [],
            videos_loading: true

        }
    },

    computed: {
        ...mapGetters([
            "videos",
            "videoSortMode",
            "currentPlaylist",
            "paging",
            "axios_running",
            "playlistForVideos"
        ]),

        selectAll() {
            return this.videos.length == this.selectedVideos.length;
        }
    },

    methods: {
        changePage: async function(page) {
            await this.$store.dispatch('setPage', page)
            await this.$store.dispatch('loadVideos')
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
            filters.concat(this.filters);
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
                //this.$store.dispatch('setVideoPosition', {'from': from, 'to': to})
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
                //this.$store.dispatch('setVideoPosition', {'from': from, 'to': to})
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
        }
    },

    mounted() {
        let view = this;

        this.$store.commit('clearPaging');
        this.$store.commit('setVideos', {});

        this.filters.push({
            type: 'playlist',
            value: this.playlist_token
        });


        this.$store.dispatch('loadVideos', {
            filters: this.filters,
            limit: -1
        }).then(() => { view.videos_loading = false });
    }
};
</script>
