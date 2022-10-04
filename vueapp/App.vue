<template>
    <div class="container" id="opencast">
        <Error />

        <div class="content">
            <router-view></router-view>
        </div>
        <div class="clearfix"></div>
        <p></p>

        <LtiAuth />
    </div>

</template>

<script>
import Error from "@/components/Error";
import LtiAuth from "@/components/LtiAuth";
import { mapGetters } from "vuex";

export default {
    name: "App",
    components: {
        Error,  LtiAuth
    },

    computed: {
        ...mapGetters(['site'])
    },

    mounted() {
        // check, which site was requested
        this.$router.push({name: this.site});
    }
};
</script>
