const state = () => ({
    avatars: {},
});

const getters = {
    getAvatar: (state) => (userId) => state.avatars[userId] || null,
};

const mutations = {
    setAvatar(state, { userId, url }) {
        state.avatars[userId] = url;
    },
};

const actions = {
    async fetchAvatar({ state, commit }, userId) {
        if (state.avatars[userId]) return state.avatars[userId]; // schon im Cache

        try {
            const baseUrl = STUDIP.URLHelper.getURL('jsonapi.php/v1/', {}, true);
            const url = new URL(`users/${userId}`, baseUrl);
            const res = await fetch(url);
            if (res.ok) {
                const data = await res.json();
                const avatar = data.data.meta?.avatar?.small || null;
                commit('setAvatar', { userId, url: avatar });
                return avatar;
            }
        } catch (err) {
            console.warn('Avatar konnte nicht geladen werden', err);
            return null;
        }
    },
};

export default {
  namespaced: true,
  state,
  actions,
  mutations,
  getters
};