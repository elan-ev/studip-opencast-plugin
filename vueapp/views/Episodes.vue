<template>
    <div class="container" id="app-episodes">
        <Navbar></Navbar>
        <EpisodeList></EpisodeList>
        <MountingPortal mountTo="#action-widget" name="sidebar-actions">
            <action-widget @uploadVideo="uploadVideo"></action-widget>
        </MountingPortal>
    </div>
</template>

<script>
import { mapGetters } from "vuex";

import StudipButton from "@/components/StudipButton";
import StudipIcon from "@/components/StudipIcon";
import MessageBox from "@/components/MessageBox";
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
        StudipButton, StudipIcon,
        MessageBox, EpisodeList,
        Navbar, ActionWidget,
    },

    methods: {
        uploadVideo() {
            this.uploadDialog = true;
        }
    }
};
</script>
