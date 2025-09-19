<template>
    <!-- <div>
        <div v-for="file in files" class="oc--file-preview" :key="file.id">
            <h3 class="oc--file-type" v-if="type == 'presenter'">
                {{ $gettext('Vortragende') }}
            </h3>
            <h3 class="oc--file-type" v-if="type == 'presentation'">
                {{ $gettext('Folien') }}
            </h3>

            <span class="oc--file-name">
                <b>
                    {{ $gettext('Name:') }}
                </b>
                {{ file.name }}
            </span>

            <span class="oc--file-size" v-if="file.size">
                <b>
                    {{ $gettext('Größe:') }}
                </b>
                {{ $filters.filesize(file.size) }}
            </span>

            <span>
                <button class='button cancel'
                    type=button @click="$emit('remove', file)"
                    v-if="!uploading"
                >
                    {{ $gettext('Entfernen') }}
                </button>
            </span>

            <span v-if="file.url">
                <a :href="file.url">
                    <button class='button download'
                        type=button
                    >
                        {{ $gettext('Herunterladen') }}
                    </button>
                </a>
            </span>
        </div>
    </div> -->
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
