<template>
    <div>
        <h1>Tob dich aus! ;)</h1>
        <div v-if="$apollo.loading">Loading...</div>
        <Episode 
            v-for="(event, index) in events"
            v-bind:event="event"
            v-bind:index="index"
            v-bind:key="event.id"></Episode>
        <button v-on:click="addTestEpisode">Add Test Episode</button>
    </div>
</template>

<script>
import { mapGetters } from "vuex";
import store from "@/store";

import StudipButton from "@/components/StudipButton";
import StudipIcon from "@/components/StudipIcon";
import MessageBox from "@/components/MessageBox";
import Episode from "@/components/Episode"

import gpl from "graphql-tag"

export default {
    name: "Episodes",
    components: {
        StudipButton, StudipIcon,
        MessageBox, Episode
    },

    data() {
        return {
            events: [],
            cid: 'test'
        }
    },

    apollo: {
        events: {
            query: gpl` query getEvents($cid: ID!) {
                events(id: $cid) {
                    id
                    title
                    lecturer
                }
            }`,
            variables() {
                return {
                    cid: this.cid
                }
            }
        },
    },

    computed: {

    },

    methods: {
        addTestEpisode() {
            this.$apollo.mutate({
                mutation: gpl` mutation ($input: EventInput) {
                    addEvent(input: $input) {
                        id
                        title
                        lecturer
                    }
                }`,
                variables: {
                    input: {
                        id: "123-d",
                        cid: "test",
                        title: "testi",
                        lecturer: "lecturer",
                        type: "upload"
                    }
                },
            })
        }
    },

    mounted() {

    }
};
</script>
