<template>
    <div class="oc-cw-pagination">
        <button v-if="paging.lastPage > 0"
            v-bind:disabled="paging.currPage <= 0"
            @click.self="setPage(paging.currPage-1)"
        >
            &laquo;
        </button>

        <button v-if="paging.lastPage > 0"
            v-bind:class="{active : paging.currPage == 0}"
            v-bind:disabled="paging.currPage == 0"
            @click.self="setPage(0)"
        >
            1
        </button>

        <button v-for="number in pageNumbers" :key="number.page" @click.self="setPage(number.page)"
            v-bind:class="{active : number.page == paging.currPage}"
            v-bind:disabled="number.page == paging.currPage || number.page < 0">
            {{ number.title }}
        </button>

        <button v-if="paging.lastPage >= 1"
            v-bind:class="{active : paging.currPage == paging.lastPage}"
            v-bind:disabled="paging.currPage == paging.lastPage"
            @click.self="setPage(paging.lastPage)"
            >
            {{ paging.lastPage + 1}}
        </button>

        <button v-if="paging.lastPage > 0"
            v-bind:disabled="paging.currPage >= paging.lastPage"
            @click.self="setPage(paging.currPage + 1)"
        >
            &raquo;
        </button>
    </div>
</template>

<script>
export default {
    name: "PaginationButtons",

    props: ['paging'],

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
