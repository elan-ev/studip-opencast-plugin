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
        <div>
            <nav>
                <button v-if="paging.currPage > 0" @click="prevPage">
                    <studip-icon icon="arr_1left" role="clickable" size="24"/>
                </button>
                <button v-else :disabled="true">
                    <studip-icon icon="arr_1left" role="inactive" size="24"/>
                </button>

                <button v-for="number in pageNumbers" :key="number" @click="setPage(number)">
                    {{number+1}}
                </button>

                <button v-if="paging.currPage < paging.lastPage" @click="nextPage">
                    <studip-icon icon="arr_1right" role="clickable" size="24"/>
                </button>
                <button v-else :disabled="true">
                    <studip-icon icon="arr_1right" role="inactive" size="24"/>
                </button>
            </nav>
        </div>
    </div>
</template>

<script>
import { mapGetters } from 'vuex'

import EpisodeCard from "@/components/Episodes/EpisodeCard"
import EmptyEpisodeCard from "@/components/Episodes/EmptyEpisodeCard"
import StudipIcon from '@/components/StudipIcon.vue';

export default {
    name: "Episodes",
    components: {
        EpisodeCard,
        EmptyEpisodeCard,
        StudipIcon
    },

    data() {
        return {
        }
    },

    computed: {
        ...mapGetters([
            'events',
            'paging'
        ]),

        pageNumbers() {
            var numbers = []
            if(this.paging.lastPage > 2) {
                numbers.push(0)
                for (var i = this.paging.currPage-1; i < this.paging.currPage+2; i++) {
                    if (i > 0 && i < this.paging.lastPage) {
                        numbers.push(i)
                    }
                }
                numbers.push(this.paging.lastPage)
            }
            return numbers
        }
    },

    methods: {
        async nextPage() {
            await this.$store.dispatch('setPage', this.paging.currPage+1)
            this.$store.dispatch('fetchEvents')
        },

        async prevPage() {
            await this.$store.dispatch('setPage', this.paging.currPage-1)
            this.$store.dispatch('fetchEvents')
        },

        async setPage(page) {
            await this.$store.dispatch('setPage', page)
            this.$store.dispatch('fetchEvents')
        },
    },

    async mounted() {
        await this.$store.dispatch('setLimit', 5)
        await this.$store.dispatch('setPage', 0)
        this.$store.dispatch('fetchEvents')
    }
};
</script>
