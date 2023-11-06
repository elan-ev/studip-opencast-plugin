const initialState = {
    messages: [],
    message_max_id: 0,
    message_num: 1
};

const getters = {
    messages(state) {
        return state.messages;
    },
    message_num(state) {
        return state.message_num;
    }
};

export const state = { ...initialState };

export const actions = {
    addMessage(context, message) {
        if (!message.text || !message.type) {
            return false;
        }

        // Remove error messages if success message is displayed
        if (message.type === "success") {
            context.dispatch('errorClear');
        }

        let messages = state.messages;
        let current_message = messages.find(msg => msg.type == message.type && msg.text == message.text);
        if (!current_message) {
            context.commit('incrementMessageMaxId')
            message.id = state.message_max_id;
            context.commit('setMessage', message);
        }
    },

    clearMessages(context) {
        context.commit('setMessages', []);
    }
};

/* eslint no-param-reassign: ["error", { "props": false }] */
export const mutations = {
    setMessage(state, message) {
        state.messages.push(message);
    },

    setMessages(state, messages) {
        state.messages = messages;
    },

    incrementMessageMaxId(state) {
        state.message_max_id++;
    },

    removeMessage(state, id) {
        let message_index = state.messages.findIndex(msg => msg.id == id);
        if (message_index != -1) {
            state.messages.splice(message_index, 1);
        }
    }
};

export default {
  state,
  actions,
  mutations,
  getters
};
