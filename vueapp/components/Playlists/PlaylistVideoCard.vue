<template>
    <div>
        <li v-if="event.refresh === undefined" :key="event.id" style="display: flex; flex-direction: row;">
            <div class="oc--flex-checkbox" v-if="playlistForVideos">
                 <input type="checkbox" :checked="isChecked" @click.stop="toggleVideo">
            </div>

            <div class="oc--flexitem oc--flexplaycontainer">
                <div class="oc--playercontainer">
                    <a v-if="event.publication && event.preview" :href="event.paella" target="_blank">
                        <span class="oc--previewimage">
                            <img class="oc--previewimage" :src="event.preview.player ? event.preview.player : event.preview.search" height="200"/>
                            <img class="oc--playbutton" :src="play">
                            <span class="oc--duration">
                                {{ getDuration }}
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
                <div class="oc--episode-buttons">
                    <ConfirmDialog v-if="DeleteConfirmDialog"
                        :title="$gettext('Aufzeichnung entfernen')"
                        :message="$gettext('Möchten Sie die Aufzeichnung wirklich entfernen?')"
                        @done="removeVideo"
                        @cancel="DeleteConfirmDialog = false"
                    />
                </div>
            </div>
            <div class="oc--sort-options">
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


export default {
    name: "VideoCard",

    components: {
        StudipButton, ConfirmDialog,
        EmptyVideoCard, StudipIcon
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
        }
    },

    data() {
        return {
            DeleteConfirmDialog: false,
            DownloadDialog: false,
            editDialog: false,
            preview:  window.OpencastPlugin.PLUGIN_ASSET_URL + '/images/default-preview.png',
            play:  window.OpencastPlugin.PLUGIN_ASSET_URL + '/images/play.svg'
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
        }

    }
}
</script>
