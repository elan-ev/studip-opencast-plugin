<template>
      <div class="cw-block cw-block-test">
        <component
            :is="containerComponents.CoursewareDefaultBlock"
            :block="block"
            :canEdit="canEdit"
            :isTeacher="isTeacher"
            :preview="true"
            @storeEdit="storeBlock"
            @closeEdit="initCurrentData"
        >
            <template #content>
                <div>
                  <div v-if="!lti_connected">
                      <span v-if="!currentEpisodeURL && !currentEpisodeId" v-translate v-text="'Es wurde bisher keine Video ausgewählt'"></span>
                      <span v-if="!currentEpisodeURL && currentEpisodeId" v-translate v-text="'Dieses Video hat keinen Veröffentlichungs-URL-Link'"></span>
                      <span v-else v-translate v-text="'Das Video ist nicht verfügbar'"></span>
                  </div>
                  <div v-else>
                      <iframe :src="currentEpisodeURL"
                        class="oc_cw_iframe"
                        allowfullscreen
                    ></iframe>
                  </div>

                  <div v-if="currentVisible == 'intern' && canEdit" class="messagebox messagebox_warning cw-canvasblock-text-info">
                      <translate>
                          Dieses Video ist für die Teilnehmenden dieser Veranstaltung nicht sichtbar!
                          Korrigieren sie die Sichtbarkeitseinstellungen im Opencast-Reiter.
                      </translate>
                  </div>
                </div>
            </template>
            <template v-if="canEdit" #edit>
                <form class="default" @submit.prevent="">
                    <label>
                        <translate>Videos</translate>
                    </label>
                    <CoursewareSearchBar
                        :sorts="sorts"
                        @doSearch="performSearch"
                        @doSort="performSort"
                    />
                    <CoursewareVideoTable
                        :videos="videos"
                        :selectedVideoId="currentVideoId"
                        :paging="paging"
                        :loadingVideos="loadingVideos"
                        :limit="limit"
                        @doSelectVideo="performSelectVideo"
                        @doChangePage="performPageChange"
                    />
                </form>
            </template>

            <template #info><translate>Informationen zum Opencast-Block</translate></template>
        </component>
    </div>
</template>

<script>
const get = window._.get.bind(window._);
import axios from 'axios';
import { mapActions, mapGetters } from 'vuex';
import CoursewareSearchBar from './components/CoursewareSearchBar.vue';
import CoursewareVideoTable from './components/CoursewareVideoTable.vue';

