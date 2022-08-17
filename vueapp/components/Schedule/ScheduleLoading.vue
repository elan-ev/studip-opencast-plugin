<template>
    <div v-show="schedule_loading" ref="main" class="oc--schedule-loading">
        <div ref="container" class="oc-loading-container">
            <h5 v-translate>
                Vorgang läuft. Bitte warten Sie einen Moment.
            </h5>
            <div>
                <span class="load-1"></span>
                <span class="load-2"></span>
                <span class="load-3"></span>
            </div>
        </div>
    </div>
</template>

<script>
import { mapGetters } from "vuex";
export default {
    name: "ScheduleLoading",

    computed: {
        ...mapGetters(["schedule_loading"]),
    },

    mounted () {
        this.setHeight();
    },

    updated () {
        this.setHeight();
    },

    methods: {
        setHeight() {
            let body = document.body,
                html = document.documentElement;

            let height = Math.max( body.scrollHeight, body.offsetHeight, 
                            html.clientHeight, html.scrollHeight, html.offsetHeight );
            
            this.$refs.main.style.height = `${height}px`;

            let vh = Math.max(document.documentElement.clientHeight || 0, window.innerHeight || 0);
            this.$refs.container.style.height = `${vh}px`;
        }
    }
}
</script>