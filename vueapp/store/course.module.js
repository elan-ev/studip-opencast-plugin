import gql from 'graphql-tag'
import { apolloClient, apolloProvider } from '../vue-apollo'

import ApiService from "@/common/api.service";


const state = {
    course_series: [],
}

const getters = {
    course_series(state) {
        return state.course_series
    }
}


const actions = {
    async loadCourseSeries({ commit }) {
        return ApiService.get('course/series/' + CID)
            .then(({ data }) => {
                commit('setCourseSeries', data.series);
            });
    },

    async addCourseSeries({ commit, state }, data) {
        return ApiService.post('course/series/' + CID, {
            series_id: data.series_id,
            config_id: data.config_id
        }).then(({ data }) => {
            let series = state.course_series;
            series.push(data.series);
            commit('setCourseSeries', series);
        });
    }
}

const mutations = {
    setCourseSeries(state, data) {
        state.course_series = data;
    }
}


export default {
    state,
    getters,
    mutations,
    actions
}
