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
            cid: 'test'
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
                    cid: this.cid,
                    title: "testi",
                    lecturer: "Testor",
                    type: "upload"
                }
            );
        }
    }
}
</script>
