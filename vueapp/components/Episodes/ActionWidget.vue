<template>
    <ul class="widget-list widget-links cw-action-widget">
        <li class="cw-action-widget">Medien Hochladen</li>
        <li class="cw-action-widget">Video Aufnehmen</li>
        <li class="cw-action-widget" @click="addTestEvent">Test Episode hinzufügen</li>
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
</script>2