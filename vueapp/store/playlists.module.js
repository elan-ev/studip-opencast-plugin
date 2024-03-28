import ApiService from "@/common/api.service";
import PlaylistsService from "@/common/playlists.service";
import { format } from "date-fns";

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

    /**
     * Load playlist
     *
     * @param context
     * @param token playlist token
     */
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

    /**
     * Updates playlist in Opencast and then in Stud.IP
     *
     * @param context
     * @param playlist playlist data
     */
    async updatePlaylist(context, playlist){
        let simpleConfigList = await context.dispatch("simpleConfigListRead", true);
        let server = simpleConfigList['server'][playlist['config_id']];
        let playlistsService = new PlaylistsService(server);

        // Load playlist from Opencast
        playlistsService.get(playlist['service_playlist_id'])
            .then(({ data }) => {
                // Update playlist in Opencast
                return playlistsService.update(data.id, playlist.title, playlist.description, playlist.creator, data.entries, data.accessControlEntries)
            })
            .then(({ data }) => {
                // Collect updated playlist data
                let updateData = {
                    title: data.title,
                    description: data.description,
                    creator: data.creator,
                    updated: format(new Date(data.updated), "yyyy-MM-dd HH:mm:ss"),
                }
                let updatedPlaylist = { ...playlist, ...updateData };

                // Update playlist in Stud.IP
                return Promise.all([
                    context.dispatch('updateStudipPlaylist', updatedPlaylist), // Save updated playlist
                    context.dispatch('updatePlaylistEntries', { token: updatedPlaylist.token, entries: data.entries }),  // Save entries
                ]);
            });
    },

    /**
     * Update playlist only in Stud.IP
     *
     * @param context
     * @param playlist playlist data
     */
    async updateStudipPlaylist(context, playlist) {
        return ApiService.put('playlists/' + playlist.token, playlist)
            .then(({ data }) => {
                context.commit('updatePlaylist', data);
            });
    },

    /**
     * Update videos of playlist with Opencast playlist entries
     *
     * @param context
     * @param data playlist data
     * @param data.token playlist token
     * @param data.entries opencast playlist entries
     */
    async updatePlaylistEntries(context, data) {
        return ApiService.put('playlists/' + data.token + '/entries', { entries: data.entries });
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
        for (const [index, playlist] of data.playlists.entries()) {
            let is_default = false;

            // Set first playlist as default
            if (data?.is_default === true && index === 0) {
                is_default = true;
            }

            await context.dispatch('copyPlaylist', {
                course: data.course,
                token: playlist,
                is_default: is_default,
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

    /**
     * Add videos to playlist
     *
     * @param context
     * @param data data
     * @param data.playlist playlist
     * @param data.videos videos to add
     */
    async addVideosToPlaylist(context, data) {
        let simpleConfigList = await context.dispatch("simpleConfigListRead", true);
        let server = simpleConfigList['server'][data.playlist['config_id']];
        let playlistsService = new PlaylistsService(server);

        // Get playlist from Opencast
        return playlistsService.get(data.playlist['service_playlist_id'])
            .then(( response ) => {
                // Update playlist entries in Opencast first
                let entries = response.data.entries;

                for (const video of data.videos) {
                    // Only add video if not contained in entries
                    if (entries.findIndex(entry => entry['contentId'] === video['episode']) < 0) {
                        // Append video to end of playlist entries
                        entries.push({
                            contentId: video['episode'],
                            type: 'EVENT'
                        });
                    }
                }

                return playlistsService.updateEntries(response.data.id, entries);
            })
            .then(() => {
                // Add videos to playlist in Stud.IP
                let promises = [];

                for (let i = 0; i < data.videos.length; i++) {
                    promises.push(ApiService.put('/playlists/' + data.playlist.token + '/video/' + data.videos[i].token));
                }

                // Wait until all operations are finished successfully
                return Promise.all(promises)
                    .then(() => {
                        context.commit('addToVideosCount', {'token': data.playlist, 'addToCount': data.videos.length});
                    })
            });
    },

    /**
     * Remove videos from playlist
     *
     * @param context
     * @param data data
     * @param data.playlist playlist
     * @param data.videos videos to remove
     */
    async removeVideosFromPlaylist(context, data) {
        let simpleConfigList = await context.dispatch("simpleConfigListRead", true);
        let server = simpleConfigList['server'][data.playlist['config_id']];
        let playlistsService = new PlaylistsService(server);

        // Get playlist and remove entries in Opencast
        return playlistsService.get(data.playlist['service_playlist_id'])
            .then(( response ) => {
                // Update playlist entries in Opencast first
                let entries = response.data.entries;

                for (const video of data.videos) {
                    // Remove all occurrences of video from entries
                    entries = entries.filter(entry => entry['contentId'] !== video['episode']);
                }

                return playlistsService.updateEntries(response.data.id, entries);
            })
            .then(() => {
                // Delete videos of playlist in Stud.IP
                let removedCount = 0;
                let forbiddenCount = 0;

                let promises = [];

                for (const video of data.videos) {
                    promises.push(ApiService.delete('/playlists/' + data.playlist['token'] + '/video/' + video['token'])
                        .then(() => {
                            removedCount++;
                        })
                        .catch((error) => {
                            // We send back 403 for those livestream video, when removing from playlist.
                            if (error?.response?.status === 403) {
                                forbiddenCount++;
                            }
                        }));
                }

                // Wait until all operations are finished successfully
                return Promise.all(promises)
                    .then(() => {
                        context.commit('addToVideosCount', {'token': data.playlist, 'addToCount': -removedCount});

                        if (removedCount > 0) {
                            return Promise.resolve({removedCount, forbiddenCount});
                        }
                        return Promise.reject({removedCount, forbiddenCount});
                    })
            });
    },

    addPlaylistUI({ commit }, show) {
        commit('setPlaylistAdd', show);
    },

    async addPlaylist({ commit, dispatch, rootState }, playlist) {
        commit('setPlaylistAdd', false);

        let simpleConfigList = await dispatch("simpleConfigListRead", true);
        let server = simpleConfigList['server'][playlist['config_id']];
        let playlistsService = new PlaylistsService(server);

        // Create empty playlist in Opencast first
        playlistsService.create(playlist.title, playlist.description, playlist.creator, [])
            .then(({ data }) => {
                playlist.service_playlist_id = data.id;

                let $cid = rootState.opencast.cid;

                let is_default = false;
                if (playlist?.is_default == true) {
                    is_default = true;
                    delete playlist.is_default;
                }

                // Create playlist in Stud.IP
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
            });
    },

    async copyPlaylist(contexcontext, params) {
        let data = {};

        if (params.course !== undefined) {
            data.course = params.course;
            data.is_default = params.is_default ?? false;
        }

        return ApiService.post('playlists/' + params.token + '/copy', data);
    },

    async deletePlaylist(context, playlist) {
        let simpleConfigList = await context.dispatch("simpleConfigListRead", true);
        let server = simpleConfigList['server'][playlist['config_id']];
        let playlistsService = new PlaylistsService(server);

        // Delete playlist in Opencast first
        return playlistsService.delete(playlist['service_playlist_id'])
            .then(() => {
                // Delete playlist from Stud.IP
                return ApiService.delete('playlists/' + playlist['token']);
            });
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
