<template>
    <div class="container" id="opencast">
        <div class="content">
            <router-view />
        </div>

        <div class="clearfix"></div>

        <LtiAuth />
        <VideoDrawer />
    </div>
</template>

<script setup>
import { useStore } from 'vuex';
import LtiAuth from '@/components/LtiAuth';
import VideoDrawer from '@/components/Videos/VideoDrawer.vue';

const store = useStore();
</script>
