<template>
    <div v-if="errors.length"
        :class="{
            'oc--messages-float': float
        }">
        <div class="messagebox messagebox_error"
            v-for="error in errors"
            v-bind:key="error">
            <div class="messagebox_buttons">
                <a class="close" href="#" title="Nachrichtenbox schliessen">
                    <span>Nachrichtenbox schliessen</span>
                </a>
            </div>
            <span v-if="error && error.data">
                <div v-if="error.data.errors"
                    v-for="err in error.data.errors">
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

            <span v-else-if="error">
                <div>
                    {{ error }}
                </div>
            </span>

            <span v-else>
                <div>
                    {{ $gettext('Es ist ein unbekannter Fehler aufgetreten') }}
                </div>
            </span>
        </div>
    </div>
</template>

<script>
import { mapGetters } from "vuex";

export default {
    name: "Error",

    props: ['float'],

    computed: {
        ...mapGetters(["errors"])
    },
    methods: {
        clearErrors() {
            this.$store.dispatch('errorClear');
        }
    }
};
</script>
