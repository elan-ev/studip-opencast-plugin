<template>
    <span>
        <span :class="css" v-if="visibility == 'internal'"
            :title="$gettext('Diese Liste sehen nur Veranstaltungsnutzer/Innen, sofern dort freigegeben')"
        >
            <StudipIcon shape="visibility-invisible" />
            <span v-if="showText">
                {{ $gettext('Intern') }}
            </span>
        </span>

        <span :class="css" v-if="visibility == 'free'"
            :title="$gettext('Auf diese Liste kann jede Person zugreifen, die den dazugehörigen Freigabelink kennt')"
        >
            <StudipIcon shape="visibility-visible" />
            <span v-if="showText">
                {{ $gettext('Nicht gelistet') }}
            </span>
        </span>

        <span :class="css" v-if="visibility == 'public'"
            :title="$gettext('Auf diese Liste kann jede Person zugreifen und die List wird u.U. auch auf externen Plattformen einsehbar.')"
        >
            <StudipIcon shape="globe" />
            <span v-if="showText">
                {{ $gettext('Öffentlich') }}
            </span>
        </span>
    </span>
</template>

<script>
import StudipIcon from '@studip/StudipIcon.vue';

export default {
    name: 'PlaylistVisibility',

    props: {
        css: String,
        visibility: String,
        showText: {
            type: Boolean,
            default: true
        }
    },

    components: {
        StudipIcon
    }
}
</script>
