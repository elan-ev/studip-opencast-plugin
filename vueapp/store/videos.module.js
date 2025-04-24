import ApiService from "@/common/api.service";

const state = {
    videos: {},
    videoSearch: '',
    videoSort: {
        field: 'created',
        order: 'desc',
    },
    videoSortMode: false,
    videoSortList: {},
    videosCount: 0,
    limit: 15,
    paging: {
        currPage: 0,
        lastPage: 0,
        items: 0
    },
    availableVideoTags: [],
    availableVideoCourses: [],
    videoShares: {},
    courseVideosToCopy: [],
    videosReload: false,
    showEpisodesDefaultVisibilityDialog: false,
}

const getters = {
    videos(state) {
        return state.videos
    },

    paging(state) {
        return state.paging
    },

    videosCount(state) {
        return state.videosCount;
    },

    limit(state) {
        return state.limit
    },

    videoSort(state) {
        return state.videoSort
    },

    videoSortMode(state) {
        return state.videoSortMode
    },

    search(state) {
        return state.search
    },

    availableVideoTags(state) {
        return state.availableVideoTags
    },

    availableVideoCourses(state) {
        return state.availableVideoCourses
    },

    videoShares(state) {
        return state.videoShares;
    },

    courseVideosToCopy(state) {
        return state.courseVideosToCopy
    },

    videosReload(state) {
        return state.videosReload
    },

    showEpisodesDefaultVisibilityDialog(state) {
        return state.showEpisodesDefaultVisibilityDialog
    }
}

const actions = {
    async loadVideos({ commit, state, dispatch, rootState }, data)
    {
        let filters = data.filters;

        const params = new URLSearchParams();

        if (!filters['order']) {
            params.append('order',  state.videoSort.field + "_" + state.videoSort.order);
        }

        if (!filters['offset']) {
            params.append('offset', state.paging.currPage * state.limit);
        }

        if (!filters['limit']) {
            params.append('limit',  state.limit);
        }

        for (let key in filters) {
            if (key === 'filters') {
                params.append('filters', JSON.stringify(filters.filters));
            } else {
                params.append(key, filters[key]);
            }
        }

        return ApiService.get(data.route, { params })
            .then(({ data }) => {
                commit('setVideos', data.videos);

                if (data.count !== undefined) {
                    commit('setVideosCount', data.count);
                    commit('updatePaging', {
                        currPage: state.paging.currPage,
                        items   : data.count
                    });
                }
            });
    },

    async loadMyVideos({ commit, state, dispatch, rootState }, data = [])
    {
        return dispatch('loadVideos', {
            route: 'videos',
            filters: data,
        })
        .then(async () => {
            await dispatch('loadAvailableVideoTags');
            await dispatch('loadAvailableVideoCourses');
        });
    },

    async loadPlaylistVideos({ commit, state, dispatch, rootState }, data)
    {
        return dispatch('loadVideos', {
            route: 'playlists/' + data.token + '/videos',
            filters: data,
        })
        .then(async () => {
            await dispatch('loadAvailableVideoTags', {token: data.token, cid: data.cid});
            await dispatch('loadAvailableVideoCourses', {token: data.token, cid: data.cid});
        });
    },

    async loadCourseVideos({ commit, state, dispatch, rootState }, data)
    {
        return dispatch('loadVideos', {
            route: 'courses/' + data.cid + '/videos',
            filters: data,
        })
        .then(async () => {
            await dispatch('loadAvailableVideoTags', {cid: data.cid});
        });
    },

    async loadAvailableVideoTags({ commit, state, dispatch, rootState }, data = []) {
        const params = new URLSearchParams();
        let route = '/tags/videos';

        if (data.token) {
            route += '/playlist/' + data.token;
            if (data.cid) {
                params.append('cid',  data.cid);
            }
        } else if (data.cid) {
            // Load tags of course videos
            route += '/course/' + data.cid;
        }

        return ApiService.get(route, { params })
            .then(({ data }) => {
                commit('setAvailableVideoTags', data);
            });
    },

    async loadAvailableVideoCourses({ commit, state, dispatch, rootState }, data = []) {
        const params = new URLSearchParams();
        let route = '/courses/videos';

        if (data.token) {
            route += '/playlist/' + data.token;
            if (data.cid) {
                params.append('cid',  data.cid);
            }
        }

        return ApiService.get(route, { params })
            .then(({ data }) => {
                commit('setAvailableVideoCourses', data);
            });
    },

    async uploadSortPositions({}, data) {
        return ApiService.put('playlists/' + data.playlist_token + '/positions', data.sortedVideos)
    },

    async createVideo(context, event) {
        let $cid = context?.rootState?.opencast?.cid ?? null;
        return ApiService.post('videos/' + event.episode, {event: event, 'course_id': $cid});
    },

    async deleteVideo(context, token) {
        return ApiService.delete('videos/' + token);
    },

    async restoreVideo(context, token) {
        return ApiService.put('videos/' + token + '/restore');
    },

    async updateVideo(context, event) {
        return ApiService.put('videos/' + event.token, {event: event});
    },

    async updateVideoVisibility(context, data) {
        return ApiService.put('videos/' + data.token +'/worldwide_share', {visibility: data.visibility});
    },

    async reportVideo(context, data) {
        return ApiService.post('videos/' + data.token + '/report', {description: data.description});
    },

    async setVideoSort({dispatch, commit}, sort) {
        await commit('setVideoSort', sort)
    },

    async loadVideoShares({ rootState, commit }, token) {
        return ApiService.get('videos/' + token + '/shares')
            .then(({ data }) => {
                commit('setShares', data)
            });
    },

    async updateVideoShares({ rootState }, data) {
        let params = {
            'data': data.shares,
        }
        return ApiService.put('videos/' + data.token + '/shares', params);
    },

    setPage({commit}, page) {
        commit('setPage', page);
    },

    setVideoSortMode({dispatch, state, commit}, mode) {
        commit('setVideoSortMode', mode);
    },

    setCourseVideosToCopy({dispatch, state, commit}, videos) {
        commit('setCourseVideosToCopy', videos);
    },

    setVideosReload({commit}, mode) {
        commit('setVideosReload', mode)
    },

    toggleShowEpisodesDefaultVisibilityDialog({commit}, mode) {
        commit('setShowEpisodesDefaultVisibilityDialog', mode);
    },
}

