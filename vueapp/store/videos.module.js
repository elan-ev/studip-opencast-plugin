import ApiService from "@/common/api.service";

const state = {
    videos: {},
    videoSearch: '',
    videoSort: {
        field: 'mkdate',
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
    playlistForVideos: null,
    filters: []
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

    playlistForVideos(state) {
        return state.playlistForVideos
    },

    filters(state) {
        return state.filters;
    }
}

const actions = {
    async loadVideos({ commit, state, dispatch, rootState }, filters = []) {
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

        return ApiService.get('videos', { params })
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

    async uploadSortPositions({}, data) {
        return ApiService.put('playlists/' + data.playlist_token + '/positions', data.sortedVideos)
    },

    async deleteVideo(context, token) {
        return ApiService.delete('videos/' + token);
    },

    async updateVideo(context, event) {
        return ApiService.put('videos/' + event.token, {event: event});
    },

    async reportVideo(context, data) {
        return ApiService.post('videos/' + data.token + '/report', {description: data.description});
    },

    async addVideoToCourses(context, data) {
        return ApiService.post('videos/' + data.token + '/courses', {courses: data.courses});
    },

    async setVideoSort({dispatch, commit}, sort) {
        await commit('setVideoSort', sort)
        dispatch('loadVideos')
    },

    setPage({commit}, page) {
        commit('setPage', page);
    },

    setVideoSortMode({dispatch, state, commit}, mode) {
        commit('setVideoSort', {
            field: 'order',
            order: 'asc',
            text : 'Benutzerdefiniert'
        });

        commit('setVideoSortMode', mode);
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

    setFilters(state, filters) {
        state.filters = filters;
    },

    setLimit(state, limit) {
        state.limit = limit;
    }
}

export default {
    state,
    getters,
    mutations,
    actions
}
