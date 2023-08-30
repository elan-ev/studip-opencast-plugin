<template>
    <div>
        <label v-if="Object.keys(videos).length === 0">
            <translate>
                Es konnten keine Videos gefunden werden
            </translate>
        </label>
        <div class="oc-cw-video-list" v-else>
            <PaginationButtons :paging="paging" @changePage="startPageChange"/>

            <label>
                <ul v-if="loadingVideos">
                    <li v-for="(n, index) in limit" :key="index">
                        <div class="oc-cw-loadingbar"></div>
                        <div class="oc-cw-loadingbar"></div>
                        <div class="oc-cw-loadingbar"></div>
                    </li>
                </ul>
                <ul class="video-list" v-else>
                    <VideoCard
                        v-for="event in videos"
                        v-bind:event="event"
                        v-bind:key="event.token"
                        :isLTIAuthenticated="isLTIAuthenticated"
                        :plugin_assets_url="plugin_assets_url"
                        @doAction="setVideo"
                        @redirectAction="redirectAction"
                    ></VideoCard>

                    <!--
                    <li v-for="(video, index) in videos" :key="index" :class="{selected: selectedVideoId == video.id}" @click="setVideo(video)">
                        <div>
                            <strong>
                                {{video.title}}
                            </strong>
                            <div>
                                {{ printDetails(video) }}
                                {{ video.author }}
                                <!- <span v-if="video.created">
                                    - {{ video.created }} Uhr
                                </span> ->
                            </div>
                        </div>
                    </li>
                    -->
                </ul>
            </label>
        </div>
    </div>
</template>

<script>
import PaginationButtons from './PaginationButtons.vue';
import VideoCard from './VideoCard.vue';
import axios from 'axios';

export default {
    name: "CoursewareVideoTable",

    props: ['videos', 'paging', 'selectedVideoId', 'loadingVideos', 'limit'],

    components: {
        PaginationButtons,
        VideoCard
    },

    data() {
        return {
            interval: null,
            interval_counter: 0,
            plugin_assets_url: '',
            isLTIAuthenticated: {}
        }
    },

    methods: {
        startPageChange(page) {
            this.$emit('doChangePage', page);
        },

        setVideo(video) {
            this.$emit('doSelectVideo', video);
        }
    },

    mounted() {
        let view = this;

        axios.get(STUDIP.ABSOLUTE_URI_STUDIP + 'plugins.php/opencast/api/config/simple')
            .then(({data}) => {
                view.plugin_assets_url = data.plugin_assets_url;
                console.log(data);

                view.interval = setInterval(() => {
                    for (let id in data['server']) {
                        if (!view.isLTIAuthenticated[id]) {
                            view.checkLTIAuthentication(data['server'][id]);
                        }
                    }

                    view.interval_counter++;

                    // prevent spamming of oc server
                    if (view.interval_counter > 10) {
                        clearInterval(view.interval);
                    }
                }, 2000);
            });
    }
}
</script>