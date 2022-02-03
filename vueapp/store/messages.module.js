import Vue from "vue";
import ApiService from "@/common/api.service";

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
        messages.push({
            id:   state.message_num++,
            type: message.type,
            text: message.text
        });
        context.commit('setMessages', messages);
    },

    clearMessages(context) {
        context.commit('setMessages', []);
    }
};

/* eslint no-param-reassign: ["error", { "props": false }] */
export const mutations = {
    setMessages(state, messages) {
        state.messages = messages;
    },

    removeMessage(state, id) {
        for (let msg_id in state.messages) {
            if (state.messages[msg_id].id == id) {
                console.log(state.messages[msg_id]);
                state.messages.splice(msg_id, 1);
            }
        }
    }
};

export default {
  state,
  actions,
  mutations,
  getters
};
