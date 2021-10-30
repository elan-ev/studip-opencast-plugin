<template>
    <div class="oc_flex">
        <div id="episodes" class="oc_flexitem oc_flexepisodelist">
            <ul class="oce_list list">
                <div v-if="$apollo.loading">Loading...</div>
                <EpisodeCard 
                    v-for="event in events"
                    v-bind:event="event"
                    v-bind:index="1"
                    v-bind:key="event.id"></EpisodeCard>
                <button v-on:click="addTestEpisode">Add Test Episode</button>
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
            cid: 'test'
        }
    },

    computed: {
        ...mapGetters([
            'events'
        ]),
    },

    methods: {
        addTestEpisode() {
            this.$store.dispatch('addEvent', 
                {
                    id: "123-x", 
                    cid: this.cid, 
                    title: "testi", 
                    lecturer: "Testor", 
                    type: "upload"
                }
            );
        }
    },

    mounted() {
        this.$store.dispatch('fetchEvents', this.cid)
    }
};
</script>