import ApiService from "@/common/api.service";

const state = {
    videos: {},
    pagedAllVideos: {},
    pagedPlaylistVideos: {},
    search: '',
    sort: {
        field: 'mkdate',
        order: 'desc'
    },
    limit: 5,
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
        let playlist_token = rootState.courses.currentPlaylist
        let route = (playlist_token == null) ? 'videos' : 'playlists/' + playlist_token + '/videos';
        
        state.loadingPage = true;

        const params = new URLSearchParams();

        params.append('offset', state.paging.currPage * state.limit);
        params.append('limit', state.limit);
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

    async setSort({dispatch, commit}, sort) {
        await commit('setSort', sort)
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
        let videos_ref = {};

        for (let i = 0; i < videos.length; i++) {
            let video = videos[i];
            videos_ref[video.token] = video;
        }

        if (playlist_token == null) {
            state.pagedAllVideos[state.paging.currPage] = {};
            for (let i = 0; i < videos.length; i++) {
                let video = videos[i];
                state.pagedAllVideos[state.paging.currPage][video.token] = video;
            }
            state.videos = state.pagedAllVideos[state.paging.currPage];
        }
        else {
            if (state.pagedPlaylistVideos[playlist_token] === undefined) {
                state.pagedPlaylistVideos[playlist_token] = {}
            }
            state.pagedPlaylistVideos[playlist_token][state.paging.currPage] = {};
            for (let i = 0; i < videos.length; i++) {
                let video = videos[i];
                state.pagedPlaylistVideos[playlist_token][state.paging.currPage][video.token] = video;
            }
            state.videos = state.pagedPlaylistVideos[playlist_token][state.paging.currPage];
        }
    },

    setSort(state, sort) {
        state.sort = sort
    },

    setPage(state, page) {
        if (page >= 0 && page <= state.paging.lastPage) {
            state.paging.currPage = page

            if (state.pagedVideos[state.paging.currPage] !== undefined) {
                state.videos = state.pagedVideos[state.paging.currPage];
            } else {
                state.videos = {};
            }
        }
        state.videos = state.allVideos
    },

    updatePaging(state, paging) {
        paging.lastPage = Math.floor(paging.items / state.limit);
        state.paging = paging;
    }
}


export default {
    state,
    getters,
    mutations,
    actions
}
