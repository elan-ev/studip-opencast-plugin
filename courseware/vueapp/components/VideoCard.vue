<template>
    <div name="oc--episode">
        <li :key="event.id" class="oc--grid-episode" @click="$emit('setVideo', event)">
            <div class="oc--flex-checkbox" v-if="playlistForVideos || playlistMode || isCourse">
                <input type="checkbox" :checked="isChecked" @click.stop="toggleVideo">
            </div>

            <div class="oc--playercontainer">
                <a v-if="event.publication && event.preview && event.available"
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
                <a v-else-if="event.state == 'cutting'"
                    @click="redirectAction(`/editor/` + event.token)"
                    :title="$gettext('Dieses Video wartet auf den Schnitt. Hier gelangen sie direkt zum Schnitteditor!')"
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
            </div>

            <div class="oc--metadata-title">
                <h2>
                    {{event.title}}
                </h2>
            </div>

            <div v-if="event.created && datetime(event.created)" class="oc--date">
                &nbsp;- {{ datetime(event.created) }} Uhr
            </div>

            <div class="oc--tooltips">
                <div data-tooltip class="tooltip" v-if="getInfoText">
                    <span class="tooltip-content" v-html="getInfoText"></span>
                    <studip-icon shape="info-circle" role="active" :size="18"></studip-icon>
                </div>
            </div>

            <div class="oc--tags oc--tags-video">
                <Tag v-for="tag in event.tags" v-bind:key="tag.id" :tag="tag.tag" />
            </div>
        </li>
    </div>
</template>

<script>
import { mapGetters } from "vuex"
import { format } from 'date-fns'
import { de } from 'date-fns/locale'


import Tag from './Tag.vue'

export default {
    name: "VideoCard",

    components: {
        Tag
    },

    props: {
        event: Object,
        isLTIAuthenticated: Object,
        simple_config_list: Object
    },

    methods: {
        preview()
        {
            return this.simple_config_list.plugin_assets_url + '/images/default-preview.png';
        },

        play()
        {
            return this.simple_config_list.plugin_assets_url + '/images/play.png';
        },

        cut()
        {
            return this.simple_config_list.plugin_assets_url + '/images/cut.png';
        },

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
            if (this.simple_config_list.plugin_assets_url) {
                let image = this.$refs[this.event.id];
                image.src = this.simple_config_list.plugin_assets_url + '/images/default-preview.png';
            }
        },

        datetime(date) {
            if (date === null) {
                return '';
           }

            let mydate = new Date(date);

            if (mydate instanceof Date && !isNaN(mydate)) {
                return format(mydate, "d. MMM, yyyy, HH:ii", { locale: de});
            }

            return false;
        }
    },

    computed: {
        ...mapGetters([
            'playlist',
            'playlists',
            'downloadSetting',
            'videoSortMode',
        ]),

        getImageSrc()
        {
            this.plugin_assets_url;

            if (this.isLTIAuthenticated[this.event.config_id]) {
                return this.event.preview.player ? this.event.preview.player : this.event.preview.search;
            } else {
                return this.plugin_assets_url + '/images/default-preview.png';
            }
        },

        downloadAllowed() {
            if (this.downloadSetting !== 'never') {
                if (this.canEdit) {
                    return true;
                }
                else if (this.playlist && this.playlist['allow_download']) {
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
        }
    },
}
</script>
