<template>
    <ul class="widget-list widget-links cw-action-widget">
        <li class="cw-action-widget">Medien Hochladen</li>
        <li class="cw-action-widget">Video Aufnehmen</li>
        <li class="cw-action-widget">Test Episode hinzufügen</li>
    </ul>
</template>

<script>
import StudipIcon from '../StudipIcon.vue';

export default {
    name: 'courseware-action-widget',
    components: {
        StudipIcon
    },
    data() {
        return {
        }
    },
    watch: {
        $route(to) {
            this.setCurrentId(to.params.id);
        },
    },


}
</script>2