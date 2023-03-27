import ApiService from "@/common/api.service";

const state = {
    playlists: {},
    playlists: null,
    playlistSearch: '',
    addPlaylist: false,
    availableTags: [],
    playlistCourses: null
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
    }
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

    async addPlaylistToCourses(context, params) {
        return ApiService.put('playlists/' + params.token + '/courses', {courses: params.courses})
    },

    async removePlaylistFromCourse(context, params) {
        return ApiService.delete('courses/' + params.course + '/playlist/' + params.token)
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
        commit('setPlaylistAdd', false);

        let $cid = rootState.opencast.cid;

        return ApiService.post('playlists', playlist)
            .then(({ data }) => {
                if ($cid !== null) {
                    // connect playlist to new course
                    dispatch('addPlaylistToCourse', {
                        course: $cid,
                        token: data.token
                    })
                    .then(() => dispatch('loadPlaylists'))
                } else {
                    dispatch('loadPlaylists');
                }
            });
    },

    async deletePlaylist(context, token) {
        return ApiService.delete('playlists/' + token);
    },

    async setPlaylistSearch({dispatch, commit}, search) {
        await commit('setPlaylistSearch', search)
        dispatch('loadPlaylists')
    },

    async setPlaylistSort({dispatch}, data) {
        return ApiService.put('/playlists/' + data.token, {
            sort_order: data.sort.field + '_' + data.sort.order
        }).then(() => dispatch('loadPlaylist', data.token));
    },

    async setAllowDownloadForPlaylist({dispatch, commit, state}, allowed) {
        if (state.playlist) {
            await commit('setAllowDownload', allowed);
            dispatch('updatePlaylist', state.playlist);
        }
    }
}

const mutations = {
    setPlaylists(state, playlists) {
        state.playlists = playlists;
    },

    setPlaylist(state, playlist) {
        state.playlist = playlist;
    },

    setPlaylistSort(state, sortOder) {
        state.playlist.sort_order = sortOder.sort.field + '_' + sortOder.sort.order
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

    setAllowDownload(state, allowed) {
        state.playlist.allow_download = allowed;
    }
}


export default {
    state,
    getters,
    mutations,
    actions
}
