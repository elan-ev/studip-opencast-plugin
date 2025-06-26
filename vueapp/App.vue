<template>
    <div class="container" id="opencast">
        <div class="content">
            <router-view></router-view>
        </div>
        <div class="clearfix"></div>
        <p></p>

        <LtiAuth />
    </div>

</template>

<script>
import LtiAuth from "@/components/LtiAuth";
import { mapGetters } from "vuex";

export default {
    name: "App",
    components: {
        LtiAuth
    },

    computed: {
        ...mapGetters(['site'])
    },

    // We moved the loading of simple config into the top level,
    // in order to reduce unwanted calls in various places!
    async beforeMount() {
        await this.$store.dispatch('simpleConfigListRead');
    }
};
</script>
