<template>
    <div class="container" id="opencast">
        <div class="content">
            <router-view />
            <VideoDrawer />
        </div>
        <div class="clearfix"></div>
        <LtiAuth />
    </div>
</template>

<script setup>
import LtiAuth from '@/components/LtiAuth';
import VideoDrawer from '@/components/Videos/VideoDrawer.vue';
</script>
