<template>
    <a class="oc--download-item">
        <div class="oc--download-item-control-row">
            <div class="oc--download-btn-container">
                <div v-if="media?.loading" class="oc--download-spinner-container">
                    <div class="oc--spinner"></div>
                </div>
                <StudipButton class="oc--download-btn" @click.prevent="$emit('performDownload')"
                    :disabled="media?.loading"
                >
                    {{ getMediaText(media) }}
                </StudipButton>
                <studip-icon shape="decline"
                    :role="media?.loading ? 'clickable' : 'inactive'"
                    class="oc--download-cancel"
                    :class="media?.loading ? 'active' : 'inactive'"
                    :title="$gettext('Abbrechen')"
                    @click="$emit('performAbortDownload')"
                />
            </div>

            <div class="oc--tooltip--copy" v-if="event.visibility == 'public'">
                <div class="oc--tooltip--copy-success"
                    :class="{
                        'oc--display--block': copied == media.url
                    }"
                >
                    {{ $gettext('Kopiert!') }}
                </div>

                <studip-icon
                    :title="$gettext('Link zur Mediendatei in die Zwischenablage kopieren')"
                    @click="copyToClipboard(media.url)"
                    :shape="copied == media.url ? 'accept' : 'copy'"
                    :role="copied == media.url ? 'status-green' : 'clickable'"
                />
            </div>
        </div>

        <div class="oc--download-info-container">
            <ProgressBar v-if="media?.loading" :progress="media.progress" :minimal="true" />
        </div>
    </a>
</template>

<script>
import StudipButton from '@studip/StudipButton'
import StudipIcon from '@studip/StudipIcon';
import ProgressBar from '@/components/ProgressBar'

export default {
    name: 'VideoDownloadItem',

    components: {
        StudipButton,
        StudipIcon,
        ProgressBar
    },

    data() {
        return {
            copied: null
        }
    },

    props: ['media', 'event'],

    emits: ['performDownload', 'performAbortDownload'],

    methods: {
        getMediaText(media) {
            var text = media?.info || '';
            text = text.replace(' * ', ' x ');
            var size = media?.size || 0;

            if (size == 0) {
                return text;
            }

            size = size / 1024;

            if (size > 1024) {
                size = Math.round(size/1024 * 10) / 10
                text = text + ' (' + size + ' MB)'
            } else {
                size = Math.round(size * 10) / 10
                text = text + ' (' + size + ' KB)'
            }

            return text
        },

        copyToClipboard(text)
        {
            navigator.clipboard.writeText(text);
            this.copied = text;
            setTimeout(() => {
                this.copied = '';
            }, 3000);
        }
    },
}
</script>
