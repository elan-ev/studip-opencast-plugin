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
        /*
        return ApiService.get('resources')
            .then(({ data }) => {
                context.commit('setServers', data);
            });
        */
    },

    async loadServers({ commit, dispatch}, id) {
        return ApiService.get('lti/servers')
            .then(({ data }) => {
                commit('setServers', data);
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
