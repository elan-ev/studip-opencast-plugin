import ApiService from "@/common/api.service";

const state = {
    errors: []
};

const getters = {
    errors(state) {
        return state.errors;
    }
};

const actions = {
    errorCommit(context, error) {
        context.commit('errorsAdd', error);
    },

    errorClear(context) {
        context.commit('errorsClear');
    }
};

const mutations = {
    errorsAdd(state, data) {
        state.errors.push(data);
    },

    errorsClear(state) {
        state.errors = [];
    }
};

export default {
  state,
  actions,
  mutations,
  getters
};
