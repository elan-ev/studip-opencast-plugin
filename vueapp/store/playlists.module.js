import ApiService from "@/common/api.service";

const state = {
    playlists: {},
    addPlaylist: false,
    currentPlaylist: 'all'
}

const getters = {
    playlists(state) {
        return state.playlists
    },

    currentPlaylist(state) {
        return state.currentPlaylist
    },

    addPlaylist(state) {
        return state.addPlaylist;
    }
}


const actions = {
    async loadPlaylists(context) {
        context.dispatch('updateLoading', true);
        let $cid = context.rootState.opencast.cid;
        let $route = ($cid == null) ? 'playlists' : 'courses/' + $cid + '/playlist';

        return ApiService.get($route)
            .then(({ data }) => {
                context.dispatch('updateLoading', false);
                context.commit('addPlaylists', data);
            });
    },

    addPlaylistUI({ commit }, show) {
        commit('setPlaylistAdd', show);
    },

    async addPlaylist(context, id) {
        // TODO
        commit('setPlaylistAdd', false);
    },

    async deletePlaylist(context, id) {
        // TODO
    },

    async setCurrentPlaylist(context, token) {
        context.commit("setCurrentPlaylist", token);
    }
}

const mutations = {
    addPlaylists(state, playlists) {
        for (let id in playlists) {
            state.playlists[playlists[id].token] = playlists[id]
        }
    },


    setPlaylistAdd(state, show) {
        state.addPlaylist = show;
    },

    setCurrentPlaylist(state, token) {
        state.currentPlaylist = token;
    }
}


export default {
    state,
    getters,
    mutations,
    actions
}
