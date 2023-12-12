<template>
    <div>
        <div class="oc-cw-video-list">
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
                            {{ $gettext('Autor/-in') }}
                        </th>
                    </tr>
                </thead>

                <tbody v-if="loadingVideos" class="oc--episode-table--empty">
                    <EmptyVideoRow
                        :numberOfColumns="numberOfColumns"
                        :simple_config_list="simple_config_list"
                    />
                    <EmptyVideoRow
                        :numberOfColumns="numberOfColumns"
                        :simple_config_list="simple_config_list"
                    />
                    <EmptyVideoRow
                        :numberOfColumns="numberOfColumns"
                        :simple_config_list="simple_config_list"
                    />
                </tbody>
                <tbody v-else-if="Object.keys(videos).length === 0 || !simple_config_list">
                    <tr>
                        <td :colspan="numberOfColumns">
                            {{ $gettext('Es wurden keine Videos für die gewählten Ansichtsoptionen gefunden.') }}
                        </td>
                    </tr>
                </tbody>
                <tbody v-else>
                    <template v-for="event in videos" >
                        <VideoRow
                            :event="event"
                            :numberOfColumns="numberOfColumns"
                            @doAction="setVideo"
                            @setVideo="setVideo(event)"
                            @redirectAction="redirectAction"
                            :isLTIAuthenticated="isLTIAuthenticated"
                            :simple_config_list="simple_config_list"
                            :selected="selectedVideoId === event.token"
                        ></VideoRow>
                    </template>
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
import VideoRow from './VideoRow.vue';
import EmptyVideoRow from './EmptyVideoRow.vue';
import LtiAuth from './LtiAuth.vue';
import axios from 'axios';

export default {
    name: "CoursewareVideoTable",

    props: ['videos', 'paging', 'selectedVideoId', 'loadingVideos', 'limit', 'sorts', 'videoSort'],

    components: {
        PaginationButtons,
        VideoRow,
        EmptyVideoRow,
        LtiAuth
    },

    data() {
        return {
            interval: null,
            interval_counter: 0,
            isLTIAuthenticated: {},
            simple_config_list: null,
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