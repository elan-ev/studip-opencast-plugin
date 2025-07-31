<template>
    <div>
        <StudipDialog
            :title="$gettext('Medien herunterladen')"
            :closeText="$gettext('Schließen')"
            :closeClass="'cancel'"
            height="400"
            width="475"
            @close="this.$emit('cancel')"
        >
            <template #dialogContent>
                <div class="oc--download-list-container">
                    <div class="oc--download-list">
                        <form class="default">
                            <label>
                                {{ $gettext('Videoquelle') }}
                            </label>
                            <label
                                ><input
                                    type="radio"
                                    value="presenter"
                                    v-model="selectedSource"
                                    :disabled="!hasPresenterVideo"
                                />
                                {{ $gettext('Aufzeichnung der vortragenden Person') }}</label
                            >
                            <label
                                ><input
                                    type="radio"
                                    value="presentation"
                                    v-model="selectedSource"
                                    :disabled="!hasPresentationVideo"
                                />
                                {{ $gettext('Aufzeichnung des Bildschirms') }}</label
                            >
                            <label>
                                {{ $gettext('Videoqualität') }}
                                <select v-model="selectedMedia">
                                    <template v-if="selectedSource === 'presenter'">
                                        <option v-for="(media, index) in presenters" :key="index" :value="media">
                                            {{ getMediaText(media) }}
                                        </option>
                                    </template>
                                    <template v-else>
                                        <option
                                            v-for="(media, index) in presentations"
                                            :key="index"
                                            :value="media"
                                        >
                                            {{ getMediaText(media) }}
                                        </option>
                                    </template>
                                </select>
                            </label>
                        </form>
                    </div>
                    <div class="oc--download-messages">
                        <MessageList :float="true" :dialog="true" />
                    </div>
                </div>
            </template>
            <template #dialogButtons>
                <button
                    v-if="event.visibility == 'public'"
                    class="button"
                    :title="$gettext('Link zur Mediendatei in die Zwischenablage kopieren')"
                    @click="copyToClipboard()"
                >
                    {{ $gettext('Link kopieren') }}
                </button>
                <a :href="downloadUrl" :download="selectedFileName" class="button">
                    {{ $gettext('Herunterladen') }}
                </a>
            </template>
        </StudipDialog>
    </div>
</template>

<script>
import StudipDialog from '@studip/StudipDialog';
import MessageList from '@/components/MessageList';
import ProgressBar from '@/components/ProgressBar';

import axios from '@/common/axios.service';

export default {
    name: 'VideoDownload',

    components: {
        StudipDialog,
        MessageList,
        ProgressBar,
    },

    props: ['event'],

    data() {
        return {
            presentations: [],
            presenters: [],
            copied: null,
            selectedSource: '',
            selectedMedia: null,
        };
    },

    computed: {
        hasPresenterVideo() {
            return this.presenters.length > 0;
        },

        hasPresentationVideo() {
            return this.presentations.length > 0;
        },

        selectedMediaIndex() {
            return this.selectedMedia?.size;
        },

        downloadUrl() {
            return window.OpencastPlugin.REDIRECT_URL + '/download/' + this.event.token + '/' + this.selectedSource + '/' + this.selectedMediaIndex;
        },

        selectedFileName() {
            return this.getFileName(this.selectedMedia);
        }
    },

    methods: {
        getFileName(media) {
            let res = media?.info || '';
            res = res.replace(' * ', ' x ').replace(/\s+/g, '');
            let ext = media?.url.split('.').pop() || '';
            return this.event.title + ' (' + res + ').' + ext;
        },

        extractDownloads() {
            let presentations = this.event?.publication?.downloads?.presentation || [];
            for (const size in presentations) {
                let presentation = presentations[size];
                presentation.size = size;
                this.presentations.push(presentation);
            }

            let presenters = this.event?.publication?.downloads?.presenter || [];
            for (const size in presenters) {
                let presenter = presenters[size];
                presenter.size = size;
                this.presenters.push(presenter);
            }
        },

        getMediaText(media) {
            let text = media?.info || '';
            text = text.replace(' * ', ' x ');
            let size = media?.size || 0;

            if (size == 0) {
                return text;
            }

            size = size / 1024;

            if (size > 1024) {
                size = Math.round((size / 1024) * 10) / 10;
                text = text + ' (' + size + ' MB)';
            } else {
                size = Math.round(size * 10) / 10;
                text = text + ' (' + size + ' KB)';
            }

            return text;
        },

        copyToClipboard() {
            const text = this.selectedMedia?.url;
            navigator.clipboard.writeText(text);
            this.$store.dispatch('clearMessages', true);
            let message = {
                type: 'info',
                text: this.$gettext('Link zur Mediendatei wurde in die Zwischenablage kopiert.'),
                dialog: true,
            };
            this.$store.dispatch('addMessage', message);
            setTimeout(() => {
                this.$store.dispatch('clearMessages', true);
            }, 3000);
        },
    },

    mounted() {
        this.extractDownloads();
        if (this.hasPresenterVideo) {
            this.selectedSource = 'presenter';
            this.selectedMedia = this.presenters[0];
        } else {
            this.selectedSource = 'presentation';
            this.selectedMedia = this.presentations[0];
        }
    },
    watch: {
        selectedSource(newSource) {
            if (newSource === 'presenter') {
                this.selectedMedia = this.presenters[0];
            }
            if (newSource === 'presentation') {
                this.selectedMedia = this.presentations[0];
            }
        }
    }
};
</script>
