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
                    <!--
                    <span v-if="isCurrentVideoLTIChecked && !isCurrentVideoLTIAuthenticated" v-text="$gettext('Es ist ein Verbindungsfehler zum Opencast Server aufgetreten. Das ausgewählte Video kann zurzeit nicht angezeigt werden.')"></span>
                    -->

                    <span v-if="!currentVideoId" v-text="$gettext('Es wurde bisher kein Video ausgewählt')"></span>
                    <span v-else-if="!currentEpisodeURL" v-text="$gettext('Dieses Video hat keinen Veröffentlichungs-URL-Link')"></span>
                    <iframe v-else :src="currentEpisodeURL"
                        class="oc_cw_iframe"
                        allowfullscreen
                    ></iframe>

                    <div v-if="currentVisible == 'intern' && canEdit" class="messagebox messagebox_warning cw-canvasblock-text-info">
                        <translate>
                            Dieses Video ist für die Teilnehmenden dieser Veranstaltung nicht sichtbar!
                            Korrigieren Sie die Sichtbarkeitseinstellungen im Opencast-Reiter.
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
                        :currentCourseSelectable="isCourse"
                        :showCurrentCourse="showCurrentCourse"
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
                        :simple_config_list="simple_config_list"
                        :isLTIAuthenticated="isLTIAuthenticated"
                    />
                </form>
            </template>

            <template #info>
                <translate>
                    Informationen zum Opencast-Block
                </translate>
            </template>
        </component>
    </div>
</template>

<script setup>
import CoursewareSearchBar from './components/CoursewareSearchBar.vue';
import CoursewareVideoTable from './components/CoursewareVideoTable.vue';
import { computed, ref, getCurrentInstance, onMounted, inject } from "vue";
import { useStore } from 'vuex';
import axios from 'axios';

const get = window._.get.bind(window._);
const store = useStore();
const containerComponents = inject('containerComponents', {});

const props = defineProps({
    block: Object,
    canEdit: Boolean,
    isTeacher: Boolean,
});

const { proxy } = getCurrentInstance();

