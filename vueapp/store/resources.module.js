import Vue from "vue";
import ApiService from "@/common/api.service";

import {
    RESOURCES_READ,
    RESOURCES_UPDATE
} from "./actions.type";

import {
    RESOURCES_SET
} from "./mutations.type";

const initialState = {
    resources: {}

};

const getters = {
    resources(state) {
        return state.resources;
    },
};

export const state = { ...initialState };

export const actions = {
    async [RESOURCES_READ](context) {
        return new Promise(resolve => {
          ApiService.get('resources')
            .then(({ data }) => {
                context.commit(RESOURCES_SET, data);
                resolve(data);
            });
        });
    },

    async [RESOURCES_UPDATE](context, params) {
        return ApiService.update('resources', {
            resources: params
        });
    },
};

/* eslint no-param-reassign: ["error", { "props": false }] */
export const mutations = {
    [RESOURCES_SET](state, data) {
        state.resources = data;
    },
};

export default {
  state,
  actions,
  mutations,
  getters
};
