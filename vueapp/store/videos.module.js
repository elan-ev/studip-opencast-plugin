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
            order: 'desc',
            text : 'Titel: Alphabetisch'
        }, {
            field: 'title',
            order: 'asc',
            text : 'Titel: Umgekehrt Alphabetisch'
        }
    ],
    limit: 5,
    paging: {
        currPage: 0,
        lastPage: 0,
        items: 0
    }
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

    search(state) {
        return state.search
    }
}

const actions = {
    async loadVideos({ commit, state, dispatch, rootState }) {
        let playlist_token = rootState.playlists.currentPlaylist

        dispatch('updateLoading', true);

        const params = new URLSearchParams();

        params.append('offset', state.paging.currPage * state.limit);
        params.append('limit', state.limit);

        if (playlist_token !== 'all') {
            params.append('filters', JSON.stringify([{
                'type': 'playlist',
                'value': playlist_token
                }
            ]));
        }

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

        return ApiService.get('videos', { params })
            .then(({ data }) => {
                commit('addVideos', {'videos': data.videos, 'playlist_token': playlist_token});

                if (data.count) {
                    commit('updatePaging', {
                        currPage: state.paging.currPage,
                        items   : data.count
                    });
                }

                dispatch('updateLoading', false);
            });
    },

    async deleteVideo(context, id) {
        // TODO
    },

    async setVideoSort({dispatch, commit}, sort) {
        await commit('setVideoSort', sort)
        dispatch('reloadVideos')
    },

    async setVideoSearch({dispatch, commit}, search) {
        await commit('setVideoSearch', search)
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

    setVideoSort(state, sort) {
        state.videoSort = sort
    },

    setPage(state, page) {
        if (page >= 0 && page <= state.paging.lastPage) {
            state.paging.currPage = page
        }
    },

    setVideoSearch(state, search) {
        state.videoSearch = search
    },

    updatePaging(state, paging) {
        paging.lastPage = (paging.items == state.limit) ? 0 : Math.floor((paging.items / state.limit));
        state.paging = paging;
    }
}


export default {
    state,
    getters,
    mutations,
    actions
}
