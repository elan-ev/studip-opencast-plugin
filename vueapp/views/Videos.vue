<template>
    <div>
        <VideosTable/>
    </div>
</template>

<script>
import { mapGetters } from "vuex";
import VideosTable from "@/components/Videos/VideosTable";

export default {
    name: "Videos",
    components: {
        VideosTable
    }
};
</script>
