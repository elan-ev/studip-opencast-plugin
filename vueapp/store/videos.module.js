import ApiService from "@/common/api.service";

const state = {
    videos: {},
    videoSearch: '',
    videoSort: {
        field: 'mkdate',
        order: 'desc',
        text : 'Datum hochgeladen: Neuste zuerst'
    },
    videoSorts: [
        {
            field: 'mkdate',
            order: 'desc',
            text : 'Datum hochgeladen: Neuste zuerst'
        },  {
            field: 'mkdate',
            order: 'asc',
            text : 'Datum hochgeladen: Älteste zuerst'
        },  {
            field: 'title',
            order: 'asc',
            text : 'Titel: Alphabetisch'
        }, {
            field: 'title',
            order: 'desc',
            text : 'Titel: Umgekehrt Alphabetisch'
        }, {
            field: 'order',
            order: 'asc',
            text : 'Benutzerdefiniert'
        }, {
            field: 'order',
            order: 'desc',
            text : 'Benutzerdefiniert Umgekehrt'
        }
    ],
    videoSortMode: false,
    videoSortList: {},
    limit: 5,
    paging: {
        currPage: 0,
        lastPage: 0,
        items: 0
    },
    playlistForVideos: null
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

    videoSorts(state) {
        return state.videoSorts
    },

    videoSortMode(state) {
        return state.videoSortMode
    },

    search(state) {
        return state.search
    },

    playlistForVideos(state) {
        return state.playlistForVideos
    }
}

const actions = {
    async loadVideos({ commit, state, dispatch, rootState }, filters = []) {
        let $cid = rootState.opencast.cid;

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

        if ($cid) {
            params.append('cid', $cid);
        }

        if (filters.filters) {
            params.append('filters', JSON.stringify(filters.filters));
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

    async setVideoSearch({dispatch, commit}, search) {
        await commit('setVideoSearch', search)
        dispatch('loadVideos')
    },

    setPage({commit}, page) {
        commit('setPage', page);
    },

    setVideoSortMode({dispatch, state, commit}, mode) {
        commit('setVideoSort', state.videoSorts.find(sort => {
            return sort.field === 'order' && sort.order === 'asc';
        }));
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
        }
    },

    setVideoSearch(state, search) {
        state.videoSearch = search
    },

    updatePaging(state, paging) {
        paging.lastPage = (paging.items == state.limit) ? 0 : Math.floor((paging.items / state.limit));
        state.paging = paging;
    },

    setPlaylistForVideos(state, playlist) {
        state.playlistForVideos = playlist;
    }
}

export default {
    state,
    getters,
    mutations,
    actions
}
