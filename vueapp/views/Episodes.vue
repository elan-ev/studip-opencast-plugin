<template>
    <div class="container" id="app-episodes">
        <Navbar></Navbar>
        <EpisodeList></EpisodeList>

        <MountingPortal mountTo="#action-widget" name="sidebar-actions" append>
            <action-widget @uploadVideo="uploadVideo"></action-widget>
        </MountingPortal>
    </div>
</template>

<script>
import { mapGetters } from "vuex";

import EpisodeList from "@/components/Episodes/EpisodeList"
import Navbar from "@/components/Episodes/Navbar"
import ActionWidget from '../components/Episodes/ActionWidget';

export default {
    name: "Episodes",

    data () {
        return {
            uploadDialog: false
        }
    },

    components: {
        EpisodeList, Navbar, ActionWidget,
    },

    methods: {
        uploadVideo() {
            this.uploadDialog = true;
        }
    },

    mounted() {
        this.$store.dispatch('authenticateLti');
    }
};
</script>