const mutations = {
    setVideos(state, videos) {
        state.videos = videos;
    },

    setVideoSort(state, sort) {
        state.videoSort = sort
    },

    setVideoSortMode(state, mode) {
        state.videoSortList = {}
        state.videoSortMode = mode
    },

    setPage(state, page) {
        if (page >= 0 && page <= state.paging.lastPage) {
            state.paging.currPage = page;
        }
    },

    clearPaging(state) {
        state.paging = {
            currPage: 0,
            lastPage: 0,
            items: 0
        };
        state.currentPage = 1;
    },

    updatePaging(state, paging) {
        paging.lastPage = (paging.items == state.limit) ? 0 : Math.floor((paging.items - 1) / state.limit);
        state.paging = paging;
    },

    setVideosCount(state, videosCount) {
        state.videosCount = videosCount;
    },

    setLimit(state, limit) {
        state.limit = limit;
    },

    setShares(state, data) {
        state.videoShares = data;
    },

    setCourseVideosToCopy(state, videos) {
        state.courseVideosToCopy = videos;
    },

    setAvailableVideoTags(state, data) {
        state.availableVideoTags = data;
    },

    setAvailableVideoCourses(state, data) {
        state.availableVideoCourses = data;
    },

    setVideosReload(state, mode) {
        state.videosReload = mode;
    },

    setShowEpisodesDefaultVisibilityDialog(state, mode) {
        state.showEpisodesDefaultVisibilityDialog = mode;
    },
}

export default {
    state,
    getters,
    mutations,
    actions
}
