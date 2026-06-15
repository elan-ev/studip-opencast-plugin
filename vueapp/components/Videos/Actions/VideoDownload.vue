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
                        <table v-if="videoDownloads.length" class="default">
                            <caption>
                                {{ $gettext('Videos') }}
                            </caption>
                            <colgroup>
                                <col>
                                <col>
                                <col style="width: 1%">
                            </colgroup>
                            <thead>
                                <tr>
                                    <th>{{ $gettext('Videoquelle') }}</th>
                                    <th>{{ $gettext('Datei(en)') }}</th>
                                    <th>{{ $gettext('Aktionen') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="download in videoDownloads" :key="download.source + '-' + download.media.size">
                                    <td>{{ getSourceText(download.source) }}</td>
                                    <td>{{ getMediaText(download.media) }}</td>
                                    <td>
                                        <a
                                            v-if="downloadAllowed"
                                            :href="getDownloadUrl(download)"
                                            :download="getFileName(download.media)"
                                            :title="$gettext('Herunterladen')"
                                        >
                                            <StudipIcon shape="download" role="clickable" />
                                        </a>

                                        <StudipIcon
                                            v-if="event.visibility == 'public'"
                                            shape="clipboard"
                                            role="clickable"
                                            :title="$gettext('Link zur Mediendatei in die Zwischenablage kopieren')"
                                            class="oc--download-copy-icon"
                                            @click="copyToClipboard(download.media.url)"
                                        />
                                    </td>
                                </tr>
                            </tbody>
                        </table>

                        <table v-if="captions.length" class="default">
                            <caption>
                                {{ $gettext('Untertiteldateien') }}
                            </caption>
                            <colgroup>
                                <col>
                                <col>
                                <col style="width: 1%">
                            </colgroup>
                            <thead>
                                <tr>
                                    <th>{{ $gettext('Sprache') }}</th>
                                    <th>{{ $gettext('Datei(en)') }}</th>
                                    <th>{{ $gettext('Aktionen') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="caption in captions" :key="caption.identifier">
                                    <td>{{ getCaptionLanguage(caption) || $gettext('unbekannt') }}</td>
                                    <td>{{ getMediaText(caption) }}</td>
                                    <td>
                                        <a
                                            v-if="downloadAllowed"
                                            :href="caption.uri"
                                            :download="getCaptionFileName(caption)"
                                            :title="$gettext('Herunterladen')"
                                        >
                                            <StudipIcon shape="download" role="clickable" />
                                        </a>

                                        <StudipIcon
                                            v-if="event.visibility == 'public'"
                                            shape="clipboard"
                                            role="clickable"
                                            :title="$gettext('Link zur Mediendatei in die Zwischenablage kopieren')"
                                            class="oc--download-copy-icon"
                                            @click="copyToClipboard(caption.uri)"
                                        />
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="oc--download-messages">
                        <MessageList :float="true" :dialog="true" />
                    </div>
                </div>
            </template>
        </StudipDialog>
    </div>
</template>

<script>
import StudipDialog from '@studip/StudipDialog';
import StudipIcon from '@studip/StudipIcon';
import MessageList from '@/components/MessageList';
import { isDownloadAllowed } from '@/common/config-options';
import { mapGetters } from 'vuex';

export default {
    name: 'VideoDownload',

    components: {
        StudipDialog,
        StudipIcon,
        MessageList,
    },

    props: ['event'],

    data() {
        return {
            presentations: [],
            presenters: [],
        };
    },

    computed: {
        ...mapGetters(['videosMedia', 'playlist', 'downloadSetting', 'currentUser']),

        captions() {
            return (this.videosMedia || []).filter((media) => media.tags?.includes('type:closed-caption'));
        },

        downloadAllowed() {
            return isDownloadAllowed({
                downloadSetting: this.downloadSetting,
                playlist: this.playlist,
                event: this.event,
                currentUser: this.currentUser
            });
        },

        videoDownloads() {
            return [
                ...this.presenters.map((media) => ({ source: 'presenter', media })),
                ...this.presentations.map((media) => ({ source: 'presentation', media })),
            ];
        },
    },

    methods: {
        getDownloadUrl(download) {
            return window.OpencastPlugin.REDIRECT_URL
                + '/download/' + this.event.token
                + '/' + download.source
                + '/' + download.media.size;
        },

        getSourceText(source) {
            return source === 'presenter'
                ? this.$gettext('Aufzeichnung der vortragenden Person')
                : this.$gettext('Aufzeichnung des Bildschirms');
        },

        getFileName(media) {
            let res = media?.info || '';
            res = res.replace(' * ', ' x ').replace(/\s+/g, '');
            let ext = media?.url.split('.').pop() || '';
            return this.event.title + ' (' + res + ').' + ext;
        },

        getCaptionLanguage(caption) {
            return caption.tags?.find((tag) => tag.startsWith('lang:'))?.substring(5) || '';
        },

        getCaptionFileName(caption) {
            let extension = caption?.uri?.split(/[?#]/)[0].split('.').pop() || 'vtt';
            let language = this.getCaptionLanguage(caption);
            language = language ? ' (' + language + ')' : '';

            return this.event.title + language + '.' + extension;
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
            let text = media?.info || media?.uri?.split(/[?#]/)[0].split('/').pop() || '';
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

        copyToClipboard(url) {
            navigator.clipboard.writeText(url);
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

        this.$store.dispatch('loadVideoMedia', {
            token: this.event.token,
        })
    }
};
</script>

<style scoped>
.oc--download-copy-icon {
    margin-left: 5px;
    cursor: pointer;
}
</style>
