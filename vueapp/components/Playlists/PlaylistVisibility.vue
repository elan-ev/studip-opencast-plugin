<template>
    <span>
        <span :class="css" v-if="visibility == 'internal'">
            <StudipIcon shape="visibility-invisible" />
            {{ $gettext('Intern') }}
        </span>

        <span :class="css" v-if="visibility == 'free'">
            <StudipIcon shape="visibility-visible" />
            {{ $gettext('Nicht gelistet') }}
        </span>

        <span :class="css" v-if="visibility == 'public'">
            <StudipIcon shape="globe" />
            {{ $gettext('Öffentlich') }}
        </span>
    </span>
</template>

<script>
import StudipIcon from '@studip/StudipIcon.vue';

export default {
    name: 'PlaylistVisibility',

    props: ['css', 'visibility'],

    components: {
        StudipIcon
    }
}
</script>
