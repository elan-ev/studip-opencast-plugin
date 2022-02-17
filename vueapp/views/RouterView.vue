<template>
    <div>
        <router-view></router-view>
    </div>

</template>

<script>
export default {
    name: "Course",

    mounted() {
        this.redirectRouter();
    },

    updated() {
        this.redirectRouter();
    },

    methods: {
        redirectRouter() {
            if (this.$route.path == '/') {
                if (CID) {
                    this.$router.push({ name : 'episodes' });
                } else {
                    this.$router.push({ name : 'admin' });
                }

            }
        }
    }

};
</script>
