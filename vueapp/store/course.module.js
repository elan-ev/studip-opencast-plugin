import gql from 'graphql-tag'
import { apolloClient, apolloProvider } from '../vue-apollo'

import ApiService from "@/common/api.service";


const state = {
    course_series: [],
    upload_xml: null
}

const getters = {
    course_series(state) {
        return state.course_series
    },

    upload_xml(state) {
        return state.upload_xml
    }
}


const actions = {
    async loadCourseSeries({ commit }) {
        return ApiService.get('course/series/' + CID)
            .then(({ data }) => {
                commit('setCourseSeries', data.series);
            });
    },

    async addCourseSeries({ commit, state, dispatch }, data) {
        return ApiService.post('course/series/' + CID, {
            series_id: data.series_id,
            config_id: data.config_id
        }).then(({ data }) => {
            let series = state.course_series;
            series.push(data.series);
            commit('setCourseSeries', series);

            // update episode list
            dispatch('reloadEvents');
        });
    },

    async removeCourseSeries({ commit, state, dispatch }, series_id) {
        // optimistic removal of series from list
        let series = state.course_series;
        let new_series = [];

        for (let i in series) {
            if (series[i].series_id != series_id) {
                new_series.push(series[i]);
            }
        }

        commit('setCourseSeries', new_series);

        // now try to get the server to cooperate
        return ApiService.delete('course/series/'
            + CID + '/' + series_id)
        .then(({ data }) => {
            // after the call reload the series to make sure our UI is up to date
            dispatch('loadCourseSeries');

            // update episode list
            dispatch('reloadEvents');
        });
    },

    async loadUploadXML({ commit }) {
        return ApiService.get('course/upload_xml/' + CID)
            .then(({ data }) => {
                commit('setUploadXML', data.oc_acl);
            });
    },
}

const mutations = {
    setCourseSeries(state, data) {
        state.course_series = data;
    },

    setUploadXML(state, data) {
        state.upload_xml = data;
    }
}


export default {
    state,
    getters,
    mutations,
    actions
}