// Data.
const sortsInit = [
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

const pagingInit = {
    currPage: 0,
    lastPage: 0,
    items: 0
};

const searchText = ref('');
const showCurrentCourse = ref();
const sorts = ref(sortsInit);
const sortObj = ref(sortsInit[0]);
const limit = ref(15);
const paging = ref(pagingInit);
const videos = ref([]);
const loadingVideos = ref(false);
const currentVideoId = ref(null);
const currentEpisodeURL = ref(null);
const currentVisible = ref('');
const isLTIAuthenticated = ref({});
const simple_config_list = ref(null);
const interval = ref();
const interval_counter = ref(0);

// Computed props.
const context = computed(() => store.getters.context);
const relatedContainers = computed(() => store.getters['courseware-containers/related']);
const container = computed(() => {
    return (
        relatedContainers.value({
            parent: props.block,
            relationship: "container",
        }) ?? {}
    );
});

const isCourse = computed(() => context.value.type === 'course');
const currentVideo = computed(() => videos.value.find(video => video.token === currentVideoId.value));
const isCurrentVideoLTIChecked = computed(() => {
    if (!currentVideo.value) {
        return false;
    }
    return isLTIAuthenticated.value[currentVideo.value.config_id] !== undefined;
});
const isCurrentVideoLTIAuthenticated = computed(() => {
    if (!currentVideo.value) {
        return false;
    }
    return isLTIAuthenticated.value[currentVideo.value.config_id] === true;
});

// Store Actions.
const companionWarning = (payload) => store.dispatch('companionWarning', payload);
const companionSuccess = (payload) => store.dispatch('companionSuccess', payload);
const companionError = (payload) => store.dispatch('companionError', payload);
const updateBlock = (payload) => store.dispatch('updateBlockInContainer', payload);

// Methods.
const resetPaging = () => {
    paging.value = pagingInit;
};

const performSearch = ({sText, currentCourseDisplayToggle}) => {
    searchText.value = sText;
    showCurrentCourse.value = currentCourseDisplayToggle;
    resetPaging();
    loadVideos();
};

const performSort = (sObj) => {
    sortObj.value = sObj;
    resetPaging();
    loadVideos();
};

const performPageChange = (pg) => {
    paging.value.currPage = pg;
    loadVideos();
};

const performSelectVideo = (video) => {
    currentVideoId.value = video.token;
    currentEpisodeURL.value = STUDIP.ABSOLUTE_URI_STUDIP + 'plugins.php/opencastv3/redirect/perform/video/' + video.token;
    currentVisible.value = video?.visibility || 'public';
};

const storeBlock = () => {
    if (!currentVideoId.value) {
        companionWarning({
            info: proxy.$gettext('Bitte wählen Sie eine Video aus.')
        });
        return false;
    }
    let attributes = {};
    attributes.payload = {};
    attributes.payload.token = currentVideoId.value;
    attributes.payload.visible = currentVisible.value;

    if (container.value?.id && props.block?.id) {
        return updateBlock({
            attributes: attributes,
            blockId: props.block.id,
            containerId: container.value.id,
        });
    } else {
        companionError({
            info: proxy.$gettext('Ungültiger Block')
        });
    }
};

const initCurrentData = () => {
    showCurrentCourse.value = isCourse.value;
    currentVideoId.value = get(props.block, "attributes.payload.token", "");
    currentEpisodeURL.value = STUDIP.ABSOLUTE_URI_STUDIP + 'plugins.php/opencastv3/redirect/perform/video/' + currentVideoId.value;
    currentVisible.value = get(props.block, "attributes.payload.visible", "");

    let copied_from = get(props.block, "attributes.payload.copied_from", "");
    if (copied_from) {
        storeBlock();
    }
};

const loadVideos = () => {
    if (props.canEdit) {
        loadingVideos.value = true;
        const params = new URLSearchParams();
        params.append('offset', paging.value.currPage * limit.value);
        params.append('limit', limit.value);
        if (sortObj.value) {
            params.append('order', sortObj.value.field + "_" + sortObj.value.order)
        }

        let filters = [];
        if (searchText.value) {
            filters.push({
                type: 'text',
                value: searchText.value
            });
        }
        if (showCurrentCourse.value) {
            filters.push({
                type: 'course',
                compare: '=',
                value: context.value.id
            });
        }

        if (filters.length > 0) {
            params.append('filters', JSON.stringify(filters));
        }

        axios
            .get(STUDIP.ABSOLUTE_URI_STUDIP + 'plugins.php/opencastv3/api/courseware/videos', { params })
            .then(({ data }) => {
                paging.value.items = parseInt(data.count);
                paging.value.lastPage = parseInt(paging.value.items / limit.value);
                videos.value = data.videos;
                loadingVideos.value = false;
            });
    } else {
        // load only the current video if user has no edit perms
        axios
            .get(STUDIP.ABSOLUTE_URI_STUDIP + 'plugins.php/opencastv3/api/videos/' + currentVideoId.value)
            .then(({ data }) => {
                videos.value = [];
                videos.value.push(data.video);
            });
    }
};

const checkLTIAuthentication = (server) => {
    let axiosConfigOptions = {
        method: 'GET',
        url: server.name + "/lti/info.json",
        crossDomain: true,
        withCredentials: true,
        headers: {
            "Content-Type": "application/x-www-form-urlencoded; charset=UTF-8"
        }
    };
    if (server?.timeout > 0) {
        axiosConfigOptions.timeout = server.timeout;
    }
    axios(axiosConfigOptions).then((response) => {
        if (response.status == 200 && response.data.user_id !== undefined) {
            isLTIAuthenticated.value[server.id] = true;
        } else {
            isLTIAuthenticated.value[server.id] = false;
        }
    }).catch(() => {
        isLTIAuthenticated.value[server.id] = false;
    });
};

onMounted(() => {
    initCurrentData();
    loadVideos();

    axios.get(STUDIP.ABSOLUTE_URI_STUDIP + 'plugins.php/opencastv3/api/config/simple')
        .then(({data}) => {
            simple_config_list.value = data;

            let server = data['server'];

            interval.value = setInterval(() => {
                for (let id in server) {
                    if (!isLTIAuthenticated.value[id]) {
                        checkLTIAuthentication(server[id]);
                    }
                }

                interval_counter.value++;

                // prevent spamming of oc server
                if (interval_counter.value > 10) {
                    clearInterval(interval.value);
                }
            }, 2000);
        });
});

</script>
