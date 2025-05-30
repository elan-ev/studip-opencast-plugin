import ApiService from '@/common/api.service';

const state = {
    showDrawer: false,
    selectedVideo: null
};

const getters = {
    showDrawer(state) {
        return state.showDrawer;
    },
    selectedVideo(state) {
        return state.selectedVideo;
    }
};

const actions = {
    setShowDrawer({ commit }, show) {
        commit('SET_SHOW_DRAWER', show);
    },
    setSelectedVideo({ commit }, video) {
        commit('SET_SELECTED_VIDEO', video);
    }
};

const mutations = {
    SET_SHOW_DRAWER(state, show) {
        state.showDrawer = show;
    },
    SET_SELECTED_VIDEO(state, video) {
        state.selectedVideo = video;
    }
};

export default {
    state,
    getters,
    actions,
    mutations,
};
