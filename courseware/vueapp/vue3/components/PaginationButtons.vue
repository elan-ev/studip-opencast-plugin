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

<script setup>
import { computed } from "vue";

const props = defineProps(['paging']);

const emit = defineEmits(['changePage']);

// Computed props.
const pageNumbers = computed(() => {
    let numbers = [];
    let countFrom = Math.min(props.paging.lastPage - 4, props.paging.currPage - 1);
    let countTo   = Math.max(5, props.paging.currPage + 2);

    // show [1] [2] [3] instead of [1] ... [3]
    if (props.paging.lastPage >= 5 && props.paging.currPage >= 3) {
        if (props.paging.currPage == 3) {
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
        if (i > 0 && i < props.paging.lastPage) {
            numbers.push({
                'title': i + 1,
                'page' : i
            })
        }
    }

    // show [97] [98] [99] instead of [97] ... [99]
    if (props.paging.lastPage >= 5 && props.paging.currPage < props.paging.lastPage - 2) {
        if (props.paging.currPage == (props.paging.lastPage - 2)) {
            numbers.push({
                'title': props.paging.lastPage,
                'page' : props.paging.lastPage - 1
            })
        } else {
            numbers.push({
                'title': '...',
                'page' : -2
            });
        }
    }

    return numbers;
});

// Methods.
const setPage = async (page) => {
    if (page < 0) {
        return;
    }

    emit('changePage', page);
};
</script>
