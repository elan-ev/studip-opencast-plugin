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
        page = (page < 0) ? 0 : page
        page = (page > state.paging.lastPage) ? state.paging.lastPage : page
        state.paging.currPage = page
    },

    SET_LASTPAGE(state, lastPage) {
        state.paging.lastPage = lastPage
    },

    SET_EVENTS(state, events) {
        state.events = events
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

    async setPage({commit, dispatch}, page) {
        await dispatch('updateLastPage')
        commit('SET_PAGE', page)
    },

    async updateLastPage({commit, dispatch}) {
        const response = await apolloClient.query({
            query: gql`
                query {
                    getCountEvents(course_id: "${state.cid}")
                }
            `
        })
        commit('SET_LASTPAGE', Math.floor(response.data.getCountEvents / state.limit))
    },

    async fetchEvents({commit, dispatch}) {
        const response = await apolloClient.query({
            query: gql`
                query {
                    getEvents(course_id: "${state.cid}", offset: ${state.paging.currPage*state.limit}, limit: ${state.limit}) {
                        id
                        title
                        author
                        track_link
                        length
                        annotation_tool
                        description
                        mk_date
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
