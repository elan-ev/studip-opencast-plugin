<template>
    <div>
        <h1>Tob dich aus! ;)</h1>
    </div>
</template>

<script>
import { mapGetters } from "vuex";
import store from "@/store";

import StudipButton from "@/components/StudipButton";
import StudipIcon from "@/components/StudipIcon";
import MessageBox from "@/components/MessageBox";

export default {
    name: "Episodes",
    components: {
        StudipButton, StudipIcon,
        MessageBox
    },

    data() {
        return {

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
