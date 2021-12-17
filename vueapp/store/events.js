import gql from 'graphql-tag'
import { apolloClient } from '../vue-apollo'


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
    SET_CID(state, cid) {
        state.cid = cid
    },

    SET_LIMIT(state, limit) {
        state.limit = limit
    },

    SET_PAGE(state, page) {
        if (page >= 0 && page <= state.paging.lastPage) {
            state.paging.currPage = page
        }
    },

    SET_EVENTS(state, data) {
        state.events = data.events
        state.paging.currPage = data.page_info.current_page
        state.paging.lastPage = data.page_info.last_page
    },

    ADD_EVENT(state, event) {
        state.events.push(event)
    },

    REMOVE_EVENT(state, id) {
        state.events = state.events.filter(function( event ) {
            return event.id !== id;
        });
    }
}

const actions = {
    async setCID({commit}, cid) {
        commit('SET_CID', cid)
    },

    async setLimit({commit}, limit) {
        commit('SET_LIMIT', limit)
    },

    async setPage({commit}, page) {
        commit('SET_PAGE', page)
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
                            track_link
                            length
                            annotation_tool
                            description
                            mk_date
                        }
                        page_info {
                            current_page
                            last_page
                        }
                    }
                }
            `
        }).catch((res) => {
            const errors = res.graphQLErrors.map((error) => {
                return error.message;
            });
            dispatch('errorCommit', { graphql: errors.join("\n") });
        });

        if (response !== undefined) {
            commit('SET_EVENTS', response.data.getEvents);
        }
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
                commit('ADD_EVENT', addEvent);
            },
        });
    },

    async removeEvent({commit, dispatch}, id) {
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
                commit('REMOVE_EVENT', removeEvent.id);
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
