<template>
    <div class="oc--pagination" v-if="showPagination">
        <button v-if="paging.lastPage >= 0"
            v-bind:disabled="paging.currPage <= 0"
            @click="setPage(paging.currPage-1)"
            class="oc--paging-arrow"
        >
            <studip-icon
                shape="arr_1left" :role="paging.currPage <= 0 ? 'inactive' : 'clickable'"
            />
        </button>

        <button v-if="paging.lastPage >= 0"
            v-bind:class="{active : paging.currPage == 0}"
            v-bind:disabled="paging.currPage == 0"
            @click="setPage(0)"
        >
            1
        </button>

        <button v-for="number in pageNumbers" :key="number.page" @click="setPage(number.page)"
            v-bind:class="{active : number.page == paging.currPage}"
            v-bind:disabled="number.page == paging.currPage || number.page < 0">
            {{ number.title }}
        </button>

        <button v-if="paging.lastPage >= 1"
            v-bind:class="{active : paging.currPage == paging.lastPage}"
            v-bind:disabled="paging.currPage == paging.lastPage"
            @click="setPage(paging.lastPage)"
            >
            {{ paging.lastPage + 1}}
        </button>

        <button v-if="paging.lastPage >= 0"
            v-bind:disabled="paging.currPage >= paging.lastPage"
            @click="setPage(paging.currPage + 1)"
            class="oc--paging-arrow"
        >
             <studip-icon
                shape="arr_1right" :role="paging.currPage >= paging.lastPage ? 'inactive' : 'clickable'"
            />
        </button>

        <select class="oc--pagination--limit"
            v-model="limit"
            :title="$gettext('Elemente pro Seite')"
        >
            <option :value="minPaginationLimit">{{minPaginationLimit}}</option>
            <option value="15">15</option>
            <option value="30">30</option>
            <option value="50">50</option>
        </select>
    </div>

    <div class="oc--pagination-mobile" v-if="showPagination">
        <button v-if="paging.lastPage >= 0"
            v-bind:disabled="paging.currPage <= 0"
            @click="setPage(0)"
            class="oc--paging-arrow"
        >
            <studip-icon
                shape="arr_2left" :role="paging.currPage <= 0 ? 'inactive' : 'clickable'"
            />
        </button>

        <button v-if="paging.lastPage >= 0"
            v-bind:disabled="paging.currPage <= 0"
            @click="setPage(paging.currPage-1)"
            class="oc--paging-arrow"
        >
            <studip-icon
                shape="arr_1left" :role="paging.currPage <= 0 ? 'inactive' : 'clickable'"
            />
        </button>

        <button class="active">
            {{ paging.currPage+1 }}
        </button>

        <button v-if="paging.lastPage >= 0"
            v-bind:disabled="paging.currPage >= paging.lastPage"
            @click="setPage(paging.currPage + 1)"
            class="oc--paging-arrow"
        >
             <studip-icon
                shape="arr_1right" :role="paging.currPage >= paging.lastPage ? 'inactive' : 'clickable'"
            />
        </button>

        <button v-if="paging.lastPage >= 0"
            v-bind:disabled="paging.currPage >= paging.lastPage"
            @click="setPage(paging.lastPage)"
            class="oc--paging-arrow"
        >
             <studip-icon
                shape="arr_2right" :role="paging.currPage >= paging.lastPage ? 'inactive' : 'clickable'"
            />
        </button>

        <select class="oc--pagination--limit"
            v-model="limit"
            :title="$gettext('Elemente pro Seite')"
        >
            <option :value="minPaginationLimit">{{minPaginationLimit}}</option>
            <option value="15">15</option>
            <option value="30">30</option>
            <option value="50">50</option>
        </select>
    </div>
</template>

<script>
import { mapGetters } from 'vuex'

import StudipIcon from '@studip/StudipIcon.vue'

export default {
    name: "PaginationButtons",

    components: {
        StudipIcon
    },

    props: {
        'paging': {
            type: Object,
            required: true
        }
    },

    emits: ['changePage', 'changeLimit'],

    data() {
        return {
            limit: 15,
            minPaginationLimit: 5
        }
    },

    computed: {
        /**
         * Takes care of the page numbers to display.  For ui-consistency,
         * the number of elements is always the same, returning always 5 elements
         *
         * @return Array An array of objects of the type {'title': ..., 'page': ...}
         */
        pageNumbers() {
            let numbers = [];
            let countFrom = Math.min(this.paging.lastPage - 4, this.paging.currPage - 1);
            let countTo   = Math.max(5, this.paging.currPage + 2);

            // show [1] [2] [3] instead of [1] ... [3]
            if (this.paging.lastPage >= 5 && this.paging.currPage >= 3) {
                if (this.paging.currPage == 3) {
                    countFrom--;
                } else {
                    numbers.push({
                        'title': '...',
                        'page' : -1
                    });
                }
            }

            // the page numbers to be shown in general
            for (var i = countFrom; i < countTo; i++) {
                if (i > 0 && i < this.paging.lastPage) {
                    numbers.push({
                        'title': i + 1,
                        'page' : i
                    })
                }
            }

            // show [97] [98] [99] instead of [97] ... [99]
            if (this.paging.lastPage >= 5 && this.paging.currPage < this.paging.lastPage - 2) {
                if (this.paging.currPage == (this.paging.lastPage - 2)) {
                    numbers.push({
                        'title': this.paging.lastPage,
                        'page' : this.paging.lastPage - 1
                    })
                } else {
                    numbers.push({
                        'title': '...',
                        'page' : -2
                    });
                }
            }

            return numbers;
        },

        showPagination() {
            return this.paging.items > this.minPaginationLimit;
        }
    },

    watch: {
        limit(newLimit, oldLimit) {
            this.$emit('changeLimit', newLimit);
            this.$emit('changePage', 0);
        }
    },

    methods: {
        async setPage(page) {
            if (page < 0) {
                return;
            }

            this.$emit('changePage', page);
        },
    }
}
</script>
