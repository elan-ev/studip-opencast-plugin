import gql from 'graphql-tag'
import { apolloClient } from '../vue-apollo'


const state = {
    events: []
}

const getters = {
    events(state) {
        return state.events
    }
}

const mutations = {
    SET_EVENTS (state, events) {
        state.events = events
    },

    ADD_EVENT (state, event) {
        state.events.push(event)
    },

    REMOVE_EVENT (state, id) {
        state.events = state.events.filter(function( event ) {
            return event.id !== id;
        });
    }
}

const actions = {
    async fetchEvents({commit}, cid) {
        const response = await apolloClient.query({
            query: gql` 
                query getEvents($cid: ID!) {
                    events(id: $cid) {
                        id
                        title
                        lecturer
                    }
                }
            `,
            variables: { cid: cid}
        })
        commit('SET_EVENTS', response.data.events)
    },

    async addEvent({commit}, input) {
        const response = await apolloClient.mutate({
            mutation: gql` 
                mutation ($input: EventInput) {
                    addEvent(input: $input) {
                        id
                        title
                        lecturer
                    }
                }
            `,
            variables: {
                input: input
            },
        })
        commit('ADD_EVENT', response.data.addEvent)
    },

    async removeEvent({commit}, id) {
        const response = await apolloClient.mutate({
            mutation: gql`
                mutation ($id: ID!) {
                    removeEvent(id: $id) {
                        id
                        title
                        lecturer
                    }
                }
            `,
            variables: {
                id: id
            },
        })
        commit('REMOVE_EVENT', id)
    }
}

export default {
    state,
    getters,
    mutations,
    actions
}