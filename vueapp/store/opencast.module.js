import ApiService from "@/common/api.service";

const state = {
    series: [],
    servers: [],
    currentUser: {},
    cid: null
}

const getters = {
    series(state) {
        return state.series
    },
    servers(state) {
        return state.servers
    },
    currentUser(state) {
        return state.currentUser
    },
    cid(state) {
        return state.cid;
    }
}


const actions = {
    updateCid({commit}, cid) {
        commit('setCid', cid);
    },

    async loadSeries({commit, dispatch}, id) {
        return ApiService.get('opencast/allseries/' + id)
            .then(({ data }) => {
                commit('setSeries', data.series);
            });
    },

    async loadServers({ commit, dispatch}) {
        return ApiService.get('opencast/servers')
            .then(({ data }) => {
                commit('setServers', data.servers);
            });
    },

    async loadCurrentUser({ commit, dispatch}) {
        return ApiService.get('user')
            .then(({ data }) => {
                commit('setCurrentUser', data.data);
            });
    }
}

const mutations = {
    setCid(state, cid) {
        state.cid = cid;
    },

    setSeries(state, data) {
        state.series = data;
    },

    setServers(state, data) {
        state.servers = data;
    },

    setCurrentUser(state, data) {
        state.currentUser = data;
    }
}


export default {
    state,
    getters,
    mutations,
    actions
}
