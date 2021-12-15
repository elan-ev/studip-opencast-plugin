<template>
    <div class="oc--flex">
        <div id="episodes" class="oc--flexitem oc--flexepisodelist">
            <ul v-if="events === null" class="oc--episode-list oc--episode-list--empty">
                <EmptyEpisodeCard />
                <EmptyEpisodeCard />
                <EmptyEpisodeCard />
            </ul>

            <ul class="oc--episode-list" v-else>
                <EpisodeCard
                    v-for="event in events"
                    v-bind:event="event"
                    v-bind:index="1"
                    v-bind:key="event.id"></EpisodeCard>
            </ul>
        </div>
        <PaginationButtons/>
    </div>
</template>

<script>
import { mapGetters } from 'vuex'

import EpisodeCard from "@/components/Episodes/EpisodeCard"
import EmptyEpisodeCard from "@/components/Episodes/EmptyEpisodeCard"
import PaginationButtons from '@/components/Episodes/PaginationButtons'

export default {
    name: "Episodes",
    components: {
        EpisodeCard,
        EmptyEpisodeCard,
        PaginationButtons
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

    methods: {},

    async mounted() {
        await this.$store.dispatch('updateLastPage')
        this.$store.dispatch('fetchEvents')
    }
};
</script>
