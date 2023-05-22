<template>
    <div name="oc--episode">
        <li v-if="event.refresh === undefined" :key="event.id" class="oc--flex-episode">
            <div class="oc--flex-checkbox" v-if="playlistForVideos || playlistMode || isCourse">
                <input type="checkbox" :checked="isChecked" @click.stop="toggleVideo">
            </div>

            <div class="oc--flexitem oc--flexplaycontainer">
                <div class="oc--playercontainer">
                    <a v-if="event.publication && event.preview && event.available" @click="redirectAction(`/video/` + event.token)" target="_blank">
                        <span class="oc--previewimage">
                            <img class="oc--previewimage"
                                :src="event.preview.player ? event.preview.player : event.preview.search"
                                @error="setDefaultImage(event)"
                                height="200"
                                :ref="event.id"
                            />
                            <img class="oc--playbutton" :src="play">
                            <span class="oc--views">
                                <studip-icon shape="visibility-visible" role="info_alt"></studip-icon>
                                {{ event.views }}
                            </span>
                            <span class="oc--duration">
                                {{ getDuration }}
                            </span>
                        </span>
                    </a>
                    <span v-else-if="!event.available" class="oc--unavailable">
                        {{ $gettext("Video nicht verfügbar") }}
                    </span>
                    <span v-else class="oc--previewimage">
                        <img class="oc--previewimage" :src="preview" height="200"/>
                        <!-- <p>No video uploaded</p> -->
                    </span>
                </div>
            </div>

            <div class="oc--metadata" :key="event.id">
                <div>
                    <div class="oc--metadata-title">
                        <h2>
                            {{event.title}}
                        </h2>
                        <div v-if="event.created && $filters.datetime(event.created)" class="oc--date">
                            &nbsp;- {{ $filters.datetime(event.created) }} Uhr
                        </div>
                    </div>
                    <div data-tooltip class="tooltip">
                        <span class="tooltip-content" v-html="getInfoText"></span>
                        <studip-icon shape="info-circle" role="active" :size="18"></studip-icon>
                    </div>
                    <div class="oc--tags oc--tags-video">
                        <Tag v-for="tag in event.tags" v-bind:key="tag.id" :tag="tag.tag" />
                    </div>
                    <!--
                    <div v-if="event.contributors">
                        {{ event.author }} -
                        {{ $gettext('Mitwirkende:') }}
                        {{ event.contributors }}
                    </div>
                    <div v-else>
                        {{ event.author }}
                    </div>

                    <div class="oc--metadata-description">
                        {{ event.description }}
                    </div>
                    -->
                </div>
            </div>
            <div v-if="!playlistMode && menuItems.length > 0" class="oc--actions-container">
                <StudipActionMenu :items="menuItems"
                    :collapseAt="menuItems.length > 1"
                    @performAction="performAction"
                    @redirectAction="redirectAction"
                />
            </div>

            <div v-if="playlistMode" class="oc--sort-options">
                <studip-icon
                    shape="arr_2up" role="navigation"
                    :hidden="!canMoveUp" @click="$emit('moveUp', event.token)" :title="$gettext('Element nach oben verschieben')"
                />
                <studip-icon
                    shape="arr_2down" role="navigation"
                    :hidden="!canMoveDown" @click="$emit('moveDown', event.token)" :title="$gettext('Element nach unten verschieben')"
                />
            </div>
        </li>
        <EmptyVideoCard v-else/>
    </div>
</template>

<script>
import { mapGetters } from "vuex"
import EmptyVideoCard from "@/components/Videos/EmptyVideoCard"
import ConfirmDialog from '@/components/ConfirmDialog'
import StudipButton from '@/components/Studip/StudipButton'
import StudipIcon from '@/components/Studip/StudipIcon'
import StudipActionMenu from '@/components/Studip/StudipActionMenu'

import Tag from '@/components/Tag.vue'

