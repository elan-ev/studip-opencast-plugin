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
    }
};
</script>
