<template>
    <div>
        <div class="oc--file-preview-wrapper" v-for="file in files" :key="file.id">
            <div class="oc--file-preview__label">
                <span v-if="type === 'presenter'">{{ $gettext('Vortragende') }}</span>
                <span v-if="type === 'presentation'">{{ $gettext('Folien') }}</span>
            </div>
            <div class="oc--file-preview">
                <div class="oc--file-preview__icon">
                    <StudipIcon shape="file-video" :size="32" />
                </div>

                <div class="oc--file-preview__info">
                    <div class="oc--file-preview__name">{{ file.name }}</div>

                    <div class="oc--file-preview__meta">
                        <span>{{ file.type }}</span>
                        <span v-if="file.size"> | {{ $filters.filesize(file.size) }}</span>
                    </div>
                </div>

                <div class="oc--file-preview__actions">
                    <button
                        class="button"
                        @click="$emit('remove')"
                        :title="$gettext('Entfernen')"
                    >
                        <StudipIcon shape="trash" :size="20" />
                    </button>

                    <button
                        class="button"
                        :title="$gettext('Datei wählen')"
                        @click="$emit('choose')"
                    >
                        <StudipIcon shape="refresh" :size="20" />
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import StudipIcon from '@studip/StudipIcon.vue';
export default {
    name: 'FilePreview',
    components: {
        StudipIcon,
    },
    props: ['files', 'type', 'uploading'],
};
</script>
