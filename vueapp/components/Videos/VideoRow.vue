<template>
    <tr class="oc--episode" v-if="event.refresh === undefined" :key="event.id" ref="videoRow">
        <td v-if="playlistEditable && videoSortMode">
            <a class="dragarea" title="$gettextInterpolate($gettext('Video per Drag & Drop verschieben'))">
                <img class="oc--drag-handle"
                     :src="dragHandle"
                     height="24"
                />
            </a>
        </td>

        <td v-if="showCheckbox">
            <input type="checkbox" :checked="isChecked" @click.stop="toggleVideo">
        </td>

        <td class="oc--playercontainer">
            <template v-if="isLivestream">
                <a :disabled="!livestreamInfo.isLive" @click="redirectAction(`/livestream/` + event.token)" target="_blank">
                    <span class="oc--previewimage"
                        :title="livestreamInfo.text"
                    >
                        <img class="oc--previewimage"
                            :src="defaultLightPreviewImageSrc"
                            @error="setDefaultImage()"
                            height="200"
                            :ref="event.id"
                        />
                        <span :class="[
                            'oc--livestream-overlay',
                            {'is-live': livestreamInfo.isLive}
                            ]"
                        >
                            <span class="oc--livestream-text">{{ livestreamInfo.text }}</span>
                            <span class="oc--livestream-timer" v-html="livestreamInfo.timer" />
                        </span>
                    </span>
                </a>
            </template>
            <template v-else>
                <a v-if="event.publication && event.preview && (event.available && event.available != '0')"
                   @click="redirectAction(`/video/` + event.token)" target="_blank"
                >
                    <span class="oc--previewimage">
                        <img class="oc--previewimage"
                             :src="getImageSrc"
                             @error="setDefaultImage()"
                             height="200"
                             :ref="event.id"
                        />
                        <studip-icon class="oc--image-button oc--play-button" shape="play" role="info_alt"></studip-icon>
                        <span data-tooltip class="tooltip oc--views">
                            <span class="tooltip-content">
                                {{ $gettext('Aufrufe') }}
                            </span>
                            <studip-icon shape="visibility-visible" role="info_alt"></studip-icon>
                            {{ event.views }}
                        </span>
                        <span class="oc--duration">
                            {{ getDuration }}
                        </span>
                    </span>
                </a>
                <span v-else-if="!event.available || event.available == '0'" class="oc--unavailable">
                    {{ $gettext("Video nicht verfügbar") }}
                </span>
                <a v-else-if="event.state == 'cutting'"
                   @click="redirectAction(`/editor/` + event.token)"
                   :title="$gettext('Dieses Video wartet auf den Schnitt. Hier gelangen sie direkt zum Editor!')"
                >
                    <span class="oc--previewimage">
                        <img class="oc--image-button" :src="cut">
                    </span>
                </a>
                <span v-else-if="event.state == 'running'" class="oc--previewimage"
                      :title="$gettext('Dieses Videos wird gerade von Opencast bearbeitet.')"
                >
                    <studip-icon class="oc--image-button" shape="admin" role="status-yellow"></studip-icon>
                </span>
                <span v-else-if="event.state == 'failed'" class="oc--previewimage"
                      :title="$gettext('Dieses Video hatte einen Verarbeitungsfehler. Bitte wenden sie sich an den Support!')"
                >
                    <studip-icon class="oc--image-button" shape="exclaim" role="status-red"></studip-icon>
                </span>
                <span v-else class="oc--previewimage">
                    <img class="oc--previewimage" :src="preview" height="200"/>
                    <!-- <p>No video uploaded</p> -->
                </span>
            </template>
        </td>

        <td class="oc--metadata-title">
            <div class="oc--title-container">
                <a v-if="isLivestream && livestreamInfo.isLive"
                   @click="redirectAction(`/livestream/` + event.token)" target="_blank">
                    {{event.title}}
                </a>
                <a v-else-if="event.publication && event.preview && event.available"
                   @click="redirectAction(`/video/` + event.token)" target="_blank">
                    {{event.title}}
                </a>
                <span v-else-if="event.state == 'running'" :title="$gettext('Dieses Videos wird gerade von Opencast bearbeitet.')">
                    {{event.title}}
                </span>
                <span v-else>
                    {{event.title}}
                </span>
            </div>
            <div class="oc--tags oc--tags-video">
                <Tag v-for="tag in event.tags" v-bind:key="tag.id" :tag="tag.tag" />
            </div>
        </td>

        <td v-if="event.created && $filters.datetime(event.created)" class="oc--date responsive-hidden">
            {{ $filters.datetime(event.created) }} Uhr
        </td>
        <td v-else></td>

        <td class="oc--presenters responsive-hidden">
            {{ event.presenters ?? '' }}
        </td>

        <td class="oc--tooltips">
            <div data-tooltip class="tooltip" v-if="getInfoText">
                <span class="tooltip-content" v-html="getInfoText"></span>
                <studip-icon shape="info-circle" role="active" :size="18"></studip-icon>
            </div>

            <div data-tooltip class="tooltip" v-if="getAccessText && canEdit">
                <span class="tooltip-content" v-html="getAccessText"></span>
                <studip-icon
                    shape="group2"
                    role="active"
                    :size="18"
                    @click="performAction('VideoAccess')"
                />
            </div>
        </td>

        <td v-if="!videoSortMode && showActions && menuItems.length > 0" class="actions">
            <StudipActionMenu :items="menuItems"
                              :collapseAt="menuItems.length > 1"
                              @performAction="performAction"
                              @redirectAction="redirectAction"
            />
        </td>
    </tr>
    <tr v-else>
        <td :colspan="numberOfColumns">
            {{ $gettext('Kein Video vorhanden') }}
        </td>
    </tr>
