<template>
    <div>
        <h1>Tob dich aus! ;)</h1>
        <h2>{{ currentUser.id }}</h2>
    </div>
</template>

<script>
import { mapGetters } from "vuex";
import store from "@/store";

import StudipButton from "@/components/StudipButton";
import StudipIcon from "@/components/StudipIcon";
import MessageBox from "@/components/MessageBox";

import gpl from "graphql-tag"

export default {
    name: "Episodes",
    components: {
        StudipButton, StudipIcon,
        MessageBox
    },

    data() {
        return {
            currentUser: {
                id: 'default'
            }
        }
    },

    apollo: {
        currentUser: gpl`
        query {
            currentUser {
                id
            }
        }`
    },

    computed: {

    },

    methods: {

    },

    mounted() {

    }
};
</script>