export default {
    name: "courseware-plugin-opencast-video",

    components: {
        CoursewareSearchBar,
        CoursewareVideoTable
    },
    props: {
        block: Object,
        canEdit: Boolean,
        isTeacher: Boolean,
    },

    data() {
        return {
            searchText: '',
            sortObj: null,
            limit: 5,
            paging: {
                    currPage: 0,
                    lastPage: 0,
                    items: 0
                },
            videos: {},
            loadingVideos : false,
            currentVideoId : null,
            currentEpisodeId : null,
            currentEpisodeURL : null,
            currentVisible : '',
            ltiConnections : [],
            ltiConnected: false
        }
    },

    computed: {
        ...mapGetters({
            context: 'context',
            relatedContainers: 'courseware-containers/related',
        }),

        sorts() {
            return [
                {
                    field: 'mkdate',
                    order: 'desc',
                    text : 'Datum hochgeladen: Neueste zuerst'
                },  {
                    field: 'mkdate',
                    order: 'asc',
                    text : 'Datum hochgeladen: Älteste zuerst'
                },  {
                    field: 'title',
                    order: 'desc',
                    text : 'Titel: Alphabetisch'
                }, {
                    field: 'title',
                    order: 'asc',
                    text : 'Titel: Umgekehrt Alphabetisch'
                }
            ];
        },

        container() {
            return (
                this.relatedContainers({
                parent: this.block,
                relationship: "container",
                }) ?? {}
            );
        },

        lti_connected() {
            if (!this.currentEpisodeURL || this.ltiConnections.length == 0) {
                return false;
            }
            let edpisode_url = new URL(this.currentEpisodeURL);
            let lti_connection = this.ltiConnections.find(connection => connection.launch_url.includes(edpisode_url.hostname));
            return lti_connection?.authenticated == true;
        }
    },

    methods: {
        ...mapActions({
            companionWarning: 'companionWarning',
            companionSuccess: 'companionSuccess',
            companionError: 'companionError',
            updateBlock: 'updateBlockInContainer',
        }),
        resetPaging() {
            this.paging = {
                    currPage: 0,
                    lastPage: 0,
                    items: 0
                };
        },

        performSearch(searchText) {
            this.searchText = searchText;
            this.resetPaging();
            this.loadVideos();
        },

        performSort(sortObj) {
            this.sortObj = sortObj;
            this.resetPaging();
            this.loadVideos();
        },

        performPageChange(page) {
            this.paging.currPage = page;
            this.loadVideos();
        },

        performSelectVideo(video) {
            this.currentVideoId = video.id;
            this.currentEpisodeId = video.episode;
            this.currentEpisodeURL = video?.publication?.track_link || '';
            this.currentVisible = video?.visibility || 'public';
        },

        storeBlock() {
            if (!this.currentVideoId) {
                this.companionWarning({
                    info: this.$gettext('Bitte wählen Sie eine Video aus.')
                });
                return false;
            }
            let attributes = {};
            attributes.payload = {};
            attributes.payload.video_id = this.currentVideoId;
            attributes.payload.episode_id = this.currentEpisodeId;
            attributes.payload.url = this.currentEpisodeURL;
            attributes.payload.visible = this.currentVisible;

            if (this.container?.id && this.block?.id) {
                return this.updateBlock({
                    attributes,
                    blockId: this.block.id,
                    containerId: this.container.id,
                });
            } else {
                this.companionError({
                    info: this.$gettext('Ungültiger Block')
                });
            }
        },

        initCurrentData() {
            this.currentVideoId = get(this.block, "attributes.payload.video_id", "");
            this.currentEpisodeId = get(this.block, "attributes.payload.episode_id", "");
            this.currentEpisodeURL = get(this.block, "attributes.payload.url", "");
            this.currentVisible = get(this.block, "attributes.payload.visible", "");

            let copied_from = get(this.block, "attributes.payload.copied_from", "");
            if (copied_from) {
                console.log('copied_from: ', copied_from);
                this.storeBlock();
            }
        },

        loadVideos() {
            let view = this;
            view.loadingVideos = true;
            const params = new URLSearchParams();
            params.append('offset', this.paging.currPage * this.limit);
            params.append('limit', this.limit);
            if (this.sortObj) {
                params.append('order', this.sortObj.field + "_" + this.sortObj.order)
            }
            if (this.searchText) {
                let filters = [{
                    type: 'text',
                    value: this.searchText
                }];
                params.append('filters', JSON.stringify(filters));
            }
            axios
                .get(STUDIP.ABSOLUTE_URI_STUDIP + 'plugins.php/opencast/api/videos', { params })
                .then(({ data }) => {
                    view.paging.items = parseInt(data.count);
                    view.paging.lastPage = parseInt(view.paging.items / view.limit);
                    view.videos = data.videos;
                    view.loadingVideos = false;
                })
        },

        async runLTI() {
            let view = this;
            return axios.get(STUDIP.ABSOLUTE_URI_STUDIP + 'plugins.php/opencast/api/lti/launch_data/' + this.context.id)
                .then(({data}) => {
                    if (data.lti.length > 0) {
                        data.lti.forEach(connection => view.ltiConnections.push({
                            launch_url: JSON.parse(JSON.stringify(connection.launch_url)),
                            launch_data: JSON.parse(JSON.stringify(connection.launch_data)),
                            authenticated: false
                        }));
                        view.ltiConnections.forEach(connection => view.checkConnection(connection));
                    } else {
                        console.log('No LTI data found');
                    }
                });
        },

        async checkConnection(connection) {
            let view = this;
            return axios({
                method: 'GET',
                url: connection.launch_url,
                crossDomain: true,
                withCredentials: true,
            }).then(({ data }) => {
                if (!data.roles) {
                    view.authenticateLTI(connection);
                } else {
                    connection.authenticated = true;
                }
            }).catch(() => {
                view.authenticateLTI(connection);
            });
        },

        async authenticateLTI(connection) {
            return axios({
                method: 'POST',
                url: connection.launch_url,
                data: new URLSearchParams(connection.launch_data),
                crossDomain: true,
                withCredentials: true,
                headers: {
                    "Content-Type": "application/x-www-form-urlencoded; charset=UTF-8"
                }
            }).then(() => {
                connection.authenticated = true;
            }).catch(() => {
                console.log('LTI Authentication failed:', connection.launch_url);
                connection.authenticated = false;
            });
        }
    },

    mounted() {
        this.runLTI();
        this.initCurrentData();
        this.loadVideos();
    },

    inject: ["containerComponents"],
}
</script>