</template>

<script>
import { mapGetters } from "vuex"
import ConfirmDialog from '@/components/ConfirmDialog'
import StudipButton from '@/components/Studip/StudipButton'
import StudipIcon from '@/components/Studip/StudipIcon'
import StudipActionMenu from '@/components/Studip/StudipActionMenu'

import Tag from '@/components/Tag.vue'

export default {
    name: "VideoRow",

    components: {
        StudipButton, ConfirmDialog,
        StudipIcon, StudipActionMenu,
        Tag
    },

    props: {
        event: Object,
        numberOfColumns: {
            type: Number,
            required: true
        },
        canMoveUp: {
            type: Boolean,
            default: false
        },
        canMoveDown: {
            type: Boolean,
            default: false
        },
        selectable: {
            type: Boolean,
            default: false
        },
        isCourse: {
            type: Boolean,
            default: false
        },
        selectedVideos: {
            type: Object,
        },
        playlistEditable: {
            type: Boolean,
            default: false
        },
        showActions: {
            type: Boolean,
            default: true
        }
    },

    data() {
        return {
            preview:  window.OpencastPlugin.PLUGIN_ASSET_URL + '/images/default-preview.png',
            play:  window.OpencastPlugin.PLUGIN_ASSET_URL + '/images/play.svg',
            cut:  window.OpencastPlugin.PLUGIN_ASSET_URL + '/images/cut.svg',
            dragHandle: window.OpencastPlugin.PLUGIN_ASSET_URL + '/images/grabber_grey.svg',
            DeleteConfirmDialog: false,
            DownloadDialog: false,
            editDialog: false,
            livestreamInfo: {
                isLive: false,
                text: 'Livestream',
                timerInterval: null,
                timer: ''
            }
        }
    },

    methods: {
        removeVideo() {
            let view = this;
            this.$store.dispatch('deleteVideo', this.event.token)
                .then(() => {
                    view.DeleteConfirmDialog = false;
                });
        },

        toggleVideo(e) {
            this.$emit("toggle", {
                event_id: this.event.token,
                checked: e.target.checked ? true : false
            });
        },
        performAction(action) {
            this.$emit('doAction', {event: JSON.parse(JSON.stringify(this.event)), actionComponent: action});
        },
        redirectAction(action) {
            this.event.views++;
            this.$emit('redirectAction', action);
        },

        setDefaultImage() {
            let image = this.$refs[this.event.id];
            image.src = window.OpencastPlugin.PLUGIN_ASSET_URL + '/images/default-preview.png';
        },

        initLivestreamChecker() {
            if (!this.isLivestream) {
                return;
            }
            let now = parseInt(((new Date()).getTime() / 1000).toFixed(0));
            let start = this.livestream?.start ? parseInt(this.livestream.start) : 0;
            let end = this.livestream?.end ? parseInt(this.livestream.end) : 0;

            this.livestreamInfo.isLive = false;
            this.livestreamInfo.text = this.$gettext('Livestream');
            this.livestreamInfo.timer = '';
            if (this.livestreamInfo.timerInterval != null) {
                window.clearInterval(this.livestreamInfo.timerInterval);
            }
            this.livestreamInfo.timerInterval = null;

            // If the livestream is in future.
            if (start > now) {
                let diff = this.livestreamCountdown(start - now);
                this.livestreamInfo.timerInterval = setInterval(() => {
                    if (diff == 0) {
                        window.clearInterval(this.livestreamInfo.timerInterval);
                        this.initLivestreamChecker();
                    }
                    diff = this.livestreamCountdown(diff)
                }, 1000);
            } else if (now >= start && now <= end) { // If it is live
                this.livestreamInfo.isLive = true;
                this.livestreamInfo.text = this.$gettext('Live');
                let counter_seconds = this.livestreamDuration(now - start);
                this.livestreamInfo.timerInterval = setInterval(() => {
                    if ((now + counter_seconds) >= end) {
                        window.clearInterval(this.livestreamInfo.timerInterval);
                        this.initLivestreamChecker();
                    }
                    counter_seconds = this.livestreamDuration(counter_seconds)
                }, 1000);
            } else if (now > end) { // If it is expired
                this.livestreamInfo.timer = this.$gettext('Beendet');
            }
        },

        livestreamCountdown(diff) {
            let days = Math.floor(diff / (60 * 60 * 24));
            let days_str = ('' + days).padStart(2, '0');
            let hours = Math.floor((diff % (60 * 60 * 24)) / (60 * 60));
            let hours_str = ('' + hours).padStart(2, '0');
            let minutes = Math.floor((diff % (60 * 60)) / 60);
            let minutes_str = ('' + minutes).padStart(2, '0');
            let seconds = Math.floor((diff % 60));
            let seconds_str = ('' + seconds).padStart(2, '0');
            this.livestreamInfo.timer = (days > 0 ? `${days_str}:` : '') + `${hours_str}:${minutes_str}:${seconds_str}`
            return diff - 1;
        },

        livestreamDuration(counter_seconds) {
            let hours = Math.floor((counter_seconds % (60 * 60 * 24) / (60 * 60)));
            let hours_str = ('' + hours).padStart(2, '0');
            let minutes = Math.floor((counter_seconds % (60 * 60)) / 60);
            let minutes_str = ('' + minutes).padStart(2, '0');
            let seconds = Math.floor((counter_seconds % 60));
            let seconds_str  = ('' + seconds).padStart(2, '0');
            this.livestreamInfo.timer = `${hours_str}:${minutes_str}:${seconds_str}`
            return counter_seconds + 1;
        },

        handleColumnNumbers() {
            let video_row = this.$refs.videoRow;
            if (video_row) {
                if (video_row?.childElementCount && video_row?.childElementCount < this.numberOfColumns) {
                    let colspan = (this.numberOfColumns - video_row.childElementCount) + 1;
                    video_row.lastElementChild.setAttribute('colspan', colspan);
                }
            }
        }
    },

    computed: {
        ...mapGetters([
            'playlist',
            'playlists',
            'downloadSetting',
            'videoSortMode',
            'isLTIAuthenticated'
        ]),

        showCheckbox() {
            return this.selectable || this.playlistEditable;
        },

        getImageSrc() {
            if (this.isLTIAuthenticated[this.event.config_id]) {
                return this.event.preview.player ? this.event.preview.player : this.event.preview.search;
            } else {
                return this.defaultPreviewImageSrc;
            }
        },

        defaultPreviewImageSrc() {
            return window.OpencastPlugin.PLUGIN_ASSET_URL + '/images/default-preview.png';
        },

        defaultLightPreviewImageSrc() {
            return window.OpencastPlugin.PLUGIN_ASSET_URL + '/images/default-preview-light-40.png';
        },

        downloadAllowed() {
            if (this.downloadSetting !== 'never') {
                if (this.canEdit) {
                    return true;
                }
                else if (this.playlist && this.playlist['allow_download'] !== undefined) {
                    return this.playlist['allow_download'];
                }
                else {
                    return this.downloadSetting === 'allow';
                }
            }
            return false;
        },

        getDuration() {
            let sec = parseInt(this.event.duration / 1000)
            let min = parseInt(sec / 60)
            let h = parseInt(min / 60)

            let duration = '';
            if (h && min) {
                // if minutes AND hours are present, add a leading zero to minutes
                duration = h + ":" + ("0" + min%60).substr(-2);
            } else {
                // if only minutes are present, to NOT add a leading zero
                duration = min%60;
            }

            return duration + ":" + ("0" + sec%60).substr(-2);
        },

        isChecked() {
            return this.selectedVideos.indexOf(this.event.token) >= 0;
        },

        getAccessText() {
            var txt = '';
            if (this.event.perms !== undefined) {
                this.event.perms.forEach(perm => {
                    txt += '<div>' + this.$filters.permname(perm.perm, this.$gettext) + ': ' + perm.fullname + '</div>'
                });
            }
            return txt;
        },

        getInfoText() {
            var txt = '';
            if (this.event.presenters) {
                txt += '<div>Vortragende: ' + this.event.presenters + '</div>';
            }
            if (this.event.contributors) {
                txt += '<div>Mitwirkende: ' + this.event.contributors + '</div>';
            }
            if (this.event.description) {
                if (txt.length > 0) {
                    txt += '<br>'
                }
                txt += '<div>' + this.event.description + '</div>';
            }
            return txt;
        },

        menuItems() {
            let menuItems = [];

            if (!this.event?.trashed) {
                if (this.canEdit) {
                    if (this.event?.state !== 'running') {
                        menuItems.push({
                            id: 1,
                            label: this.$gettext('Bearbeiten'),
                            icon: 'edit',
                            emit: 'performAction',
                            emitArguments: 'VideoEdit'
                        });
                    }

                    /*
                    if (this.playlistForVideos) {
                        menuItems.push({
                            id: 3,
                            label: this.$gettext('Zur Wiedergabeliste hinzufügen'),
                            icon: 'add',
                            emit: 'performAction',
                            emitArguments: 'VideoAddToPlaylist'
                        });
                    }
                    */

                    /*
                    menuItems.push({
                        label: this.$gettext('Zu Wiedergabeliste hinzufügen'),
                        icon: 'add',
                        emit: 'performAction',
                        emitArguments: 'VideoLinkToPlaylists'
                    });
                    */

                    menuItems.push({
                        id: 3,
                        label: this.$gettext('Verknüpfungen'),
                        icon: 'group',
                        emit: 'performAction',
                        emitArguments: 'VideoLinkToPlaylists'
                    });

                    if (this.event?.perm === 'owner') {
                        menuItems.push({
                            id: 4,
                            label: this.$gettext('Video freigeben'),
                            icon: 'share',
                            emit: 'performAction',
                            emitArguments: 'VideoAccess'
                        });
                    }

                    if ((this.event?.preview?.has_previews || this.event?.state == 'cutting') && !this.isLivestream) {
                        menuItems.push({
                            id: 5,
                            label: this.$gettext('Videoeditor öffnen'),
                            icon: 'video2',
                            emit: 'performAction',
                            emitArguments: 'VideoCut'
                        });
                    }

                    if (this.event?.publication?.annotation_tool && this.event?.state !== 'running') {
                        menuItems.push({
                            id: 6,
                            label: this.$gettext('Anmerkungen hinzufügen'),
                            icon: 'chat',
                            emit: 'redirectAction',
                            emitArguments: '/annotation/' + this.event.token
                        });
                    }

                    if (this.event?.state !== 'running' && !this.isLivestream) {
                        menuItems.push({
                            id: 7,
                            label: this.$gettext('Untertitel bearbeiten'),
                            icon: 'accessibility',
                            emit: 'performAction',
                            emitArguments: 'CaptionUpload'
                        });
                    }

                    if (!this.isCourse && !this.isLivestream) {
                        menuItems.push({
                            id: 9,
                            label: this.$gettext('Zum Löschen markieren'),
                            icon: 'trash',
                            emit: 'performAction',
                            emitArguments: 'VideoDelete'
                        });
                    }

                    if (this.playlistEditable && this.playlist) {
                        menuItems.push({
                            id: 10,
                            label: this.$gettext('Aus Wiedergabeliste entfernen'),
                            icon: 'remove-circle',
                            emit: 'performAction',
                            emitArguments: 'VideoRemoveFromPlaylist'
                        });
                    }
                }
                if (this.downloadAllowed && this.event?.state !== 'running' && !this.isLivestream) {
                    menuItems.push({
                        id: 2,
                        label: this.$gettext('Medien runterladen'),
                        icon: 'download',
                        emit: 'performAction',
                        emitArguments: 'VideoDownload'
                    });
                }
            }
            else {
                if (this.canEdit) {
                    menuItems.push({
                        id: 0,
                        label: this.$gettext('Wiederherstellen'),
                        icon: 'refresh',
                        emit: 'performAction',
                        emitArguments: 'VideoRestore'
                    });
                    menuItems.push({
                        id: 11,
                        label: this.$gettext('Unwiderruflich entfernen'),
                        icon: 'trash',
                        emit: 'performAction',
                        emitArguments: 'VideoDeletePermanent'
                    });
                }
            }

            if (!this.isLivestream) {
                menuItems.push({
                    id: 8,
                    label: this.$gettext('Technisches Feedback'),
                    icon: 'support',
                    emit: 'performAction',
                    emitArguments: 'VideoReport'
                });
            }

            menuItems.sort((a, b) => {
                return a.id - b.id;
            });

            return menuItems;
        },

        canEdit() {
            return this.event?.perm && (this.event.perm == 'owner' || this.event.perm == 'write');
        },

        livestream() {
            return this.event?.livestream ?? null;
        },

        isLivestream() {
            return this.livestream !== null;
        },
    },

    mounted () {
        this.initLivestreamChecker();
        this.handleColumnNumbers();
    }
}
</script>
