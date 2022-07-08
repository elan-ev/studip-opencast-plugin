<template>
    <div class="container" id="app-episodes">
        <router-view></router-view>

    </div>
</template>

<script>
import { mapGetters } from "vuex";

export default {
    name: "Course",

    components: {

    },

    methods: {
        uploadVideo() {
            this.uploadDialog = true;
        }
    },

    mounted() {
        this.$store.dispatch('loadCurrentUser');
    }
};
</script>
