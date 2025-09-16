<template>
    <EmptyState
        v-if="isEmpty"
        :title="$gettext('Hier ist Platz für spannende Inhalte')"
        :description="
            $gettext(
                'Sobald Videos hochgeladen oder aufgezeichnet werden, entsteht hier eine Sammlung spannender Inhalte.'
            )
        "
    >
        <template v-if="canEdit || canUpload" #buttons>
            <button class="button add" @click="addVideo('addVideoFromSystem')">
                {{ $gettext('Video hochladen') }}
            </button>
            <button class="button add" @click="addVideo('addVideoFromContents')">
                {{ $gettext('Aus Arbeitsplatz wählen') }}
            </button>
            <button class="button add" @click="addVideo('addVideoFromCourse')">
                {{ $gettext('Aus Veranstaltung wählen') }}
            </button>
        </template>
    </EmptyState>
    <section v-else class="oc--videos-all">
        <VideoCard v-for="video in videos" :key="video.token" :video="video" />
    </section>
</template>

<script setup>
import VideoCard from './VideoCard.vue';
import EmptyState from '../Layouts/EmptyState.vue';
import { computed } from 'vue';
import { useStore } from 'vuex';

const store = useStore();

const emit = defineEmits(['call-to-action']);

const videos = computed(() => {
    return store.getters['videos/globalVideos'];
});

const isEmpty = computed(() => {
    return videos.value.length === 0;
});

const courseConfig = computed(() => {
    return store.getters['config/course_config'];
});

const canEdit = computed(() => {
    return courseConfig.value?.edit_allowed ?? false;
});

const canUpload = computed(() => {
    return courseConfig.value?.upload_allowed ?? false;
});

const addVideo = (id) => {
    emit('call-to-action', { id: id });
};
</script>
