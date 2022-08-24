import ApiService from "@/common/api.service";

const state = {
    series: [],
    servers: [],
    currentUser: {},
    currentPage: 'videos',
    loading: false,
    cid: null,
    site: null
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
    currentPage(state) {
        return state.currentPage
    },
    cid(state) {
        return state.cid;
    },
    site(state) {
        return state.site;
    },
    loading(state) {
        return state.loading
    },
}


const actions = {
    updateCid({commit}, cid) {
        commit('setCid', cid);
    },

    updateSite({commit}, site) {
        commit('setSite', site);
    },

    updatePage({commit}, page) {
        commit('setPage', page);
    },

    updateLoading({commit}, loading) {
        commit('setLoading', loading);
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

    setSite(state, site) {
        state.site = site;
    },

    setPage(state, page) {
        state.currentPage = page;
    },

    setSeries(state, data) {
        state.series = data;
    },

    setServers(state, data) {
        state.servers = data;
    },

    setCurrentUser(state, data) {
        state.currentUser = data;
    },

    setLoading(state, loading) {
        state.loading = loading;
    }
}


export default {
    state,
    getters,
    mutations,
    actions
}
