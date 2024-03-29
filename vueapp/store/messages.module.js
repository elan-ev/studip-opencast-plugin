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
        // Handle axios error messages
        if (message.data) {
            let text = "";
            if (message.data.errors) {
                message.data.errors.forEach(err => {
                    text += err.code + ": " + err.title + ". ";
                });
            }
            if (message.data.message) {
                text += message.status + ": " + message.data.message + " (" + message.config.method + ": " + message.config.baseURL + "/" + message.config.url + "). ";
            }
            if (message.data.error) {
                message.data.error.forEach(err => {
                    text += err.message + ": " + "Line " + err.line + " in file " + err.file + ". ";
                });
            }

            message.type = "error";
            message.global = true;
            message.text = text;
        }

        if (!(message.text && message.type)) {
            return false;
        }

        // Remove error messages if success message is displayed
        if (message.type === "success") {
            context.commit('setMessages', state.messages.filter(m => m.type !== "error"));
        }

        let messages = state.messages;
        let current_message = messages.find(msg => msg.type == message.type && msg.text == message.text && msg.dialog == message.dialog);

        // Romove the message if it already exists and append it to the end of the list
        if (current_message) {
            context.commit('removeMessage', current_message.id);
        }

        context.commit('incrementMessageMaxId')
        message.id = state.message_max_id;
        context.commit('setMessage', message);
    },

    removeMessage(context, message) {
        // Prevent message runtime errors.
        if (state?.messages && Array.isArray(state.messages)) {
            let found = state.messages.filter(msg => msg.type == message.type && msg.text == message.text && msg.dialog == message.dialog);
            if (found.length) {
                let id = found[0].id;
                context.commit('removeMessage', id);
            }
        }
    },

    clearMessages(context, is_dialog) {
        if (is_dialog) {
            context.commit('setMessages', state.messages.filter(m => !m.dialog));
        }
        else {
            context.commit('setMessages', state.messages.filter(m => m.dialog));
        }
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
