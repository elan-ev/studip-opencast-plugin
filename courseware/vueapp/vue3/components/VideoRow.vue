<template>
    <tr class="oc--episode" :class="{'oc-cw-video-selected' : selected}"
        v-if="event.refresh === undefined"
        :key="event.id"
        @click="$emit('setVideo', event)"
        @keydown.enter="$emit('setVideo', event)"
        style="cursor: pointer"
        title="Video auswählen"
        tabindex="0"
    >
        <td class="oc--playercontainer">
            <span v-if="event.publication && (event.available && event.available != '0' && !isProcessing)">
                <span class="oc--previewimage">
                    <img class="oc--previewimage"
                        :src="getImageSrc"
                        @error="setDefaultImage()"
                        height="200"
                        :ref="setEventElementRefs(event.id)"
                        :alt="event.title"
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
                    <img class="oc--image-button" :src="failed" :alt="$gettext('Video nicht (mehr) in Opencast vorhanden')">
                </span>
            </span>
            <span v-else-if="event.state == 'cutting'"
                :title="$gettext('Dieses Video wartet auf den Schnitt.')"
            >
                <span class="oc--previewimage">
                    <img class="oc--image-button" :src="cut" :alt="$gettext('Dieses Video wartet auf den Schnitt.')">
                </span>
            </span>
            <span v-else-if="isProcessing" class="oc--previewimage"
                :title="$gettext('Dieses Video wird gerade von Opencast verarbeitet.')"
            >
                <studip-icon class="oc--image-button" shape="admin" role="status-yellow" :alt="$gettext('Dieses Video wird gerade von Opencast verarbeitet.')"></studip-icon>
            </span>
            <span v-else-if="event.state == 'failed'" class="oc--previewimage"
                :title="$gettext('Dieses Video hatte einen Verarbeitungsfehler. Bitte wenden Sie sich an den Support!')"
            >
                <studip-icon class="oc--image-button" shape="exclaim" role="status-red" :alt="$gettext('Dieses Video hatte einen Verarbeitungsfehler. Bitte wenden Sie sich an den Support!')"></studip-icon>
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

<script setup>
import { ref, computed, getCurrentInstance } from "vue";
import { format } from 'date-fns';
import { de } from 'date-fns/locale';
import Tag from './Tag.vue';

const { proxy } = getCurrentInstance();

const props = defineProps({
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
});

const emit = defineEmits(['doAction', 'redirectAction']);

// Computed props.
const preview = computed(() => {
    return props.simple_config_list.plugin_assets_url + '/images/default-preview.png';
});

const play = computed(() => {
    return props.simple_config_list.plugin_assets_url + '/images/play.svg';
});

const cut = computed(() => {
    return props.simple_config_list.plugin_assets_url + '/images/cut.svg';
});

const failed = computed(() => {
    return props.simple_config_list.plugin_assets_url + '/images/failed.svg';
});

const getImageSrc = computed(() => {
    return STUDIP.ABSOLUTE_URI_STUDIP + 'plugins.php/opencastv3/redirect/preview/' + props.event.token;
});

const getDuration = computed(() => {
    let sec = parseInt(props.event.duration / 1000)
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
});

const getAccessText = computed(() => {
    var txt = '';
    props.event.perms?.forEach(perm => {
        txt += '<div>' + perm.fullname + ': ' + permname(perm.perm) + '</div>'
    });
    return txt;
});

const getInfoText = computed(() => {
    var txt = '';
    if (props.event.presenters) {
        txt += '<div>Vortragende(r): ' + props.event.presenters + '</div>';
    }
    if (props.event.contributors) {
        txt += '<div>Mitwirkende: ' + props.event.contributors + '</div>';
    }
    if (props.event.description) {
        if (txt.length > 0) {
            txt += '<br>'
        }
        txt += '<div>' + props.event.description + '</div>';
    }
    return txt;
});

const canEdit = computed(() => {
    return props.event?.perm && (props.event.perm == 'owner' || props.event.perm == 'write');
});

const isProcessing = computed(() => {
    // if the video is currently processing, no one can/should access it
    return (props.event.state == 'running' );
});

// HTML Ref.
const eventElementRefs = ref(new Map());

// Methods.
const setEventElementRefs = eventId => el => {
    if (el) {
        eventElementRefs.value.set(eventId, el);
    } else {
        eventElementRefs.value.delete(eventId);
    }
};

const performAction = (action) => {
    emit('doAction', {event: JSON.parse(JSON.stringify(props.event)), actionComponent: action});
};

const redirectAction = (action) => {
    props.event.views++;
    emit('redirectAction', action);
};

const setDefaultImage = () => {
    let eventElementImage = eventElementRefs.value.get(props.event.id);
    eventElementImage.src = props.simple_config_list.plugin_assets_url + '/images/default-preview.png';
};

const datetime = (date) => {
    if (date === null) {
        return '';
    }

    let mydate = new Date(date);

    if (mydate instanceof Date && !isNaN(mydate)) {
        return format(mydate, "d. MMM, yyyy, HH:ii", { locale: de});
    }

    return false;
};

const permname = (perm) => {
    let translations = {
        'owner': proxy.$gettext('Besitzer/in'),
        'write': proxy.$gettext('Schreibrechte'),
        'read':  proxy.$gettext('Leserechte'),
        'share': proxy.$gettext('Kann weiterteilen')
    }

    return translations[perm] ? translations[perm] : ''
};
</script>
