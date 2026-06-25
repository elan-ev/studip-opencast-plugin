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
    <div v-else >
        <EmptyState
            v-if="videos.length === 0 && searchActive"
            :title="$gettext('Keine Treffer gefunden')"
            :description="
                $gettext(
                    'Die gewählten Filter und Suchbegriffe haben leider keine Treffer ergeben.'
                )
            "
        >
            <template #buttons>
                <button class="button" @click="emit('reset-search')">
                    {{ $gettext('Suche zurücksetzen') }}
                </button>
            </template>
        </EmptyState>
        <section class="oc--videos-all">
        <VideoCard v-for="video in videos" :key="video.token" :video="video" />
        </section>
    </div>
</template>

<script setup>
import VideoCard from './VideoCard.vue';
import EmptyState from '../Layouts/EmptyState.vue';
import { computed } from 'vue';
import { useStore } from 'vuex';

const store = useStore();

const emit = defineEmits(['call-to-action', 'reset-search']);

const props = defineProps({
    filter: {
        type: Array,
        default: () => {
            return [];
        },
    },
    needle: {
        type: String,
        default: '',
    },
});

const searchActive = computed(() => {
    return props.filter.length > 0 || props.needle !== '';
});

const allVideos = computed(() => {
    return store.getters['videos/globalVideos'] || [];
});

const videos = computed(() => {
    if ((!props.filter || props.filter.length === 0) && !props.needle) {
        return allVideos.value;
    }

    return allVideos.value.filter((video) => {
        let tagMatch = false;
        if (props.filter.length > 0 && Array.isArray(video.tags)) {
            tagMatch = video.tags.some((vTag) => props.filter.some((fTag) => fTag.id === vTag.id));
        }

        let needleMatch = false;
        if (props.needle) {
            const needle = props.needle.toLowerCase();
            needleMatch =
                video.title?.toLowerCase().includes(needle) ||
                false ||
                video.description?.toLowerCase().includes(needle) ||
                false;
        }

        return tagMatch || needleMatch;
    });
});

const isEmpty = computed(() => {
    return allVideos.value.length === 0;
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
