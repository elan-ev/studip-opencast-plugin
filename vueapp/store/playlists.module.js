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
                context.commit('setPlaylists', data);
            });
    },

    async loadPlaylist(context, token) {
        return ApiService.get('playlists/' + token)
            .then(({ data }) => {
                context.commit('setPlaylists', [data]);
            });
    },

    addPlaylistUI({ commit }, show) {
        commit('setPlaylistAdd', show);
    },

    async addPlaylist({ commit, dispatch, rootState }, playlist) {
        // TODO
        commit('setPlaylistAdd', false);

        let $cid = rootState.opencast.cid;
        let $route = ($cid == null) ? 'playlists' : 'courses/' + $cid + '/playlist';

        return ApiService.post($route, playlist)
            .then(({ data }) => {
                dispatch('loadPlaylists');
            });
    },

    async deletePlaylist(context, id) {
        // TODO
    },

    async setCurrentPlaylist(context, token) {
        context.commit("setCurrentPlaylist", token);
    }
}

const mutations = {
    setPlaylists(state, playlists) {
        let pl = playlists || {};

        for (let id in pl) {
            state.playlists[pl[id].token] = pl[id];
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
