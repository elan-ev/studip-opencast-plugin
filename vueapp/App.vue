<template>
    <div class="container" id="opencast">
        <Error />

        <div class="content">
            <router-view></router-view>
        </div>
        <div class="clearfix"></div>
        <p></p>
    </div>

</template>

<script>
import Error from "@/components/Error";
import { mapGetters } from "vuex";

export default {
    name: "App",
    components: {
        Error
    },

    computed: {
        ...mapGetters(['page'])
    },

    mounted() {
        // check, which site was requested
        this.$router.push({name: this.page});
    }
};
</script>
