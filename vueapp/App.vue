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

export default {
    name: "App",
    components: {
        Error
    }
};
</script>
