import ApiService from "@/common/api.service";

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
    async resourcesRead(context) {
        return new Promise(resolve => {
          ApiService.get('resources')
            .then(({ data }) => {
                context.commit(RESOURCES_SET, data);
                resolve(data);
            });
        });
    },

    async resourcesUpdate(context, params) {
        return ApiService.update('resources', {
            resources: params
        });
    },
};

/* eslint no-param-reassign: ["error", { "props": false }] */
export const mutations = {
    resourcesSet(state, data) {
        state.resources = data;
    },
};

export default {
  state,
  actions,
  mutations,
  getters
};
