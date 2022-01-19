import Vue from "vue";
import ApiService from "@/common/api.service";

const initialState = {
    config_list: [],
    config: {
        'url' :      null,
        'user':      null,
        'password':  null,
        'ltikey':    null,
        'ltisecret': null
    }
};

const getters = {
    config_list(state) {
        return state.config_list;
    },
    config(state) {
        return state.config;
    }
};

export const state = { ...initialState };

export const actions = {
    async configListRead(context) {
        return new Promise(resolve => {
          ApiService.get('config')
            .then(({ data }) => {
                context.commit('configListSet', data);
                resolve(data);
            });
        });
    },

    async configRead(context, id) {
        return ApiService.get('config/' + id)
            .then(({ data }) => {
                context.commit('configSet', data.config);
            });
    },

    async configDelete(context, id) {
        await ApiService.delete('config/' + id);
        context.dispatch('configListRead');
    },

    async configUpdate(context, params) {
        return ApiService.update('config', params.id, {
            config: params
        });
    },

    async configCreate(context, params) {
        return await ApiService.post('config', {
            config: params
        });
    },

    configClear(context) {
        context.commit('configSet', {});
    },

    configListClear(context) {
        context.commit('configListSet', {});
    },
};

/* eslint no-param-reassign: ["error", { "props": false }] */
export const mutations = {
    configListSet(state, data) {
        state.config_list = data;
    },

    configSet(state, data) {
        state.config = data;
    }
};

export default {
  state,
  actions,
  mutations,
  getters
};
