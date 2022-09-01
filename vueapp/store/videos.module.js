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
        }
    ],
    videoSortMode: false,
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

    videoSortMode(state) {
        return state.videoSortMode
    },

    search(state) {
        return state.search
    }
}

const actions = {
    async loadVideos({ commit, state, dispatch, rootState }, filters) {
        let playlist_token = rootState.playlists.currentPlaylist
        let $cid = rootState.opencast.cid;

        let page_from = state.paging.currPage
        let preload = state.videoSortMode

        dispatch('updateLoading', true);

        const params = new URLSearchParams();

        params.append('order', state.videoSort.field + "_" + state.videoSort.order)
        params.append('offset', state.paging.currPage * state.limit)
        
        if (preload) {
            if (state.paging.currPage > 0) {
                page_from--
                params.append('limit', state.limit*3)
            }
            else {
                params.append('limit', state.limit*2)
            }
        } else {
            params.append('limit', state.limit);
        }

        if (playlist_token !== 'all') {
            filters.append({
                'type': 'playlist',
                'value': playlist_token
            });
        } else if ($cid) {
            params.append('cid', $cid);
        }

        params.append('filters', JSON.stringify(filters));

        return ApiService.get('videos', { params })
            .then(({ data }) => {
                commit('addVideos', {'videos': data.videos, 'playlist_token': playlist_token, 'page_from': page_from});

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
        dispatch('loadVideos')
    },

    async setVideoSearch({dispatch, commit}, search) {
        await commit('setVideoSearch', search)
        dispatch('loadVideos')
    },

    setPage({commit}, page) {
        commit('setPage', page);
    },

    setVideoSortMode({commit}, mode) {
        commit('setVideoSortMode', mode);
    }
}

const mutations = {
    addVideos(state, payload) {
        let videos = payload.videos;
        let playlist_token = payload.playlist_token;
        let page_from = payload.page_from;

        if (state.videos[playlist_token] === undefined) {
            state.videos[playlist_token] = {}
        }
        for (let i=0; i<videos.length/state.limit; i++) {
            if(!state.videoSortMode || state.videos[playlist_token][page_from+i] === undefined) {
                state.videos[playlist_token][page_from+i] = videos.slice(i*state.limit, (i+1)*state.limit);
            }
        }
    },

    setVideoSort(state, sort) {
        state.videoSort = sort
    },

    setVideoSortMode(state, mode) {
        state.videoSortMode = mode
    },

    setPage(state, page) {
        if (page >= 0 && page <= state.paging.lastPage) {
            state.paging.currPage = page;
        }
    },

    setVideoSearch(state, search) {
        state.videoSearch = search
    },

    updatePaging(state, paging) {
        paging.lastPage = (paging.items == state.limit) ? 0 : Math.floor((paging.items / state.limit));
        state.paging = paging;
    },
}


export default {
    state,
    getters,
    mutations,
    actions
}
