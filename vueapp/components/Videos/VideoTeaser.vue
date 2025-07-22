<template>
    <button class="oc--video-teaser" @click="selectVideo(video)">
        <img :src="preview" alt="" class="oc--video-teaser__thumbnail" />
        <div class="oc--video-teaser__info">
            <span class="oc--video-teaser__title">{{ video.title }}</span>
            <span class="oc--video-teaser__owner-name">{{ ownerName }}</span>
            <span class="oc--video-teaser__description">{{ video.description }}</span>
        </div>
    </button>
</template>

<script setup>
import { computed } from 'vue';
import { useUrlHelper } from '@/composables/useUrlHelper';
import { useVideoDrawer } from '@/composables/useVideoDrawer';

const { previewSrc } = useUrlHelper();
const { selectVideo } = useVideoDrawer();
const props = defineProps({
    video: {
        type: Object,
        required: true,
    },
});
const preview = previewSrc(props.video);
const ownerName = computed(() => props.video.owner.fullname);
</script>
