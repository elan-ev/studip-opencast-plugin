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
                <ul class="oc--episode-list--small" v-else>
                    <VideoCard
                        v-if="simple_config_list"
                        v-for="event in videos"
                        class="{selected: selectedVideoId == event.id}"
                        v-bind:event="event"
                        v-bind:key="event.token"
                        :isLTIAuthenticated="isLTIAuthenticated"
                        :simple_config_list="simple_config_list"
                        @doAction="setVideo"
                        @redirectAction="redirectAction"
                        @setVideo="setVideo(event)"
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

        <LtiAuth v-if="simple_config_list"
            :simple_config_list="simple_config_list"
        />
    </div>
</template>

<script>
import PaginationButtons from './PaginationButtons.vue';
import VideoCard from './VideoCard.vue';
import LtiAuth from './LtiAuth.vue';
import axios from 'axios';

export default {
    name: "CoursewareVideoTable",

    props: ['videos', 'paging', 'selectedVideoId', 'loadingVideos', 'limit'],

    components: {
        PaginationButtons,
        VideoCard,
        LtiAuth

    },

    data() {
        return {
            interval: null,
            interval_counter: 0,
            isLTIAuthenticated: {},
            simple_config_list: null
        }
    },

    methods: {
        startPageChange(page) {
            this.$emit('doChangePage', page);
        },

        setVideo(video) {
            this.$emit('doSelectVideo', video);
        },

        checkLTIAuthentication(server)
        {
            axios({
                method: 'GET',
                url: server.name + "/lti/info.json",
                crossDomain: true,
                withCredentials: true,
                headers: {
                    "Content-Type": "application/x-www-form-urlencoded; charset=UTF-8"
                }
            }).then((response) => {
                if (response.status == 200 && response.data.user_id !== undefined) {
                    this.$set(this.isLTIAuthenticated, server.id, true);
                }
            });
        },

        redirectAction(action) {
            let redirectUrl = this.simple_config_list.redirect_url;

            if (redirectUrl) {
                redirectUrl = redirectUrl + action;
                window.open(redirectUrl, '_blank');
            }
        }
    },

    mounted() {
        let view = this;

        axios.get(STUDIP.ABSOLUTE_URI_STUDIP + 'plugins.php/opencast/api/config/simple')
            .then(({data}) => {
                view.simple_config_list = data;

                let server = data['server'];

                view.interval = setInterval(() => {
                    for (let id in server) {
                        if (!view.isLTIAuthenticated[id]) {
                            view.checkLTIAuthentication(server[id]);
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