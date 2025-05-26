<template>
    <div>
        <StudipProgressIndicator
            v-if="loadingVideos"    
            class="oc--loading-indicator"
            :description="$gettext('Lade Videos...')"
            :size="64"
        />
        <div v-else class="oc-cw-video-list">
            <PaginationButtons :paging="paging" @changePage="startPageChange"/>
            <table id="episodes" class="default oc--episode-table--small" v-if="simple_config_list">
                <colgroup>
                    <col style="width: 119px">
                    <col>
                    <col style="width: 180px" class="responsive-hidden">
                    <col style="width: 150px" class="responsive-hidden">
                </colgroup>
                <thead>
                    <tr class="sortable">
                        <th data-sort="false">{{ $gettext('Video') }}</th>
                        <th @click="setSort('title')" :class="sortClasses('title')">
                            <a href="#" @click.prevent>
                                {{ $gettext('Titel') }}
                            </a>
                        </th>
                        <th @click="setSort('created')" class="responsive-hidden" :class="sortClasses('created')">
                            <a href="#" @click.prevent>
                                {{ $gettext('Datum') }}
                            </a>
                        </th>
                        <th data-sort="false" class="responsive-hidden">
                            {{ $gettext('Vortragende(r)') }}
                        </th>
                    </tr>
                </thead>
                <tbody v-if="Object.keys(videos).length === 0 || !simple_config_list">
                    <tr>
                        <td :colspan="numberOfColumns">
                            {{ $gettext('Es wurden keine Videos für die gewählten Ansichtsoptionen gefunden.') }}
                        </td>
                    </tr>
                </tbody>
                <tbody v-else>
                    <VideoRow
                        v-for="(event, index) in videos"
                        :event="event"
                        :key="index"
                        :numberOfColumns="numberOfColumns"
                        @doAction="setVideo"
                        @setVideo="setVideo(event)"
                        @redirectAction="redirectAction"
                        :isLTIAuthenticated="isLTIAuthenticated"
                        :simple_config_list="simple_config_list"
                        :selected="selectedVideoId === event.token"
                    />
                </tbody>
            </table>
        </div>

        <LtiAuth v-if="simple_config_list"
            :simple_config_list="simple_config_list"
        />
    </div>
</template>

<script>
import PaginationButtons from './PaginationButtons.vue';
import StudipProgressIndicator from './StudipProgressIndicator.vue';
import VideoRow from './VideoRow.vue';
import LtiAuth from './LtiAuth.vue';

export default {
    name: "CoursewareVideoTable",

    props: ['videos', 'paging', 'selectedVideoId', 'loadingVideos', 'limit', 'sorts', 'videoSort', 'isLTIAuthenticated', 'simple_config_list'],

    components: {
        PaginationButtons,
        StudipProgressIndicator,
        VideoRow,
        LtiAuth
    },

    data() {
        return {
            interval: null,
            interval_counter: 0,
            numberOfColumns: 5
        }
    },

    methods: {
        startPageChange(page) {
            this.$emit('doChangePage', page);
        },

        setVideo(video) {
            this.$emit('doSelectVideo', video);
        },

        sortClasses(column) {
            let classes = [];
            if (this.videoSort.field === column) {
                classes.push(this.videoSort.order === 'asc' ? 'sortasc' : 'sortdesc');
            }
            return classes;
        },

        redirectAction(action) {
            let redirectUrl = this.simple_config_list.redirect_url;

            if (redirectUrl) {
                redirectUrl = redirectUrl + action;
                window.open(redirectUrl, '_blank');
            }
        },

        setSort(column) {
            let videoSort = {
                field: column,
                order: 'asc'
            };

            if (this.videoSort.field === column) {
                if (this.playlist && this.videoSort.order === 'desc') {
                    // Custom order in playlists after descending order
                    videoSort.field = 'order';
                    videoSort.order = 'asc';
                } else {
                    videoSort.order = this.videoSort.order === 'desc' ? 'asc' : 'desc';
                }
            }

            this.$emit('doSort', videoSort)
        }
    },
}
</script>