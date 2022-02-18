import gql from 'graphql-tag'
import { apolloClient, apolloProvider } from '../vue-apollo'

import ApiService from "@/common/api.service";


const state = {
    series: [],
    servers: []
}

const getters = {
    series(state) {
        return state.series
    },
    servers(state) {
        return state.servers
    }
}


const actions = {

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
    }
}

const mutations = {

    setSeries(state, data) {
        state.series = data;
    },

    setServers(state, data) {
        state.servers = data;
    }
}


export default {
    state,
    getters,
    mutations,
    actions
}
