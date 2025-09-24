<template>
    <button class="oc--hero-video" @click="selectVideo(video)">
        <img :src="preview" class="oc--video-hero__thumbnail" />
        <div class="oc--hero-video__info">
            <div class="oc--hero-video__title">{{ video.title }}</div>
            <div v-if="video.tags.length > 0" class="oc--tags oc--tags-video">
                <Tag v-for="tag in video.tags" v-bind:key="tag.id" :tag="tag.tag" />
            </div>
            <p class="oc--hero-video__description">{{ video.description }}</p>
            <div class="oc--hero-video__meta-wrapper">
                <div class="oc--hero-video__owner-row">
                    <img :src="avatarUrl" class="oc--hero-video__owner-avatar" />
                    <span class="oc--hero-video__owner-name">{{ ownerName }}</span>
                </div>
                <div class="oc--hero-video__meta">
                    <div class="oc--hero-video__date" :title="readableDate">
                        {{ $gettext('Erstellt') }} {{ timeAgo(video.created) }}
                    </div>
                    <div class="oc--hero-video__duration">{{ $gettext('Dauer') }}: {{ readableDuration }}</div>
                    <div class="oc--hero-video__views">{{ $gettext('Aufrufe') }}: {{ video.views }}</div>
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
import Tag from '@/components/Tag.vue';

const { formatDuration, formatISODateTime, timeAgo } = useFormat();
const { previewSrc } = useUrlHelper();
const { selectVideo } = useVideoDrawer();

const props = defineProps({
    video: {
        type: Object,
        required: true,
    },
});

const ownerId = computed(() => props.video.owner.id);
const ownerName = computed(() => props.video.owner.fullname);
const { avatarUrl } = useAvatar(ownerId);

const preview = computed(() => previewSrc(props.video));
const readableDuration = computed(() => formatDuration(props.video.duration));
const readableDate = computed(() => formatISODateTime(props.video.created));
</script>
