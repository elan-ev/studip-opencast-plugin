import ApiService from "@/common/api.service";

const state = {
    playlists: [],
    userPlaylists: [],
    playlist: null,
    playlistSearch: '',
    addPlaylist: false,
    availableTags: [],
    playlistCourses: null,
    showPlaylistAddVideosDialog: false,
    playlistsReload: false,
    schedule_playlist: null,
    livestream_playlist: null,
}

const getters = {
    playlists(state) {
        return state.playlists
    },

    userPlaylists(state) {
        return state.userPlaylists;
    },

    playlist(state) {
        return state.playlist
    },

    defaultPlaylist(state) {
        // Find the courses default playlist
        for (let id in state.playlists) {
            if (state.playlists[id].is_default == '1') {
                return state.playlists[id];
            }
        }

        // If no default is found, use the first playlist
        for (let id in state.playlists) {
            return state.playlists[id];
        }

        return null;
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

    showPlaylistAddVideosDialog(state) {
        return state.showPlaylistAddVideosDialog;
    },

    playlistsReload(state) {
        return state.playlistsReload;
    },

    schedule_playlist(state) {
        return state.schedule_playlist
    },

    livestream_playlist(state) {
        return state.livestream_playlist
    },

}


const actions = {
    async loadPlaylists(context, filters= {}) {
        let $cid = context.rootState.opencast.cid;
        let $route = ($cid == null) ? 'playlists' : 'courses/' + $cid + '/playlists';

        // Load all playlists if no limit is set
        filters.limit = filters.limit || -1;

        // Set filters
        const params = new URLSearchParams();

        for (let key in filters) {
            if (key === 'filters') {
                params.append('filters', JSON.stringify(filters.filters));
            } else {
                params.append(key, filters[key]);
            }
        }

        return ApiService.get($route, { params })
            .then(({ data }) => {
                context.commit('setPlaylists', data.playlists);
                if ($cid) {
                    context.dispatch('loadScheduledRecordingPlaylists');
                }
            });
    },

    async loadUserPlaylists(context, filters) {
        // Set filters
        const params = new URLSearchParams();

        for (let key in filters) {
            if (key === 'filters') {
                params.append('filters', JSON.stringify(filters.filters));
            } else {
                params.append(key, filters[key]);
            }
        }

        // Get playlists user has access to
        return ApiService.get('playlists', { params })
            .then(({ data }) => {
                context.commit('setUserPlaylists', data.playlists);
            });
    },

    async loadPlaylist(context, token) {
        return ApiService.get('playlists/' + token)
            .then(({ data }) => {
                context.dispatch('setDefaultSortOrder', data).then(() => {
                    context.commit('setPlaylist', data);
                });
            });
    },

    async setPlaylist(context, playlist) {
        context.commit('setPlaylist', playlist);
    },

    async setDefaultSortOrder(context, playlist) {
        let field, order;
        if (!playlist.sort_order) {
            sort = 'created';
            order = 'desc';
        } else {
            [field, order] = playlist.sort_order.split('_');
        }
        context.commit('setVideoSort', {field: field, order: order});
    },

    async updatePlaylist(context, playlist) {
        return ApiService.put('playlists/' + playlist.token, playlist)
            .then(({ data }) => {
                context.commit('updatePlaylist', data);
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

    async addPlaylistToCourse(context, data) {
        let params = {};
        if (data?.is_default == true) {
            params.is_default = true;
        }
        return ApiService.post('courses/' + data.course + '/playlist/' + data.token, params)
    },

    async addPlaylistsToCourse(context, data) {
        for (const playlist of data.playlists) {
            await context.dispatch('addPlaylistToCourse', {
                course: data.course,
                token: playlist,
            });
        }
    },

    async copyPlaylistsToCourse(context, data) {
        for (const playlist of data.playlists) {
            await context.dispatch('copyPlaylist', {
                course: data.course,
                token: playlist,
            });
        }
    },

    async updatePlaylistCourses(context, params) {
        return ApiService.put('playlists/' + params.token + '/courses', {courses: params.courses})
    },

    async updatePlaylistOfCourse(context, params) {
        return ApiService.put('courses/' + params.course + '/playlist/' + params.token, params.playlist)
            .then(({ data }) => {
                context.commit('updatePlaylist', data);
            });
    },

    async removePlaylistFromCourse(context, params) {
        return ApiService.delete('courses/' + params.course + '/playlist/' + params.token)
    },

    async addVideosToPlaylist(context, data) {
        for (let i = 0; i < data.videos.length; i++) {
            await ApiService.put('/playlists/' + data.playlist + '/video/' + data.videos[i]);
        }
        context.commit('addToVideosCount', {'token': data.playlist, 'addToCount': data.videos.length});
    },

    async removeVideosFromPlaylist(context, data) {
        let removedCount = 0;
        let forbiddenCount = 0;
        for (let i = 0; i < data.videos.length; i++) {
            try {
                await ApiService.delete('/playlists/' + data.playlist + '/video/' + data.videos[i]);
                removedCount++;
            } catch (err) {
                // We send back 403 for those livestream video, when removing from playlist.
                if (err?.response?.status == 403) {
                    forbiddenCount++;
                }
            }
        }
        context.commit('addToVideosCount', {'token': data.playlist, 'addToCount': -removedCount});

        if (removedCount > 0) {
            return Promise.resolve({removedCount, forbiddenCount});
        }
        return Promise.reject({removedCount, forbiddenCount});
    },

    addPlaylistUI({ commit }, show) {
        commit('setPlaylistAdd', show);
    },

    async addPlaylist({ commit, dispatch, rootState }, playlist) {
        commit('setPlaylistAdd', false);

        let $cid = rootState.opencast.cid;

        let is_default = false;
        if (playlist?.is_default == true) {
            is_default = true;
            delete playlist.is_default;
        }

        return ApiService.post('playlists', playlist)
            .then(({ data }) => {
                if ($cid !== null) {
                    // connect playlist to new course
                    dispatch('addPlaylistToCourse', {
                        course: $cid,
                        token: data.token,
                        is_default: is_default
                    })
                    .then(() => {
                        dispatch('setPlaylistsReload', true);
                        dispatch('loadPlaylists');
                        // When is_default is true, it means it is the course playlist creation and we need to set a few things.
                        if (is_default) {
                            dispatch('loadCourseConfig', $cid);
                            dispatch('loadPlaylist', data.token);
                        }
                    })
                } else {
                    dispatch('setPlaylistsReload', true);
                    dispatch('loadPlaylists');
                }
            });
    },

    async copyPlaylist(context, params) {
        let data = {};

        if (params.course !== undefined) {
            data.course = params.course;
        }

        return ApiService.post('playlists/' + params.token + '/copy', data);
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
        });
    },

    async setAllowDownloadForPlaylist({dispatch, commit, state, rootState}, allowed) {
        let $cid = rootState.opencast.cid;

        if (state.playlist) {
            await commit('setAllowDownload', allowed);

            if ($cid !== null) {
                dispatch('updatePlaylistOfCourse', {
                    course: $cid,
                    token: state.playlist.token,
                    playlist: state.playlist
                });
            } else {
                dispatch('updatePlaylist', state.playlist);
            }
        }
    },

    togglePlaylistAddVideosDialog({commit}, mode) {
        commit('setShowPlaylistAddVideosDialog', mode);
    },

    setPlaylistsReload({commit}, mode) {
        commit('setPlaylistsReload', mode)
    },

    async loadScheduledRecordingPlaylists({dispatch, commit, state}) {
        if (state.playlists?.length) {
            let schedule_playlists = state.playlists.filter(pl => pl.contains_scheduled == true);
            if (schedule_playlists?.length) {
                await commit('setSchedulePlaylist', schedule_playlists[0]);
            }
            let livestream_playlists = state.playlists.filter(pl => pl.contains_livestreams == true);
            if (livestream_playlists?.length) {
                await commit('setLivestreamPlaylist', livestream_playlists[0]);
            }
        }
    },

    async setSchedulePlaylist({ commit, dispatch, rootState }, token) {
        let $cid = rootState.opencast.cid;
        return ApiService.post('playlists/' + token + '/schedule/' + $cid + '/scheduled')
    },

    async setLivestreamPlaylist({ commit, dispatch, rootState }, token) {
        let $cid = rootState.opencast.cid;
        return ApiService.post('playlists/' + token + '/schedule/' + $cid + '/livestreams')
    }
}

const mutations = {
    updatePlaylist(state, playlist) {
        let idx = state.playlists.findIndex(p => p.token === playlist.token);
        if (idx > -1) {
            state.playlists[idx] = playlist;
        }
    },

    setPlaylists(state, playlists) {
        state.playlists = playlists;
    },

    setUserPlaylists(state, playlists) {
        state.userPlaylists = playlists;
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

    setShowPlaylistAddVideosDialog(state, mode) {
        state.showPlaylistAddVideosDialog = mode;
    },

    setAllowDownload(state, allowed) {
        state.playlist.allow_download = allowed;
    },

    addToVideosCount(state, data) {
        let idx = state.playlists.findIndex(playlist => playlist.token === data.token);
        if (idx !== -1) {
            state.playlists[idx].videos_count += data.addToCount;
        }
    },

    setPlaylistsReload(state, mode) {
        state.playlistsReload = mode;
    },

    setSchedulePlaylist(state, schedule_playlist) {
        state.schedule_playlist = schedule_playlist
    },

    setLivestreamPlaylist(state, livestream_playlist) {
        state.livestream_playlist = livestream_playlist
    },
}


export default {
    state,
    getters,
    mutations,
    actions
}
