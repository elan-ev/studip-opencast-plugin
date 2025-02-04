<template>
    <tr class="oc--episode" :class="{'oc-cw-video-selected' : selected}"
        v-if="event.refresh === undefined"
        :key="event.id"
        @click="$emit('setVideo', event)"
        style="cursor: pointer"
        title="Video auswählen"
    >
        <td class="oc--playercontainer">
            <span v-if="event.publication && event.preview && (event.available && event.available != '0' && !isProcessing)">
                <span class="oc--previewimage">
                    <img class="oc--previewimage"
                         :src="getImageSrc"
                         @error="setDefaultImage()"
                         height="200"
                         :ref="event.id"
                    />
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
            </span>
            <span v-else-if="!event.available || event.available == '0'" class="oc--unavailable"
                :title="$gettext('Video nicht (mehr) in Opencast vorhanden')"
            >
                <span class="oc--previewimage">
                    <img class="oc--image-button" :src="failed">
                </span>
            </span>
            <span v-else-if="event.state == 'cutting'"
               :title="$gettext('Dieses Video wartet auf den Schnitt.')"
            >
                <span class="oc--previewimage">
                    <img class="oc--image-button" :src="cut">
                </span>
            </span>
            <span v-else-if="isProcessing" class="oc--previewimage"
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
            </span>
        </td>

        <td class="oc--metadata-title">
            <div class="oc--title-container">
                <span v-if="event.publication && event.preview && event.available">
                    {{event.title}}
                </span>
                <span v-else>
                    {{event.title}}
                </span>
            </div>

            <div class="oc--tooltips">
                <div data-tooltip class="tooltip" v-if="getAccessText && canEdit">
                    <span class="tooltip-content" v-html="getAccessText"></span>
                    <studip-icon
                        shape="group2"
                        role="active"
                        :size="18"
                        @click="performAction('VideoAccess')"
                    />
                </div>

                <div data-tooltip class="tooltip" v-if="event.visibility == 'public'">
                    <span class="tooltip-content">
                        {{ $gettext('Dieses Video ist öffentlich.') }}
                    </span>
                    <studip-icon
                        shape="globe"
                        role="status-yellow"
                        :size="18"
                    />
                </div>

                <div data-tooltip class="tooltip" v-if="getInfoText">
                    <span class="tooltip-content" v-html="getInfoText"></span>
                    <studip-icon shape="info-circle" role="active" :size="18"></studip-icon>
                </div>
            </div>
            <div class="oc--tags oc--tags-video">
                <Tag v-for="tag in event.tags" v-bind:key="tag.id" :tag="tag.tag" />
            </div>
        </td>

        <td v-if="event.created && datetime(event.created)" class="oc--date responsive-hidden">
            {{ datetime(event.created) }} Uhr
        </td>
        <td v-else></td>

        <td class="oc--presenters responsive-hidden">
            {{ event.presenters ? event.presenters : '' }}
        </td>
    </tr>
    <tr v-else>
        <td :colspan="numberOfColumns">
            {{ $gettext('Kein Video vorhanden') }}
        </td>
    </tr>
</template>

<script>
import { format } from 'date-fns'
import { de } from 'date-fns/locale'

import Tag from './Tag.vue'

export default {
    name: "VideoRow",

    components: {
        Tag
    },

    props: {
        event: Object,
        simple_config_list: Object,

        numberOfColumns: {
            type: Number,
            required: true
        },
        selected: {
            type: Boolean,
            default: false
        }
    },

    data() {
        return {
            preview:  this.simple_config_list.plugin_assets_url + '/images/default-preview.png',
            play:  this.simple_config_list.plugin_assets_url + '/images/play.svg',
            cut:  this.simple_config_list.plugin_assets_url + '/images/cut.svg',
            failed:  this.simple_config_list.plugin_assets_url + '/images/failed.svg'
        }
    },

    methods: {
        performAction(action) {
            this.$emit('doAction', {event: JSON.parse(JSON.stringify(this.event)), actionComponent: action});
        },
        redirectAction(action) {
            this.event.views++;
            this.$emit('redirectAction', action);
        },

        setDefaultImage() {
            let image = this.$refs[this.event.id];
            image.src = this.simple_config_list.plugin_assets_url + '/images/default-preview.png';
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
        },

        permname(perm) {
            let translations = {
                'owner': this.$gettext('Besitzer/in'),
                'write': this.$gettext('Schreibrechte'),
                'read':  this.$gettext('Leserechte'),
                'share': this.$gettext('Kann weiterteilen')
            }

            return translations[perm] ? translations[perm] : ''
        },
    },

    computed: {
        getImageSrc() {
            return STUDIP.ABSOLUTE_URI_STUDIP + 'plugins.php/opencastv3/redirect/preview/' + this.event.token;
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

        getAccessText() {
            var txt = '';
            this.event.perms?.forEach(perm => {
                txt += '<div>' + perm.fullname + ': ' + this.permname(perm.perm) + '</div>'
            });
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

        canEdit() {
            return this.event?.perm && (this.event.perm == 'owner' || this.event.perm == 'write');
        },

        isProcessing()
        {
            // if the video is currently processing, no one can/should access it
            return (this.event.state == 'running' );
        }
    }
}
</script>
