<template>
    <div>
        <VideosTable></VideosTable>
    </div>
</template>

<script>
import { mapGetters } from "vuex";
import VideosTable from "@/components/Videos/VideosTable";

export default {
    name: "VideosArchive",
    components: {
      VideosTable
    }
};
</script>
