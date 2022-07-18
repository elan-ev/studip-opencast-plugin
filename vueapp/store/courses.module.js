import ApiService from "@/common/api.service";

const state = {
    playlists: {},
    currentPlaylist: 'all'
}

const getters = {
    playlists(state) {
        return state.playlists
    },

    currentPlaylist(state) {
        return state.currentPlaylist
    }
}


const actions = {
    async loadPlaylists(context) {
        return ApiService.get('courses/' + context.rootState.opencast.cid + '/playlist')
            .then(({ data }) => {
                context.commit('addPlaylists', data);
            });
    },

    async addPlaylist(context, id) {
        // TODO
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
