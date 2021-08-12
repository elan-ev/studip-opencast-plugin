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
        context.commit(ERROR_SET, error);
    },

    errorClear(context) {
        context.commit(ERROR_SET, null);
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
