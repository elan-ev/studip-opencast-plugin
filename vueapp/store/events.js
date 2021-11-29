import gql from 'graphql-tag'
import { apolloClient } from '../vue-apollo'


const state = {
    cid: '',
    events: null
}

const getters = {
    events(state) {
        return state.events
    }
}

const mutations = {
    SET_CID(state, cid) {
        state.cid = cid
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

    async fetchEvents({commit, dispatch}) {
        const response = await apolloClient.query({
            query: gql`
                query {
                    getEvents(course_id: "${state.cid}") {
                     id
                     title
                     author
                     track_link
                     length
                     annotation_tool
                     description
                     mk_date
                }}
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
