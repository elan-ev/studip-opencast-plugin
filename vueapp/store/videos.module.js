import ApiService from "@/common/api.service";

const state = {
    videos: {},
    search: '',
    sort: {
        field: 'mkdate',
        order: 'desc'
    },
    limit: 3,
    paging: {
        currPage: 0,
        lastPage: 0,
        items: 0
    },
    loadingPage: true
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

    sort(state) {
        return state.sort
    },

    search(state) {
        return state.search
    },

    loadingPage(state) {
        return state.loadingPage
    },
}

const actions = {
    async loadVideos({ commit, state, rootState }) {
        let playlist_token = rootState.playlists.currentPlaylist
        let route = (playlist_token == 'all') ? 'videos' : 'playlists/' + playlist_token + '/videos';

        state.loadingPage = true;

        const params = new URLSearchParams();

        params.append('offset', state.paging.currPage * state.limit);
        params.append('limit', state.limit);
        /*
        params.append('filters', JSON.stringify([{
                'type': 'text',
                'value': 'test'
            }, {
                'type': 'tag',
                'value': 'php'
            }, {
                'type': 'tag',
                'value': 'mathematik'
            }
        ]));
        */

        return ApiService.get(route, { params })
            .then(({ data }) => {
                commit('addVideos', {'videos': data.videos, 'playlist_token': playlist_token});

                if (data.count) {
                    commit('updatePaging', {
                        currPage: state.paging.currPage,
                        items   : data.count
                    });
                }

                state.loadingPage = false;
            });
    },

    async deleteVideo(context, id) {
        // TODO
    },

    async setSort({dispatch, commit}, sort) {
        await commit('setSort', sort)
        dispatch('reloadVideos')
    },

    async setSearch({dispatch, commit}, search) {
        await commit('setSearch', search)
        dispatch('reloadVideos')
    },

    setPage({commit}, page) {
        commit('setPage', page);
    },

}

const mutations = {
    addVideos(state, payload){
        let videos = payload.videos;
        let playlist_token = payload.playlist_token;

        if (state.videos[playlist_token] === undefined) {
            state.videos[playlist_token] = {}
        }
        if (state.videos[playlist_token][state.paging.currPage] === undefined) {
            state.videos[playlist_token][state.paging.currPage] = {};
        }
        for (let i = 0; i < videos.length; i++) {
            let video = videos[i];
            state.videos[playlist_token][state.paging.currPage][video.token] = video;
        }
    },

    setSort(state, sort) {
        state.sort = sort
    },

    setPage(state, page) {
        if (page >= 0 && page <= state.paging.lastPage) {
            state.paging.currPage = page
        }
    },

    updatePaging(state, paging) {
        paging.lastPage = Math.round((paging.items / state.limit)-1);
        state.paging = paging;
    }
}


export default {
    state,
    getters,
    mutations,
    actions
}
