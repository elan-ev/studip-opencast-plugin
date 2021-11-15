<template>
    <div class="oc--flex">
        <div id="episodes" class="oc--flexitem oc--flexepisodelist">
            <ul class="oc--episode-list">
                <div v-if="$apollo.loading">Loading...</div>
                <EpisodeCard
                    v-for="event in events"
                    v-bind:event="event"
                    v-bind:index="1"
                    v-bind:key="event.id"></EpisodeCard>
            </ul>
        </div>
    </div>
</template>

<script>
import { mapGetters } from 'vuex'

import EpisodeCard from "@/components/Episodes/EpisodeCard"

export default {
    name: "Episodes",
    components: {
        EpisodeCard
    },

    data() {
        return {
        }
    },

    computed: {
        ...mapGetters([
            'events'
        ]),
    },

    methods: {

    },

    mounted() {
        this.$store.dispatch('fetchEvents')
    }
};
</script>
