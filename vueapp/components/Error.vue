<template>
    <div v-if="error" class="messagebox messagebox_error ">
        <div class="messagebox_buttons">
            <a class="close" href="#" title="Nachrichtenbox schliessen">
                <span>Nachrichtenbox schliessen</span>
            </a>
        </div>
        <span v-if="error.data">
            <div v-if="error.data.errors" v-for="err in error.data.errors">
                {{ err.code }}: {{ err.title }}
            </div>

            <div v-if="error.data.message">
                {{ error.status }}: {{ error.data.message }} ({{ error.config.method }}: {{ error.config.baseURL }}/{{ error.config.url }})
            </div>

            <div v-if="error.data.error" v-for="err in error.data.error">
                {{ err.message }}<br>
                Line {{ err.line }} in file {{ err.file }}
            </div>
        </span>

        <span v-else>
            <div v-if="error.graphql">
                GraphQL: {{ error.graphql }}
            </div>
            <div v-else>
                {{ error }}
            </div>
        </span>
    </div>
</template>

<script>
import store from "@/store";
import { mapGetters } from "vuex";

export default {
    name: "Error",

    computed: {
        ...mapGetters(["error"])
    },
    methods: {
        clearErrors() {
            this.$store.dispatch('errorClear');
        }
    }
};
</script>
