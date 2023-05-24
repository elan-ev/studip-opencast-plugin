import ApiService from "@/common/api.service";

const state = {
    videos: {},
    videoSearch: '',
    videoSort: {
        field: 'created',
        order: 'desc',
        text : 'Datum hochgeladen: Neueste zuerst'
    },
    videoSortMode: false,
    videoSortList: {},
    limit: 15,
    paging: {
        currPage: 0,
        lastPage: 0,
        items: 0
    },
    availableVideoTags: [],
    playlistForVideos: null,
    videoShares: {},
    courseVideosToCopy: [],
    showCourseCopyDialog: false,
}

const getters = {
    videos(state) {
        return state.videos
    },

    paging(state) {
        return state.paging
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

    playlistForVideos(state) {
        return state.playlistForVideos
    },

    videoShares(state) {
        return state.videoShares;
    },

    courseVideosToCopy(state) {
        return state.courseVideosToCopy
    },

    showCourseCopyDialog(state) {
        return state.showCourseCopyDialog
    },
}

const actions = {
    async loadVideos({ commit, state, dispatch, rootState }, data)
    {
        let filters = data.filters;

        const params = new URLSearchParams();

        params.append('order',  state.videoSort.field + "_" + state.videoSort.order);

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

                if (data.count) {
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
        .then(() => dispatch('loadAvailableVideoTags'));
    },

    async loadPlaylistVideos({ commit, state, dispatch, rootState }, data)
    {
        return dispatch('loadVideos', {
            route: 'playlists/' + data.token + '/videos',
            filters: data,
        })
        .then(() => dispatch('loadAvailableVideoTags', {token: data.token, cid: data.cid}));
    },

    async loadAvailableVideoTags({ commit, state, dispatch, rootState }, data = []) {
        const params = new URLSearchParams();
        let route = '/tags/videos';

        if (data.token) {
            route += '/playlist/' + data.token;
            if (data.cid) {
                params.append('cid',  data.cid);
            }
        }

        return ApiService.get(route, { params })
            .then(({ data }) => {
                commit('setAvailableVideoTags', data);
            });
    },

    async uploadSortPositions({}, data) {
        return ApiService.put('playlists/' + data.playlist_token + '/positions', data.sortedVideos)
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

    async reportVideo(context, data) {
        return ApiService.post('videos/' + data.token + '/report', {description: data.description});
    },

    async addVideoToPlaylists(context, data) {
        return ApiService.post('videos/' + data.token + '/playlists', {playlists: data.playlists});
    },

    async copyVideosToCourses(context, data) {
        return ApiService.post('videos/' + data.cid + '/copy',
            {
                courses: data.courses,
                tokens: data?.tokens ?? [],
                type: data.type,
            }
        );
    },

    async setVideoSort({dispatch, commit}, sort) {
        await commit('setVideoSort', sort)
    },

    async loadVideoShares({ commit }, token) {
        return ApiService.get('videos/' + token + '/shares')
            .then(({ data }) => {
                commit('setShares', data)
            });
    },

    async updateVideoShares({}, data) {
        return ApiService.put('videos/' + data.token + '/shares', data.shares);
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

    toggleCourseCopyDialog({dispatch, state, commit}, mode) {
        commit('setShowCourseCopyDialog', mode);
    }
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

    setPlaylistForVideos(state, playlist) {
        state.playlistForVideos = playlist;
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

    setShowCourseCopyDialog(state, mode) {
        state.showCourseCopyDialog = mode;
    },

    setAvailableVideoTags(state, data) {
        state.availableVideoTags = data;
    }
}

export default {
    state,
    getters,
    mutations,
    actions
}
