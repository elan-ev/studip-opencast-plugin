import ApiService from "@/common/api.service";

const state = {
    series: [],
    servers: [],
    currentUser: {},
    currentUserSeries: '',
    currentView: 'videos',
    cid: null,
    site: null,
    axios_running: false,
    userCourses: [],
    userList: []
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
    currentUserSeries(state) {
        return state.currentUserSeries
    },
    currentView(state) {
        return state.currentView
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
        return state.userCourses;
    },
    userList(state) {
        return state.userList;
    }
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

    async loadSeries({commit }, id) {
        return ApiService.get('opencast/allseries/' + id)
            .then(({ data }) => {
                commit('setSeries', data.series);
            });
    },

    async loadServers({ commit }) {
        return ApiService.get('opencast/servers')
            .then(({ data }) => {
                commit('setServers', data.servers);
            });
    },

    async loadCurrentUser({ commit }) {
        return ApiService.get('user')
            .then(({ data }) => {
                commit('setCurrentUser', data.data);
            });
    },

    async loadCurrentUserSeries({ commit }) {
        return ApiService.get('user/series')
            .then(({ data }) => {
                commit('setCurrentUserSeries', data.series_id);
            });
    },

    async loadUserList({ commit }, search_term) {
        return ApiService.get('user/search/' + encodeURIComponent(search_term))
            .then(({ data }) => {
                commit('setUserList', data.users);
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

    updateView({ commit, dispatch }, view) {
        commit('setView', view);
        commit('clearPaging');
    },

    async authenticateLti({ dispatch }) {
        // by reloading the simple config, LtiAuth.vue reloads the iframe for lti authentication
        return dispatch('simpleConfigListRead');
    },

    setVisibility({ commit }, data) {
        return ApiService.put('courses/' + data.cid + '/visibility/' + data.visibility);
    },

    setUpload({ commit }, data) {
        return ApiService.put('courses/' + data.cid + '/upload/' + data.upload);
    }
}

const mutations = {
    setCid(state, cid) {
        state.cid = cid;
    },

    setSite(state, site) {
        state.site = site;
    },

    setView(state, view) {
        state.currentView = view;
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

    setCurrentUserSeries(state, data) {
        state.currentUserSeries = data;
    },

    setAxiosRunning(state, running) {
        state.axios_running = running;
    },

    setUserCourses(state, data) {
        state.userCourses = data;
    },

    setUserList(state, data) {
        state.userList = data;
    },
}


export default {
    state,
    getters,
    mutations,
    actions
}
