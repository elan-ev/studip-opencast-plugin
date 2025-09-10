<template>
    <div class="oc--playlist-card__stack">
        <div
            v-for="(video, index) in filledVideos"
            :key="index"
            class="oc--playlist-card__stack-image"
            :class="{ 'oc--playlist-card__stack-image--dummy': video.isDummy }"
        >
            <img
                v-if="!video.isDummy"
                :src="video.preview"
                alt=""
                role="img"
                :aria-label="$gettext('Vorschaubild')"
            />
            <div v-else class="oc--playlist-card__stack-placeholder">
                <StudipIcon shape="decline" :size="32" role="inactive" />
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed, onMounted, watch } from 'vue';
import StudipIcon from '@studip/StudipIcon.vue';

const props = defineProps({
    videos: { type: Array, required: true },
});

const filledVideos = computed(() => {
    const videos = props.videos.slice(0, 3).map((v) => ({
        ...v,
        preview: STUDIP.ABSOLUTE_URI_STUDIP + 'plugins.php/opencastv3/redirect/preview/' + v.token,
        isDummy: false,
    }));

    while (videos.length < 3) {
        videos.push({
            isDummy: true,
        });
    }

    return videos;
});
</script>
