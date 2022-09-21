<template>
    <div>
        <SearchBar @search="doSearch" v-if="!videoSortMode" :playlist="playlist" />

        <div class="oc--bulk-actions">
            <input type="checkbox" :checked="selectAll" @click.stop="toggleAll">

            <StudipButton icon="trash" @click.prevent="removeVideosFromPlaylist">
                {{ $gettext('Videos aus der Wiedergabeliste löschen') }}
            </StudipButton>
        </div>

        <div id="episodes" class="oc--flexitem oc--flexepisodelist">
            <ul v-if="Object.keys(videos_list).length === 0 && (axios_running || videos_loading)" class="oc--episode-list--small oc--episode-list--empty">
                <EmptyVideoCard />
                <EmptyVideoCard />
                <EmptyVideoCard />
                <EmptyVideoCard />
                <EmptyVideoCard />
            </ul>

            <ul v-else-if="Object.keys(videos_list).length === 0" class="oc--episode-list oc--episode-list--empty">
                <MessageBox type="info">
                    {{ $gettext('Es wurden keine Videos für die gewählten Ansichtsoptionen gefunden.') }}
                </MessageBox>
            </ul>

            <draggable class="oc--episode-list--small" v-else
                :disabled="!videoSortMode"
                v-model="videos_list"
                item-key="id"
                ghost-class="oc--ghost">
                <template #item="{element, index}">
                    <PlaylistVideoCard
                        :event="element"
                        :canMoveUp="canMoveUp(index)"
                        :canMoveDown="canMoveDown(index)"
                        @moveUp="moveUpVideoCard"
                        @moveDown="moveDownVideoCard"
                        :playlistForVideos="playlistForVideos"
                        :selectedVideos="selectedVideos"
                        @toggle="toggleVideo"
                    ></PlaylistVideoCard>
                </template>
            </draggable>
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

import draggable from 'vuedraggable'

export default {
    name: "PlaylistVideos",

    props: {
        'playlist': {
            type: Object,
            required: true
        }
    },

    components: {
        PlaylistVideoCard,  EmptyVideoCard,
        MessageBox,
        SearchBar,          Tag,
        StudipButton,
        draggable
    },

    data() {
        return {
            filters: [],
            selectedVideos: [],
            videos_loading: true,
            sortedVideos: null
        }
    },

    computed: {
        ...mapGetters([
            "videos",
            "videoSortMode",
            "paging",
            "axios_running",
            "playlistForVideos"
        ]),

        selectAll() {
            return this.videos.length == this.selectedVideos.length;
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
        }

    },

    methods: {
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

        doSearch(options) {
            let view = this;

            options.filters = options.filters.concat(this.filters);
            options.limit = -1;

            this.$store.dispatch('loadVideos', options)
                .then(() => { view.videos_loading = false });
        },

        canMoveUp(index) {
            return this.videoSortMode && (index !== 0);
        },

        canMoveDown(index) {
            return this.videoSortMode && (index !== this.videos.length - 1);
        },

        moveUpVideoCard(token) {
            const index = this.sortedVideos.findIndex(video => {
                return video.token === token;
            });

            if (this.canMoveUp(index)) {
                let tmp = this.sortedVideos[index - 1];
                this.sortedVideos[index - 1] = this.sortedVideos[index];
                this.sortedVideos[index] = tmp;
            }
        },

        moveDownVideoCard(token) {
            const index = this.sortedVideos.findIndex(video => {
                return video.token === token;
            });

            if (this.canMoveDown(index)) {
                let tmp = this.sortedVideos[index + 1];
                this.sortedVideos[index + 1] = this.sortedVideos[index];
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

                this.$store.dispatch('loadVideos', {
                    filters: this.filters,
                    limit: -1
                })
            })
        }
    },

    watch: {
        videoSortMode(newmode) {
            if (newmode === true) {
                this.sortedVideos = this.videos;
            } else {
                if (newmode === 'commit') {
                    // store the new sorting order
                    this.$store.commit('setVideos', this.sortedVideos);

                    this.$store.dispatch('uploadSortPositions', {
                        playlist_token: this.playlist.token,
                        sortedVideos  : this.sortedVideos.map((elem) => elem.token)
                    });

                    this.$store.dispatch('setVideoSortMode', false);
                } else {
                    // cancel sorting
                }

            }
        }
    },

    mounted() {
        let view = this;

        this.$store.commit('clearPaging');
        this.$store.commit('setVideos', {});

        this.filters.push({
            type: 'playlist',
            value: this.playlist.token
        });


        this.$store.dispatch('loadVideos', {
            filters: this.filters,
            limit: -1
        }).then(() => { view.videos_loading = false });
    }
};
</script>
