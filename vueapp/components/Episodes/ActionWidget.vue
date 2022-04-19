<template>
    <ul class="widget-list oc-widget-list widget-links cw-action-widget">
        <li class="cw-action-widget" @click="$emit('uploadVideo')">
            <studip-icon style="margin-left: -20px;" shape="upload" role="clickable"/>
            Medien Hochladen
        </li>
        <li class="cw-action-widget" @click="$emit('recordVideo')">
            <studip-icon style="margin-left: -20px;" shape="video" role="clickable"/>
            Video Aufnehmen
        </li>
        <li class="cw-action-widget" @click="$emit('seriesManager')">
            <studip-icon style="margin-left: -20px;" shape="add" role="clickable"/>
            Serien verwalten
        </li>
    </ul>
</template>

<script>
import StudipIcon from '@studip/StudipIcon.vue';

export default {
    name: 'episodes-action-widget',
    components: {
        StudipIcon
    },
    watch: {
        $route(to) {
            this.setCurrentId(to.params.id);
        },
    }
}
</script>