export default {
    name: "VideoCard",

    components: {
        StudipButton, ConfirmDialog,
        EmptyVideoCard, StudipIcon,
        StudipActionMenu, Tag
    },

    props: {
        event: Object,
        canMoveUp: {
            type: Boolean,
            default: false
        },
        canMoveDown: {
            type: Boolean,
            default: false
        },
        playlistForVideos: {
            type: Object,
            default: null
        },
        selectedVideos: {
            type: Object,
        },
        isCourse: {
            type: Boolean,
            default: false
        },
        playlistMode: {
            type: Boolean,
            default: false
        }
    },

    data() {
        return {
            preview:  window.OpencastPlugin.PLUGIN_ASSET_URL + '/images/default-preview.png',
            play:  window.OpencastPlugin.PLUGIN_ASSET_URL + '/images/play.svg',
            DeleteConfirmDialog: false,
            DownloadDialog: false,
            editDialog: false,
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

        setDefaultImage(event) {
            let image = this.$refs[event.id];
            image.src = window.OpencastPlugin.PLUGIN_ASSET_URL + '/images/default-preview.png';
        }
    },

    computed: {
        ...mapGetters([
            'playlist',
            'playlists',
            'downloadSetting',
        ]),

        downloadAllowed() {
            if (this.downloadSetting !== 'never') {
                if (this.canEdit) {
                    return true;
                }

                let playlist_download = this.playlist['allow_download'];
                if (playlist_download === null) {
                    return this.downloadSetting === 'allow';
                }
                else {
                    return playlist_download;
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
            return this.selectedVideos.indexOf(this.event.token)
                >= 0 ? true : false;
        },

        getInfoText() {
            var txt = '';
            if (this.event.author) {
                txt += '<div>Author: ' + this.event.author + '</div>';
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
                    menuItems.push({
                        id: 1,
                        label: this.$gettext('Bearbeiten'),
                        icon: 'edit',
                        emit: 'performAction',
                        emitArguments: 'VideoEdit'
                    });

                    /*
                    menuItems.push({
                        label: this.$gettext('Zu Wiedergabeliste hinzufügen'),
                        icon: 'add',
                        emit: 'performAction',
                        emitArguments: 'VideoAddToPlaylist'
                    });
                    */

                    menuItems.push({
                        id: 3,
                        label: this.$gettext('Verknüpfungen'),
                        icon: 'add',
                        emit: 'performAction',
                        emitArguments: 'VideoAddToPlaylist'
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

                    if (this.event?.preview?.has_previews) {
                        menuItems.push({
                            id: 5,
                            label: this.$gettext('Schnitteditor öffnen'),
                            icon: 'knife',
                            emit: 'redirectAction',
                            emitArguments: '/editor/' + this.event.token
                        });
                    }

                    if (this.event?.publication?.annotation_tool) {
                        menuItems.push({
                            id: 6,
                            label: this.$gettext('Anmerkungen hinzufügen'),
                            icon: 'knife',
                            emit: 'redirectAction',
                            emitArguments: '/annotation/' + this.event.token
                        });
                    }

                    menuItems.push({
                        id: 8,
                        label: this.$gettext('Entfernen'),
                        icon: 'trash',
                        emit: 'performAction',
                        emitArguments: 'VideoDelete'
                    });
                }
                if (this.downloadAllowed) {
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
                        id: 8,
                        label: this.$gettext('Unwiderruflich entfernen'),
                        icon: 'trash',
                        emit: 'performAction',
                        emitArguments: 'VideoDeletePermanent'
                    });
                }
            }

            menuItems.push({
                id: 7,
                label: this.$gettext('Technisches Feedback'),
                icon: 'support',
                emit: 'performAction',
                emitArguments: 'VideoReport'
            });

            menuItems.sort((a, b) => {
                return a.id - b.id;
            });

            return menuItems;
        },

        canEdit() {
            return this.event?.perm && (this.event.perm == 'owner' || this.event.perm == 'write');
        }
    }
}
</script>
