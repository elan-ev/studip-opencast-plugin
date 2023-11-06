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

    errorRemove(context, error) {
        context.commit('errorsRemove', error);
    },

    errorClear(context) {
        context.commit('errorsClear');
    }
};

const mutations = {
    errorsAdd(state, data) {
        state.errors.push(data);
    },

    errorsRemove(state, data) {
        let idx = state.errors.indexOf(data);
        if (idx !== -1) {
            state.errors.splice(idx, 1);
        }
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