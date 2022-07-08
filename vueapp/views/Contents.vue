<template>
    <div>
        <Teleport to="#layout-sidebar > section.sidebar">
            <VideosSidebar></VideosSidebar>
        </Teleport>

        <router-view></router-view>
    </div>
</template>

<script>
import VideosSidebar from "@/components/Videos/VideosSidebar";

export default {
    name: "Contents",
    components: {
        VideosSidebar
    }
};
</script>
