import ApiService from "@/common/api.service";

const state = {
    series: [],
    servers: [],
    currentUser: {},
    currentPage: 'videos',
    cid: null,
    site: null,
    axios_running: false,
    userCourses: [],
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
    axios_running(state) {
        return state.axios_running;
    },
    userCourses(state) {
        return state.userCourses
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
    },

    axiosStart({ commit }) {
        commit('setAxiosRunning', true);
    },

    axiosStop({ commit }) {
        commit('setAxiosRunning', false);
    },

    async loadUserCourses({ commit, dispatch}) {
        return ApiService.get('courses')
            .then(({ data }) => {
                commit('setUserCourses', data);
            });
    },
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

    setAxiosRunning(state, running) {
        state.axios_running = running;
    },

    setUserCourses(state, data) {
        state.userCourses = data;
    },
}


export default {
    state,
    getters,
    mutations,
    actions
}
