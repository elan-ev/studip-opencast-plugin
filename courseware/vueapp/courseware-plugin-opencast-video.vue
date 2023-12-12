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
                    <span v-if="!currentVideoId" v-text="$gettext('Es wurde bisher kein Video ausgewählt')"></span>
                    <span v-else-if="!currentEpisodeURL" v-text="$gettext('Dieses Video hat keinen Veröffentlichungs-URL-Link')"></span>
                    <iframe v-else :src="currentEpisodeURL"
                        class="oc_cw_iframe"
                        allowfullscreen
                    ></iframe>

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
                        @doSearch="performSearch"
                    />
                    <CoursewareVideoTable
                        :videos="videos"
                        :selectedVideoId="currentVideoId"
                        :paging="paging"
                        :loadingVideos="loadingVideos"
                        :limit="limit"
                        @doSelectVideo="performSelectVideo"
                        @doChangePage="performPageChange"
                        @doSort="performSort"
                        :sorts="sorts"
                        :videoSort="sortObj"
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
        CoursewareVideoTable,
    },

    props: {
        block: Object,
        canEdit: Boolean,
        isTeacher: Boolean,
    },

    data() {
        const sorts = [
            {
                field: 'created',
                order: 'desc',
            }, {
                field: 'created',
                order: 'asc',
            }, {
                field: 'title',
                order: 'asc',
            }, {
                field: 'title',
                order: 'desc',
            }
        ];

        return {
            searchText: '',
            sorts: sorts,
            sortObj: sorts[0],
            limit: 15,
            paging: {
                    currPage: 0,
                    lastPage: 0,
                    items: 0
                },
            videos: {},
            loadingVideos : false,
            currentVideoId : null,
            currentEpisodeURL : null,
            currentVisible : '',
        }
    },

    computed: {
        ...mapGetters({
            context: 'context',
            relatedContainers: 'courseware-containers/related',
        }),

        container() {
            return (
                this.relatedContainers({
                parent: this.block,
                relationship: "container",
                }) ?? {}
            );
        },
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
            // this.resetPaging();
            this.loadVideos();
        },

        performSort(sortObj) {
            this.sortObj = sortObj;
            // this.resetPaging();
            this.loadVideos();
        },

        performPageChange(page) {
            this.paging.currPage = page;
            this.loadVideos();
        },

        performSelectVideo(video) {
            this.currentVideoId = video.token;
            this.currentEpisodeURL = STUDIP.ABSOLUTE_URI_STUDIP + 'plugins.php/opencast/redirect/perform/video/' + video.token;
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
            attributes.payload.token = this.currentVideoId;
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
            this.currentVideoId = get(this.block, "attributes.payload.token", "");
            this.currentEpisodeURL =STUDIP.ABSOLUTE_URI_STUDIP + 'plugins.php/opencast/redirect/perform/video/' + this.currentVideoId;
            this.currentVisible = get(this.block, "attributes.payload.visible", "");

            let copied_from = get(this.block, "attributes.payload.copied_from", "");
            if (copied_from) {
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
    },

    mounted() {
        this.initCurrentData();
        this.loadVideos();
    },

    inject: ["containerComponents"],
}
</script>
