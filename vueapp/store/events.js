import gql from 'graphql-tag'
import { apolloClient } from '../vue-apollo'


const state = {
    cid: '',
    events: []
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

    async fetchEvents({commit}) {
        const response = await apolloClient.query({
            query: gql` 
                query getEvents($cid: ID!) {
                    events(id: $cid) {
                        id
                        title
                        url
                        lecturer
                    }
                }
            `,
            variables: { cid: state.cid}
        })
        commit('SET_EVENTS', response.data.events)
    },

    async addEvent({commit, dispatch}, input) {
        input['cid'] = state.cid
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
        }).then(()=>{dispatch('fetchEvents', state.cid)})
    },

    async removeEvent({commit, dispatch}, id) {
        const response = await apolloClient.mutate({
            mutation: gql`
                mutation ($id: ID!) {
                    removeEvent(id: $id) {
                        id
                        title
                        url
                        lecturer
                    }
                }
            `,
            variables: {
                id: id
            },
        }).then(()=>{dispatch('fetchEvents', state.cid)})
    }
}

export default {
    state,
    getters,
    mutations,
    actions
}