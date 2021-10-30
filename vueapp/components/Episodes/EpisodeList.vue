<template>
    <div class="oc_flex">
        <div id="episodes" class="oc_flexitem oc_flexepisodelist">
            <ul class="oce_list list">
                <div v-if="$apollo.loading">Loading...</div>
                <EpisodeCard 
                    v-for="(event, index) in events"
                    v-bind:event="event"
                    v-bind:index="index"
                    v-bind:key="event.id"></EpisodeCard>
                <button v-on:click="addTestEpisode">Add Test Episode</button>
            </ul>
        </div>
    </div>
</template>

<script>
import { mapGetters } from "vuex";
import store from "@/store";

import EpisodeCard from "@/components/Episodes/EpisodeCard"

import gpl from "graphql-tag"

export default {
    name: "Episodes",
    components: {
        EpisodeCard
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