import ApiService from "@/common/api.service";

const state = {
    playlists: {},
    playlists: null,
    playlistSearch: '',
    addPlaylist: false,
    availableTags: [],
    playlistCourses: null,
    myCourses: null
}

const getters = {
    playlists(state) {
        return state.playlists
    },

    playlist(state) {
        return state.playlist
    },

    addPlaylist(state) {
        return state.addPlaylist;
    },

    availableTags(state) {
        return state.availableTags;
    },

    playlistCourses(state) {
        return state.playlistCourses
    },

    myCourses(state) {
        return state.myCourses
    },
}


const actions = {
    async loadPlaylists(context) {
        let $cid = context.rootState.opencast.cid;
        let $route = ($cid == null) ? 'playlists' : 'courses/' + $cid + '/playlists';

        return ApiService.get($route)
            .then(({ data }) => {
                context.commit('setPlaylists', data);
            });
    },

    async loadPlaylist(context, token) {
        return ApiService.get('playlists/' + token)
            .then(({ data }) => {
                context.commit('setPlaylist', data);
            });
    },

    async updatePlaylist(context, playlist) {
        return ApiService.put('playlists/' + playlist.token, playlist)
            .then(({ data }) => {
                context.commit('setPlaylists', [data]);
            });
    },

    async updateAvailableTags(context) {
        return ApiService.get('tags')
            .then(({ data }) => {
                context.commit('setAvailableTags', data);
            });
    },

    async loadPlaylistCourses(context, token) {
        return ApiService.get('playlists/' + token + '/courses')
        .then(({ data }) => {
            context.commit('setPlaylistCourses', data);
        });
    },

    async addPlaylistToCourse(context, params) {
        return ApiService.put('courses/' + params.course + '/playlist/' + params.token)
    },

    async removePlaylistFromCourse(context, params) {
        return ApiService.delete('courses/' + params.course + '/playlist/' + params.token)
    },

    async loadMyCourses(context, token) {
        return ApiService.get('courses')
        .then(({ data }) => {
            context.commit('setMyCourses', data);
        });
    },

    async addVideosToPlaylist(context, data) {
        for (let i = 0; i < data.videos.length; i++) {
            await ApiService.put('/playlists/' + data.playlist + '/video/' + data.videos[i]);
        }
    },

    async removeVideosFromPlaylist(context, data) {
        for (let i = 0; i < data.videos.length; i++) {
            await ApiService.delete('/playlists/' + data.playlist + '/video/' + data.videos[i]);
        }
    },

    addPlaylistUI({ commit }, show) {
        commit('setPlaylistAdd', show);
    },

    async addPlaylist({ commit, dispatch, rootState }, playlist) {
        // TODO
        commit('setPlaylistAdd', false);

        let $cid = rootState.opencast.cid;
        let $route = ($cid == null) ? 'playlists' : 'courses/' + $cid + '/playlists';

        return ApiService.post($route, playlist)
            .then(({ data }) => {
                dispatch('loadPlaylists');
            });
    },

    async deletePlaylist(context, token) {
        return ApiService.delete('playlists/' + token);
    },

    async setPlaylistSearch({dispatch, commit}, search) {
        await commit('setPlaylistSearch', search)
        dispatch('loadPlaylists')
    },

    async setPlaylistSearch({dispatch, commit}, search) {
        await commit('setPlaylistSearch', search)
        dispatch('loadPlaylists')
    },

    async setPlaylistSort({}, data) {
        return ApiService.put('/playlists/' + data.token, {
            sort_order: data.sort.field + '_' + data.sort.order
        });
    }
}

const mutations = {
    setPlaylists(state, playlists) {
        state.playlists = playlists;
    },

    setPlaylist(state, playlist) {
        state.playlist = playlist;
    },

    setPlaylistAdd(state, show) {
        state.addPlaylist = show;
    },

    setAvailableTags(state, availableTags) {
        state.availableTags = availableTags;
    },

    setPlaylistSearch(state, search) {
        state.playlistSearch = search;
    },

    setPlaylistCourses(state, courses) {
        state.playlistCourses = courses
    },

    setMyCourses(state, courses) {
        state.myCourses = courses
    },
}


export default {
    state,
    getters,
    mutations,
    actions
}
