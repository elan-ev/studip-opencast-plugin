<template>
    <div class="container" id="app">
        <div class="content">
            <router-view></router-view>
        </div>
        <div class="clearfix"></div>
        <p></p>

        <Error />
    </div>

</template>

<script>
import Error from "@/components/Error";

export default {
    name: "App",
    components: {
        Error
    },
    mounted() {
        this.$store.dispatch('setCID', 'test') // add proper cid here or fix it in template

        let url  = new URL(window.location);
        let page = url.pathname.substr(url.pathname.lastIndexOf("/") + 1);

        console.log('routing...');
        if (page == 'admin') {
            console.log('redirecting to admin');
            this.$router.push({ name: 'admin' });
        } else if ((page == 'scheduler')) {
            this.$router.push({ name: 'scheduler' });
        } else if ((page == 'manager')) {
            this.$router.push({ name: 'manager' });
        } else {
            this.$router.push({ name: 'episodes' });
        }
    }

};
</script>
