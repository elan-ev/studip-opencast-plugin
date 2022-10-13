<template>
    <div name="oc--episode">
        <li v-if="event.refresh === undefined" :key="event.id" class="oc--flex-episode">
            <div class="oc--flex-checkbox" v-if="playlistForVideos || playlistMode">
                 <input type="checkbox" :checked="isChecked" @click.stop="toggleVideo">
            </div>

            <div class="oc--flexitem oc--flexplaycontainer">
                <div class="oc--playercontainer">
                    <a v-if="event.publication && event.preview" @click="redirectAction(`/video/` + event.token)" target="_blank">
                        <span class="oc--previewimage">
                            <img class="oc--previewimage"
                                :src="event.preview.player ? event.preview.player : event.preview.search"
                                @error="setDefaultImage(event)"
                                height="200"
                                :ref="event.id"
                            />
                            <img class="oc--playbutton" :src="play">
                            <span class="oc--duration">
                                {{ getDuration }}
                            </span>
                            <span v-if="canEdit">
                                {{ event.views }}
                            </span>
                        </span>
                    </a>
                    <span v-else class="oc--previewimage">
                        <img class="oc--previewimage" :src="preview" height="200"/>
                        <!-- <p>No video uploaded</p> -->
                    </span>
                </div>
            </div>

            <div class="oc--metadata" :key="event.id">
                <div>
                    <h2 class="oc--metadata-title">
                        {{event.title}}
                    </h2>
                    <div class="oc--tags oc--tags-video">
                        <Tag v-for="tag in event.tags" v-bind:key="tag.id" :tag="tag.tag" />
                    </div>
                    <div>
                        {{ event.author }}
                        <span v-if="event.created && $filters.datetime(event.created)">
                            - {{ $filters.datetime(event.created) }} Uhr
                        </span>
                    </div>

                    <div v-if="event.contributors">
                        {{ $gettext('Mitwirkende:') }}
                        {{ event.contributors }}
                    </div>

                    <div class="oc--metadata-description">
                        {{ event.description }}
                    </div>
                </div>
            </div>
            <div v-if="canEdit && !playlistMode" class="oc--actions-container">
                <StudipActionMenu :items="menuItems"
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
            this.$store.dispatch('incrementViews', this.event);
            this.$emit('redirectAction', action);
        },

        setDefaultImage(event) {
            let image = this.$refs[event.id];
            image.src = window.OpencastPlugin.PLUGIN_ASSET_URL + '/images/default-preview.png';
        }
    },

    computed: {
        getDuration() {
            var sec = parseInt(this.event.duration / 1000)
            var min = parseInt(sec / 60)
            var h = parseInt(min / 60)
            return ("0" + h).substr(-2) + ":" + ("0" + min%60).substr(-2) + ":" + ("0" + sec%60).substr(-2)
        },

        isChecked() {
            return this.selectedVideos.indexOf(this.event.token)
                >= 0 ? true : false;
        },

        menuItems() {
            let menuItems = [
                {
                    label: this.$gettext('Bearbeiten'),
                    icon: 'edit',
                    emit: 'performAction',
                    emitArguments: 'VideoEdit'
                },
                {
                    label: this.$gettext('Medien runterladen'),
                    icon: 'download',
                    emit: 'performAction',
                    emitArguments: 'VideoDownload'
                },
            ];

            if (!this.isCourse) {
                /*
                menuItems.push({
                    label: this.$gettext('Zu Wiedergabeliste hinzufügen'),
                    icon: 'add',
                    emit: 'performAction',
                    emitArguments: 'VideoAddToPlaylist'
                });
                */
                menuItems.push({
                    label: this.$gettext('Verknüpfte Kurse'),
                    icon: 'add',
                    emit: 'performAction',
                    emitArguments: 'VideoAddToSeminar'
                });

                menuItems.push({
                    label: this.$gettext('Video freigeben'),
                    icon: 'share',
                    emit: 'performAction',
                    emitArguments: 'VideoAccess'
                });
            }

            if (this.event?.preview?.has_previews) {
                menuItems.push({
                    label: this.$gettext('Schnitteditor öffnen'),
                    icon: 'knife',
                    emit: 'redirectAction',
                    emitArguments: '/editor/' + this.event.token
                });
            }

            if (this.event?.publication?.annotation_tool) {
                menuItems.push({
                    label: this.$gettext('Anmerkungen hinzufügen'),
                    icon: 'knife',
                    emit: 'redirectAction',
                    emitArguments: '/annotation/' + this.event.token
                });
            }

            menuItems.push({
                label: this.$gettext('Technisches Feedback'),
                icon: 'support',
                emit: 'performAction',
                emitArguments: 'VideoReport'
            });
            menuItems.push({
                label: this.$gettext('Entfernen'),
                icon: 'trash',
                emit: 'performAction',
                emitArguments: 'VideoDelete'
            });

            return menuItems;
        },

        canEdit() {
            return this.event?.perm && (this.event.perm == 'owner' || this.event.perm == 'write');
        }
    }
}
</script>
