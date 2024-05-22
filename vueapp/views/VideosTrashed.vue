<template>
    <div>
        <VideosTable :trashBin="true"/>
    </div>
</template>

<script>
import VideosTable from "@/components/Videos/VideosTable";

export default {
    name: "VideosArchive",
    components: {
      VideosTable
    }
};
</script>
