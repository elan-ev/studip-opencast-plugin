<template>
    <ul class="widget-list oc-widget-list widget-links cw-action-widget">
        <li class="cw-action-widget">
            <studip-icon style="margin-left: -20px;" icon="upload" role="clickable"/>
            Medien Hochladen
        </li>
        <li class="cw-action-widget">
            <studip-icon style="margin-left: -20px;" icon="video" role="clickable"/>
            Video Aufnehmen
        </li>
        <li class="cw-action-widget" @click="addTestEvent">
            <studip-icon style="margin-left: -20px;" icon="add" role="clickable"/>
            Test Episode hinzufügen
        </li>
        <li class="cw-action-widget" @click="showAddDialog = true">
            <studip-icon style="margin-left: -20px;" icon="add" role="clickable"/>
            Episode hinzufügen
        </li>

        <EpisodeAdd v-if="showAddDialog"
            @done="showAddDialog = false"
            @cancel="showAddDialog = false"/>
    </ul>
</template>

<script>
import StudipIcon from '../StudipIcon.vue';
import EpisodeAdd from '@/components/Episodes/EpisodeAdd'

export default {
    name: 'courseware-action-widget',
    components: {
        StudipIcon, EpisodeAdd
    },
    data() {
        return {
            showAddDialog: false
        }
    },
    watch: {
        $route(to) {
            this.setCurrentId(to.params.id);
        },
    },

    methods: {
        addTestEvent() {
            this.$store.dispatch('addEvent',
                {
                    id: "123-x",
                    title: "testi",
                    author: "Testor",
                    type: "upload"
                }
            );
        }
    }
}
</script>
