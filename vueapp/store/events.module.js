import gql from 'graphql-tag'
import { apolloClient, apolloProvider } from '../vue-apollo'


const state = {
    cid: '',
    events: null,
    limit: 5,
    paging: {
        currPage: 0,
        lastPage: 0,
    },
}

const getters = {
    events(state) {
        return state.events
    },
    paging(state) {
        return state.paging
    }
}

const mutations = {
    setCid(state, cid) {
        state.cid = cid
    },

    setLimit(state, limit) {
        state.limit = limit
    },

    setPage(state, page) {
        if (page >= 0 && page <= state.paging.lastPage) {
            state.paging.currPage = page
        }
    },

    updatePaging(state, paging) {
        state.paging = paging;
    },

    setEvents(state, data) {
        if (data !== null && data.events !== undefined) {
            state.events = data.events;
        } else {
            state.events = [];
        }
    },

    addEvent(state, event) {
        state.events.push(event)
    },

    removeEvent(state, id) {
        for (let key in state.events) {
            if (state.events[key].id == id) {
                state.events[key].refresh = true;
                console.log('marked for refresh: ', state.events[key]);
            }
        }

        state.paging.totalItems -= 1;

        // go one page back if event removing reduced the number of pages
        if (Math.floor((state.paging.totalItems - 1) / 5) < state.paging.currPage) {
            if (state.paging.currPage > 0) {
                state.paging.currPage -= 1;
            }
        }
    }
}

const actions = {
    async setCID({commit}, cid) {
        commit('setCid', cid)
    },

    async setLimit({commit}, limit) {
        commit('setLimit', limit)
    },

    setPage({commit}, page) {
        apolloClient.stop();
        commit('setPage', page);
    },

    async reloadEvents({ dispatch, commit }) {
        apolloClient.clearStore().then(() =>
         {
            dispatch('fetchEvents');
        });
    },

    async fetchEvents({commit, dispatch}) {
        const response = await apolloClient.query({
            query: gql`
                query {
                    getEvents(course_id: "${state.cid}", offset: ${state.paging.currPage*state.limit}, limit: ${state.limit}) {
                        events {
                            id
                            title
                            author
                            contributor
                            track_link
                            length
                            annotation_tool
                            downloads {
                                type
                                url
                                width
                                height
                                size
                            }
                            description
                            mk_date
                        }
                        page_info {
                            total_items
                            current_page
                            last_page
                        }
                    }
                }
            `
        }).catch((res) => {
            if (res.graphQLErrors) {
                const errors = res.graphQLErrors.map((error) => {
                    return error.message;
                });

                dispatch('errorCommit', { graphql: errors.join("\n") });
            } else {
                dispatch('errorCommit', { graphql: res });
            }
        }).then(( response ) => {
            commit('setEvents', response.data.getEvents);

            // only update paging if events and paging info are available
            if (response.data.getEvents) {
                commit('updatePaging', {
                    currPage : response.data.getEvents.page_info.current_page,
                    lastPage : response.data.getEvents.page_info.last_page,
                    totalItems: response.data.getEvents.page_info.total_items
                });
            }
        })
    },

    async addEvent({commit, dispatch}, input) {
        input['mk_date'] = Math.floor(Date.now()/1000)
        const response = await apolloClient.mutate({
            mutation: gql`
                mutation ($course_id: ID!, $input: EventInput) {
                    addEvent(course_id: $course_id, input: $input) {
                        id
                        title
                        author
                    }
                }
            `,
            variables: {
                course_id: state.cid,
                input: input
            },
            update: (store, { data: { addEvent } }) => {
                commit('addEvent', addEvent);
            },
        });
    },

    async removeEvent({commit, dispatch}, id) {
        commit('removeEvent', id);
        const response = await apolloClient.mutate({
            mutation: gql`
                mutation ($course_id: ID!, $id: ID!) {
                    removeEvent(course_id: $course_id, id: $id) {
                        id
                        title
                        author
                    }
                }
            `,
            variables: {
                course_id: state.cid,
                id: id
            },
            update: (store, { data: { removeEvent } }) => {
                // clear cache
                apolloClient.cache.data.data = {};
                dispatch('fetchEvents');
            },
        });
    }
}

export default {
    state,
    getters,
    mutations,
    actions
}
