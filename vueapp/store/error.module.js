import ApiService from "@/common/api.service";

const state = {
    error: null
};

const getters = {
    error(state) {
        return state.error;
    }
};

const actions = {
    errorCommit(context, error) {
        context.commit('errorSet', error);
    },

    errorClear(context) {
        context.commit('errorSet', null);
    }
};

const mutations = {
    errorSet(state, data) {
        state.error = data;
    }
};

export default {
  state,
  actions,
  mutations,
  getters
};
