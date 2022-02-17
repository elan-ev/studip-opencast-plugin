<template>
    <ul class="widget-list oc-widget-list widget-links cw-action-widget">
        <li class="cw-action-widget" @click="showAddDialog=true;">
            <studip-icon style="margin-left: -20px;" icon="upload" role="clickable"/>
            Medien Hochladen
        </li>
        <li class="cw-action-widget">
            <studip-icon style="margin-left: -20px;" icon="video" role="clickable"/>
            Video Aufnehmen
        </li>
        <li class="cw-action-widget" @click="showSeriesDialog=true;">
            <studip-icon style="margin-left: -20px;" icon="add" role="clickable"/>
            Serien verwalten
        </li>

        <EpisodeAdd v-if="showAddDialog"
            @done="showAddDialog = false"
            @cancel="showAddDialog = false"/>

        <SeriesManager v-if="showSeriesDialog"
            @done="showSeriesDialog = false"
            @cancel="showSeriesDialog = false"/>

    </ul>
</template>

<script>
import StudipIcon from '@/components/StudipIcon.vue';
import EpisodeAdd from '@/components/Episodes/EpisodeAdd'
import SeriesManager from '@/components/SeriesManager'

export default {
    name: 'episodes-action-widget',
    components: {
        StudipIcon, EpisodeAdd, SeriesManager
    },
    data() {
        return {
            showAddDialog: false,
            showSeriesDialog: false
        }
    },
    watch: {
        $route(to) {
            this.setCurrentId(to.params.id);
        },
    },

    methods: {

    }
}
</script>
