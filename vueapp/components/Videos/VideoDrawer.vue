<template>
        <StudipDrawer v-if="attachTarget" :visible="showDrawer" :attachTo="attachTarget" side="right" width="75%" :maxWidth="1200" @close="close">
            <h2>Testinhalt im Drawer</h2>
            <p>Ein bisschen Beispieltext. Funktioniert das?</p>
        </StudipDrawer>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import StudipDrawer from '@studip/StudipDrawer.vue';
import { useStore } from 'vuex';
const store = useStore();

const attachTarget = ref(null);

const showDrawer = computed(() => {
    return store.getters.showDrawer;
});

onMounted(() => {
    attachTarget.value = document.querySelector('#content-wrapper');
});
const close = () =>  {
    store.commit('setShowDrawer', false);
}
</script>