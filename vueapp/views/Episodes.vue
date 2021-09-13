<template>
    <div>
        <h1>Tob dich aus! ;)</h1>
        <div v-if="$apollo.loading">Loading...</div>
        <Episode 
            v-for="(event, index) in events"
            v-bind:event="event"
            v-bind:index="index"
            v-bind:key="event.id"></Episode>
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
            events: []
        }
    },

    apollo: {
        events: {
            query: gpl` query {
                events {
                    id
                    title
                    lecturer
                }
            }`
        }
    },

    computed: {

    },

    methods: {

    },

    mounted() {

    }
};
</script>
