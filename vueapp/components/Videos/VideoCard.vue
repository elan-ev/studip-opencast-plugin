<template>
    <button class="oc--video-card" @click="selectVideo(video)">
        <div class="oc--video-card__thumbnail-wrapper">
            <img :src="preview" class="oc--video-card__thumbnail" />
            <div class="oc--video-card__duration">{{ readableDuration }}</div>
        </div>
        <div class="oc--video-card__info">
            <div class="oc--video-card__owner-row">
                <img :src="avatarUrl" class="oc--video-card__owner-avatar" />
                <h4 class="oc--video-card__owner-name">{{ ownerName }}</h4>
            </div>
            <h3 class="oc--video-card__title">{{ video.title }}</h3>
            <p class="oc--video-card__description">{{ video.description }}</p>
            <div class="oc--video-card__meta">
                <div class="oc--video-card__date" :title="readableDate">{{ timeAgo(video.created) }}</div>
                <div class="oc--video-card__views">
                    <StudipIcon shape="visibility-visible" role="inactive" /> {{ video.views }}
                </div>
            </div>
        </div>
    </button>
</template>

<script setup>
import { computed } from 'vue';
import { useFormat } from '@/composables/useFormat';
import { useUrlHelper } from '@/composables/useUrlHelper';
import { useVideoDrawer } from '@/composables/useVideoDrawer';
import { useAvatar } from '@/composables/useAvatar';
import StudipIcon from '@studip/StudipIcon';

const { formatDuration, formatISODateTime, timeAgo } = useFormat();
const { selectVideo } = useVideoDrawer();
const { previewSrc } = useUrlHelper();
const props = defineProps({
    video: {
        type: Object,
        required: true,
    },
});

const ownerId = computed(() => props.video.owner.id);
const ownerName = computed(() => props.video.owner.fullname);
const { avatarUrl } = useAvatar(ownerId);




const preview = previewSrc(props.video);
const readableDuration = computed(() => formatDuration(props.video.duration));
const readableDate = computed(() => formatISODateTime(props.video.created));
</script>
