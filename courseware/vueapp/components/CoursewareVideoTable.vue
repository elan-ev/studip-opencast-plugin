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
                    <li v-for="(video, index) in videos" :key="index" :class="{selected: selectedVideoId == video.id}" @click="setVideo(video)">
                        <div>
                            <strong>
                                {{video.title}}
                            </strong>
                            <div>
                                {{ printDetails(video) }}
                                <!-- {{ video.author }}
                                <span v-if="video.created">
                                    - {{ video.created }} Uhr
                                </span> -->
                            </div>
                        </div>
                    </li>
                </ul>
            </label>
        </div>
    </div>
</template>

<script>
import PaginationButtons from './PaginationButtons.vue';
import { format } from 'date-fns'
import { de } from 'date-fns/locale'

export default {
    name: "CoursewareVideoTable",

    props: ['videos', 'paging', 'selectedVideoId', 'loadingVideos', 'limit'],

    components: {
        PaginationButtons,
    },
    methods: {
        startPageChange(page) {
            this.$emit('doChangePage', page);
        },

        setVideo(video) {
            this.$emit('doSelectVideo', video);
        },

        printDetails(video) {
            let details = [];
            if (video?.author) {
                details.push(video.author);
            }
            if (video?.created) {
                let mydate = new Date(video.created);

                if (mydate instanceof Date && !isNaN(mydate)) {
                    details.push(format(mydate, "d. MMM, yyyy, HH:ii", {locale: de}));
                }
            }

            return details.join(' - ');
        }
    },
}
</script>