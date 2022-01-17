<template>
    <div class="oc--flex">
        <div id="episodes" class="oc--flexitem oc--flexepisodelist">
            <ul v-if="events === null || loadingPage" class="oc--episode-list oc--episode-list--empty">
                <EmptyEpisodeCard />
                <EmptyEpisodeCard />
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
        <PaginationButtons @changePage="changePage"/>
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
            loadingPage: false
        }
    },

    computed: {
        ...mapGetters([
            'events'
        ]),
    },

    methods: {
        changePage: async function(page) {
            this.loadingPage = true;
            await this.$store.dispatch('setPage', page)
            await this.$store.dispatch('fetchEvents')
            this.loadingPage = false;
        }
    },

    mounted() {
        this.$store.dispatch('fetchEvents')
    }
};
</script>
