import ApiService from "@/common/api.service";

const state = {
    playlists: {},
    playlists: null,
    playlistSearch: '',
    playlistSort: {
        field: 'mkdate',
        order: 'desc',
        text : 'Datum hochgeladen: Neuste zuerst'
    },
    playlistSorts: [
        {
            field: 'mkdate',
            order: 'desc',
            text : 'Datum hochgeladen: Neuste zuerst'
        },  {
            field: 'mkdate',
            order: 'asc',
            text : 'Datum hochgeladen: Älteste zuerst'
        },  {
            field: 'title',
            order: 'desc',
            text : 'Titel: Alphabetisch'
        }, {
            field: 'title',
            order: 'asc',
            text : 'Titel: Umgekehrt Alphabetisch'
        }
    ],
    addPlaylist: false,
    currentPlaylist: 'all',
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

    currentPlaylist(state) {
        return state.currentPlaylist
    },

    addPlaylist(state) {
        return state.addPlaylist;
    },

    availableTags(state) {
        return state.availableTags;
    },

    playlistSort(state) {
        return state.playlistSort
    },

    playlistSorts(state) {
        return state.playlistSorts
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
        context.dispatch('updateLoading', true);
        let $cid = context.rootState.opencast.cid;
        let $route = ($cid == null) ? 'playlists' : 'courses/' + $cid + '/playlist';

        return ApiService.get($route)
            .then(({ data }) => {
                context.dispatch('updateLoading', false);
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

    addPlaylistUI({ commit }, show) {
        commit('setPlaylistAdd', show);
    },

    async addPlaylist({ commit, dispatch, rootState }, playlist) {
        // TODO
        commit('setPlaylistAdd', false);

        let $cid = rootState.opencast.cid;
        let $route = ($cid == null) ? 'playlists' : 'courses/' + $cid + '/playlist';

        return ApiService.post($route, playlist)
            .then(({ data }) => {
                dispatch('loadPlaylists');
            });
    },

    async deletePlaylist(context, token) {
        return ApiService.delete('playlists/' + token);
    },

    async setCurrentPlaylist(context, token) {
        context.commit("setCurrentPlaylist", token);
    },

    async setPlaylistSearch({dispatch, commit}, search) {
        await commit('setPlaylistSearch', search)
        dispatch('loadPlaylists')
    },

    async setPlaylistSort({dispatch, commit}, sort) {
        await commit('setPlaylistSort', sort)
        dispatch('loadPlaylists')
    },

    async setPlaylistSearch({dispatch, commit}, search) {
        await commit('setPlaylistSearch', search)
        dispatch('loadPlaylists')
    },
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

    setCurrentPlaylist(state, token) {
        state.currentPlaylist = token;
    },

    setAvailableTags(state, availableTags) {
        state.availableTags = availableTags;
    },

    setPlaylistSearch(state, search) {
        state.playlistSearch = search;
    },

    setPlaylistSort(state, sort) {
        state.playlistSort = sort
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
