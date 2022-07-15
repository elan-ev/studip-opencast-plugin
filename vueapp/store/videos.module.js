import ApiService from "@/common/api.service";

const state = {
    videos: {},
    pagedVideos: {},
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
    async loadVideos({ commit, state }) {
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

        return ApiService.get('videos', { params })
            .then(({ data }) => {
                commit('addVideos', data.videos);

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
    addVideos(state, videos){
        state.pagedVideos[state.paging.currPage] = {};

        for (let i = 0; i < videos.length; i++) {
            let video = videos[i];
            state.pagedVideos[state.paging.currPage][video.token] = video;
        }

        state.videos = state.pagedVideos[state.paging.currPage];
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
