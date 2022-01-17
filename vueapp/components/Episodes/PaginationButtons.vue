<template>
    <div class="oc--pagination">
        {{ paging }}

        <button v-if="paging.lastPage > 0"
            v-bind:disabled="paging.currPage <= 0"
            @click="setPage(paging.currPage-1)"
        >
            &laquo;
        </button>

        <button v-if="paging.lastPage > 0"
            v-bind:class="{active : paging.currPage == 0}"
            v-bind:disabled="paging.currPage == 0"
            @click="setPage(0)"
            >
            1
        </button>

        <button v-if="paging.lastPage > 2 && paging.currPage > 2" disabled>
            ...
        </button>

        <button v-for="number in pageNumbers" :key="number" @click="setPage(number)"
            v-bind:class="{active : number == paging.currPage}"
            v-bind:disabled="number == paging.currPage">
            {{ number + 1 }}
        </button>

        <button v-if="paging.lastPage > 2 && paging.currPage < paging.lastPage-2" disabled>
            ...
        </button>

        <button v-if="paging.lastPage >= 1"
            v-bind:class="{active : paging.currPage == paging.lastPage}"
            v-bind:disabled="paging.currPage == paging.lastPage"
            @click="setPage(paging.lastPage)"
            >
            {{ paging.lastPage + 1}}
        </button>

        <button v-if="paging.lastPage > 0"
            v-bind:disabled="paging.currPage >= paging.lastPage"
            @click="setPage(paging.currPage+1)"
        >
            &raquo;
        </button>
    </div>
</template>

<script>
import { mapGetters } from 'vuex'

import StudipIcon from '@/components/StudipIcon.vue'

export default {
    name: "PaginationButtons",

    components: {
        StudipIcon
    },

    computed: {
        ...mapGetters([
            'paging'
        ]),

        pageNumbers() {
            var numbers = []
            for (var i = this.paging.currPage-1; i < this.paging.currPage+2; i++) {
                if (i > 0 && i < this.paging.lastPage) {
                    numbers.push(i)
                }
            }
            return numbers
        }
    },

    methods: {
        async setPage(page) {
            this.$emit('changePage', page);
        },
    }
}
</script>
