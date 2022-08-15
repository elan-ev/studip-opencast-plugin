const initialState = {
    messages: [],
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

        let messages = state.messages;
        let current_message = messages.find(msg => msg.type == message.type && msg.text == message.text);
        if (!current_message) {
            message.id = messages.length + 1;
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
